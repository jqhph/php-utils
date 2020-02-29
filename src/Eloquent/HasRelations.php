<?php

namespace Dcat\Utils\Eloquent;

use Illuminate\Database\Eloquent\Relations;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait HasRelations
{
    protected $__relations = [null, null];

    /**
     * {@inheritdoc}
     */
    protected static function bootHasRelations()
    {
        static::saving(function ($model) {
            /* @var static $model */
            $attributes = $model->getAttributes();

            [$relations, $relationKeyMap] = $model->getRelationInputs($attributes);

            if ($relations) {
                $updates = Arr::except($attributes, array_keys($relationKeyMap));
            }

            $model->setRawAttributes([]);

            foreach ($updates as $column => $value) {
                $model->setAttribute($column, $value);
            }
        });

        static::saved(function ($model) {
            /* @var static $model */
            $model->updateRelation();
        });
    }

    /**
     * Get inputs for relations.
     *
     * @param array $inputs
     *
     * @return array
     */
    public function getRelationInputs($inputs = [])
    {
        $map = [];
        $relations = [];

        foreach ($inputs as $column => $value) {
            $relationColumn = null;

            if (method_exists($this, $column)) {
                $relationColumn = $column;
            } elseif (method_exists($this, $camelColumn = Str::camel($column))) {
                $relationColumn = $camelColumn;
            }

            if (! $relationColumn) {
                continue;
            }

            $relation = call_user_func([$this, $relationColumn]);

            if ($relation instanceof Relations\Relation) {
                $relations[$column] = $value;

                $map[$column] = $relationColumn;
            }
        }

        return $this->__relations = [&$relations, $map];
    }


    /**
     * Update relation data.
     *
     * @param array $relationsData
     * @param array $relationKeyMap
     *
     * @throws \Exception
     */
    public function updateRelation(array $relationsData = null, array $relationKeyMap = null)
    {
        $relationsData = $relationsData ?: $this->__relations[0];
        $relationKeyMap = $relationKeyMap ?: $this->__relations[1];

        foreach ($relationsData as $name => $values) {
            $relationName = $relationKeyMap[$name];

            if (! method_exists($this, $relationName)) {
                continue;
            }

            $relation = $this->$relationName();

            $relationValues = [$name => $values];

            switch (true) {
                case $relation instanceof Relations\BelongsToMany:
                case $relation instanceof Relations\MorphToMany:
                    if (isset($relationValues[$name])) {
                        $relation->sync($relationValues[$name]);
                    }
                    break;
                case $relation instanceof Relations\HasOne:

                    $related = $this->$name;

                    // if related is empty
                    if (is_null($related)) {
                        $related = $relation->getRelated();
                        $qualifiedParentKeyName = $relation->getQualifiedParentKeyName();
                        $localKey = Arr::last(explode('.', $qualifiedParentKeyName));
                        $related->{$relation->getForeignKeyName()} = $this->{$localKey};
                    }

                    foreach ($relationValues[$name] as $column => $value) {
                        $related->setAttribute($column, $value);
                    }

                    $related->save();
                    break;
                case $relation instanceof Relations\BelongsTo:

                    $parent = $this->$name;

                    // if related is empty
                    if (is_null($parent)) {
                        $parent = $relation->getRelated();
                    }

                    foreach ($relationValues[$name] as $column => $value) {
                        $parent->setAttribute($column, $value);
                    }

                    $parent->save();

                    // When in creating, associate two models
                    if (! $this->{$relation->getForeignKey()}) {
                        $this->{$relation->getForeignKey()} = $parent->getKey();

                        $this->save();
                    }

                    break;
                case $relation instanceof Relations\MorphOne:
                    $related = $this->$name;
                    if (is_null($related)) {
                        $related = $relation->make();
                    }
                    foreach ($relationValues[$name] as $column => $value) {
                        $related->setAttribute($column, $value);
                    }
                    $related->save();
                    break;
                case $relation instanceof Relations\HasMany:
                case $relation instanceof Relations\MorphMany:

                    foreach ($relationValues[$name] as $related) {
                        /** @var Relations\Relation $relation */
                        $relation = $this->$relationName();

                        $keyName = $relation->getRelated()->getKeyName();

                        $instance = $relation->findOrNew(Arr::get($related, $keyName));

                        $instance->fill($related);

                        $instance->save();
                    }

                    break;
            }
        }
    }
}

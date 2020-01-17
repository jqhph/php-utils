<?php

namespace Dcat\Utils\Cast;

/**
 * Trait HasCaster.
 *
 * @property string $casterClass 类名
 *
 * @property array $allowedFields 允许的字段
 * @property array $denyFields 拒绝的字段
 * @property array $nullableFields 当值为true时则所有字段都允许为 null
 * @property array $stringFields
 * @property array $intFields
 * @property array $floatFields
 * @property array $booleanFields
 * @property array $arrayFields
 * @property array $newNameFields 需要重新命名key的字段
 * @property array $defaultValues 字段默认值
 */
trait HasCaster
{
    /**
     * @var Caster
     */
    protected $caster;

    /**
     * @var \Closure
     */
    protected $formatter;

    /**
     * 初始化.
     *
     * @param Caster $caster
     */
    protected function initCaster(Caster $caster)
    {
        $map = [
            'allowedFields'  => 'allow',
            'denyFields'     => 'deny',
            'nullableFields' => 'nullable',
            'intFields'      => 'integer',
            'floatFields'    => 'float',
            'booleanFields'  => 'boolean',
            'arrayFields'    => 'array',
            'newNameFields'  => 'rename',
            'defaultValues'  => 'default',
        ];

        foreach ($map as $property => $method) {
            if (! empty($this->$property)) {
                $caster->$method($this->$property);
            }
        }

        $caster->casted(function ($newRow, $row) {
            if ($f = $this->formatter) {
                return $f($newRow, $row);
            }

            return $newRow;
        });
    }

    /**
     * @param \Closure $closure
     *
     * @return void
     */
    public function formatAttributes(\Closure $closure)
    {
        $this->formatter = $closure;
    }

    /**
     * 转化数组每个元素的数据类型.
     *
     * @param array $values
     * @param bool  $addAllAllowedFields
     *
     * @return array
     */
    public function castAttributes(array $values, bool $addAllAllowedFields = false)
    {
        return $this->caster()->cast($values, $addAllAllowedFields);
    }

    /**
     * @return Caster
     */
    public function caster()
    {
        return $this->caster ?: ($this->caster = $this->newCaster());
    }

    /**
     * @return Caster
     */
    public function newCaster()
    {
        $class = empty($this->casterClass) ? Caster::class : $this->casterClass;

        $caster = new $class();

        $this->initCaster($caster);

        return $caster;
    }
}

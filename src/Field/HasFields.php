<?php

namespace Dcat\Utils\Field;

/**
 * Trait HasFields.
 *
 * @property string $fieldsClass 类名
 *
 * @property array $allowedFields 允许的字段
 * @property array $denyFields 拒绝的字段
 * @property array $nullableFields 当值为true时则所有字段都允许为 null
 * @property array $stringFields 当值为true时则所有字段都将转化为 string
 * @property array $intFields 当值为true时则所有字段都将转化为 int
 * @property array $floatFields 当值为true时则所有字段都将转化为 float
 * @property array $arrayFields 当值为true时则所有字段都将转化为 array
 * @property array $newNameFields 需要重新命名key的字段
 * @property array $defaultValues 字段默认值
 */
trait HasFields
{
    /**
     * @var Fields
     */
    protected $__fields;

    /**
     * @var \Closure
     */
    protected $formatter;

    /**
     * 初始化.
     *
     * @param Fields $fields
     */
    protected function initFields(Fields $fields)
    {
        $map = [
            'allowedFields'  => 'allow',
            'denyFields'     => 'deny',
            'nullableFields' => 'nullable',
            'intFields'      => 'integer',
            'floatFields'    => 'float',
            'arrayFields'    => 'array',
            'newNameFields'  => 'rename',
            'defaultValues'  => 'default',
        ];

        foreach ($map as $property => $method) {
            if (! empty($this->$property)) {
                $fields->$method($this->$property);
            }
        }

        $fields->formated(function ($newRow, $row) {
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
    public function formatter(\Closure $closure)
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
    public function transformValues(array $values, bool $addAllAllowedFields = false)
    {
        return $this->fields()->format($values, $addAllAllowedFields);
    }

    /**
     * @return Fields
     */
    public function fields()
    {
        return $this->__fields ?: ($this->__fields = $this->newFields());
    }

    /**
     * @return Fields
     */
    protected function newFields()
    {
        $class = empty($this->fieldsClass) ? Fields::class : $this->fieldsClass;

        $fields = new $class();

        $this->initFields($fields);

        return $fields;
    }
}

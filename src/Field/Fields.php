<?php

namespace Dcat\Utils\Field;

/**
 * @method $this allow($fields) 设置允许的字段
 * @method $this deny($fields) 设置要排除的字段
 * @method $this nullable($fields = true) 设置允许为 null 值的字段
 * @method $this string($fields = true) 设置字符串类型字段
 * @method $this integer($fields = true) 设置整型字段
 * @method $this float($fields = true) 设置浮点型字段
 * @method $this boolean($fields = true) 设置布尔型字段
 * @method $this array($fields = true) 设置数组类型字段
 * @method $this rename($key, $newKey = null) 字段重命名
 * @method $this default($key, $value = null) 设置字段默认值
 */
class Fields
{
    /**
     * @var array
     */
    protected static $map = [
        'formatters' => [
            'stringFields'  => 'formatStringField',
            'floatFields'   => 'formatFloadField',
            'intFields'     => 'formatIntField',
            'booleanFields' => 'formatBoolField',
            'arrayFields'   => 'formatArrayField',
        ],
        'setters' => [
            'allow'    => 'allowedFields',
            'deny'     => 'denyFields',
            'string'   => 'stringFields',
            'nullable' => 'nullableFields',
            'integer'  => 'intFields',
            'float'    => 'floatFields',
            'boolean'  => 'booleanFields',
            'array'    => 'arrayFields',
        ],
    ];

    /**
     * @var \Closure[]
     */
    protected static $customFormatters = [];

    /**
     * 允许的字段.
     *
     * @var array
     */
    protected $allowedFields = [];

    /**
     * 不允许的字段.
     *
     * @var array
     */
    protected $denyFields = [];

    /**
     * 当值为true时则所有字段都允许为 null.
     *
     * @var array|true
     */
    protected $nullableFields = [];

    /**
     * 当值为true时则所有字段都将转化为 string.
     *
     * @var array
     */
    protected $stringFields = [];

    /**
     * 当值为true时则所有字段都将转化为 int.
     *
     * @var array|true
     */
    protected $intFields = [];

    /**
     * 当值为true时则所有字段都将转化为 float.
     *
     * @var array|true
     */
    protected $floatFields = [];

    /**
     * 当值为true时则所有字段都将转化为 bool.
     *
     * @var array|true
     */
    protected $booleanFields = [];

    /**
     * 当值为true时则所有字段都将转化为 array.
     *
     * @var array|true
     */
    protected $arrayFields = [];

    /**
     * @var array
     */
    protected $newNameFields = [];

    /**
     * 默认值设置.
     *
     * @var array
     */
    protected $defaultValues = [];

    /**
     * 自定义格式化回调.
     *
     * @var \Closure[]
     */
    protected $fieldFormatters = [];

    /**
     * @var \Closure[]
     */
    protected $formatedCallbacks = [];

    /**
     * @var array
     */
    protected $customValues = [];

    /**
     * 格式化单个字段.
     *
     * @param string|array $field
     * @param \Closure $callback
     *
     * @return $this
     */
    public function formatField($field, \Closure $callback)
    {
        foreach ((array) $field as $f) {
            if (! isset($this->fieldFormatters[$f])) {
                $this->fieldFormatters[$f] = [];
            }

            $this->fieldFormatters[$f][] = $callback;
        }

        return $this;
    }

    /**
     * 追加的字段，如果字段已存在，则忽略.
     *
     * @param string|array $key
     * @param null $value
     *
     * @return $this
     */
    public function add($key, $value = null)
    {
        return $this->formated(function ($row) use ($key, $value) {
            if (is_array($key)) {
                foreach ($key as $k => $v) {
                    if (! array_key_exists($k, $row)) {
                        $row[$k] = $v;
                    }
                }

                return $row;
            }

            $row[$key] = $value;

            return $this;
        });
    }

    /**
     * 合并字段.
     *
     * @param array $values
     *
     * @return $this
     */
    public function merge(array $values)
    {
        return $this->formated(function ($row) use ($values) {
            return array_merge($row, $values);
        });
    }

    /**
     * @param \Closure $closure
     *
     * @return $this
     */
    public function formated(\Closure $closure)
    {
        $this->formatedCallbacks[] = $closure;

        return $this;
    }

    /**
     * 转换字段类型.
     *
     * @param array $row
     * @param bool  $addAllAllowedFields
     *
     * @return array
     */
    public function format(array $row, bool $addAllAllowedFields = false)
    {
        if (! array_is_assoc($row)) {
            $newRows = [];
            foreach ($row as &$v) {
                if (is_array($v)) {
                    $newRows[] = $this->format($v, $addAllAllowedFields);
                }
            }

            return $newRows;
        }

        $newRow = [];

        foreach ($this->getAllowedFields($row) as $field) {
            if ($this->denyFields && in_array($field, $this->denyFields)) {
                continue;
            }

            if (
                $addAllAllowedFields === false
                && ! array_key_exists($field, $row)
                && ! $this->hasDefaultValue($field)
            ) {
                continue;
            }

            // 获取字段值，如果没有则取默认值
            $newRow[$field] = $this->getValue($row, $field);

            // 判断是否允许null类型
            $nullable = $this->nullableFields === true ? true : in_array($field, $this->nullableFields);

            $value = $this->applyFormatters($field, $newRow, $nullable, $formated);
            if ($formated) {
                $newRow[$field] = $value;
                $newRow[$field] = $this->applyFieldFormatters($field, $newRow, $row);

                continue;
            }

            if (is_null($newRow[$field]) || is_scalar($newRow[$field])) {
                // 默认转化为string类型，如果不是标量变量，则不做处理
                $newRow[$field] = Formatter::formatStringField($newRow, $field, $nullable);
            }

            // 默认格式化为字符串类型
            $newRow[$field] = $this->applyFieldFormatters($field, $newRow, $row);
        }

        return $this->applyFormatedCallbacks($newRow, $row);
    }

    /**
     * @param string $field
     * @param array  $newRow
     * @param bool   $nullable
     * @param mixed  $formated
     *
     * @return mixed|void
     */
    protected function applyFormatters(string $field, array &$newRow, bool $nullable, &$formated)
    {
        if ($this->customValues) {
            foreach (static::$customFormatters as $method => $callback) {
                if ($this->isField($this->customValues[$method] ?? null, $field)) {
                    $formated = true;

                    return $callback($newRow, $field, $nullable);
                }
            }
        }

        foreach (static::$map['formatters'] as $property => $method) {
            // 格式化字段值
            if ($this->isField($this->$property, $field)) {
                $formated = true;

                return Formatter::$method($newRow, $field, $nullable);
            }
        }

        $formated = false;
    }

    /**
     * @param true|array $fields
     * @param string     $field
     *
     * @return bool
     */
    protected function isField($fields, string $field)
    {
        return $fields && ($fields === true || in_array($field, $fields));
    }

    /**
     * @param array $newRow
     * @param array $row
     *
     * @return array
     */
    protected function applyFormatedCallbacks(array &$newRow, array &$row)
    {
        $newRow = $this->replaceKey($newRow);

        if ($this->formatedCallbacks) {
            foreach ($this->formatedCallbacks as $f) {
                $newRow = $f($newRow, $row);
            }
        }

        return $newRow;
    }

    /**
     * 字段重命名.
     *
     * @param array $row
     *
     * @return array
     */
    protected function replaceKey(array $row)
    {
        foreach ($this->newNameFields as $field => $newField) {
            if (! array_key_exists($field, $row)) {
                continue;
            }

            $row[$newField] = $row[$field];
            unset($row[$field]);
        }

        return $row;
    }

    /**
     * @param string $field
     * @param array  $newRow
     * @param array  $row
     *
     * @return mixed
     */
    protected function applyFieldFormatters($field, array $newRow, array $row)
    {
        if (empty($this->fieldFormatters[$field])) {
            return $newRow[$field];
        }

        foreach ($this->fieldFormatters[$field] as $callback) {
            $value = $callback($newRow[$field], $newRow, $row, $field);
        }

        return $value;
    }

    /**
     * @param array  $row
     * @param string $field
     *
     * @return bool
     */
    public function hasDefaultValue($field)
    {
        return array_key_exists($field, $this->defaultValues);
    }

    /**
     * @param array $row
     * @param string $field
     *
     * @return mixed|null
     */
    protected function getValue(array &$row, $field)
    {
        if (! isset($row[$field]) && $this->hasDefaultValue($field)) {
            return $this->defaultValues[$field];
        }

        return $row[$field] ?? null;
    }

    /**
     * 所有允许的字段.
     *
     * @param array $row
     *
     * @return array
     */
    protected function getAllowedFields(array &$row)
    {
        return $this->allowedFields ?: array_keys($row);
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return $this
     */
    public function __call($name, $arguments)
    {
        if (isset(static::$customFormatters[$name])) {
            $fields = $arguments[0] ?? true;
            $fields = $fields === true ? true : (array) $fields;

            $this->customValues[$name] = $fields;
        } elseif (isset(static::$map['setters'][$name])) {
            if ($name === 'allow' || $name === 'deny') {
                $fields = (array) ($arguments[0] ?? []);
            } else {
                $fields = $arguments[0] ?? true;
                $fields = $fields === true ? true : (array) $fields;
            }

            $this->{static::$map['setters'][$name]} = $fields;
        } elseif ($name === 'rename') {
            if (is_array($arguments[0])) {
                $this->newNameFields = $arguments[0];
            } else {
                $this->newNameFields[$arguments[0]] = $arguments[1] ?? null;
            }
        } elseif ($name === 'default') {
            if (is_array($arguments[0])) {
                $this->defaultValues = $arguments[0];
            } else {
                $this->defaultValues[$arguments[0]] = $arguments[1] ?? null;
            }
        } else {
            if ($arguments[0] instanceof \Closure) {
                $this->formatField($name, $arguments[0]);
            } else {
                $this->defaultValues[$name] = $arguments[0];
            }
        }

        return $this;
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return array
     */
    public static function __callStatic($name, $arguments)
    {
        return static::make()
            ->$name($arguments[0])
            ->format($arguments[1], (bool) ($arguments[2] ?? false));
    }

    /**
     * 格式化并返回一个新数组.
     *
     * @param array $row 单行或多行数组
     * @param bool  $addAllAllowedFields
     *
     * @return array
     */
    public static function transform(array $row, bool $addAllAllowedFields = false)
    {
        return static::make()->format($row, $addAllAllowedFields);
    }

    /**
     * 实例化 Fields 对象
     *
     * @param mixed ...$params
     *
     * @return static
     */
    public static function make(...$params)
    {
        return new static(...$params);
    }

    /**
     * 注册自定义字段格式化工具
     *
     * @param string $method
     * @param \Closure $formatter
     *
     * @return void
     */
    public static function extend(string $method, \Closure $formatter)
    {
        static::$customFormatters[$method] = $formatter;
    }
}

<?php

namespace Dcat\Utils\Cast;

/**
 * @method $this allow($fields) 设置允许的字段
 * @method $this deny($fields) 设置要排除的字段
 * @method $this nullable($fields = true) 设置允许为 null 值的字段
 * @method $this string($fields) 设置字符串类型字段
 * @method $this integer($fields) 设置整型字段
 * @method $this float($fields) 设置浮点型字段
 * @method $this boolean($fields) 设置布尔型字段
 * @method $this array($fields) 设置数组类型字段
 * @method $this rename($key, $newKey = null) 字段重命名
 * @method $this default($key, $value = null) 设置字段默认值
 */
class Caster
{
    const TYPE_INT = 'integer';
    const TYPE_INT_NULLABLE = 'integer|nullable';

    const TYPE_STRING = 'string';
    const TYPE_STRING_NULLABLE = 'string|nullable';

    const TYPE_BOOL = 'boolean';
    const TYPE_BOOL_NULLABLE = 'boolean|nullable';

    const TYPE_FLOAT = 'float';
    const TYPE_FLOAT_NULLABLE = 'float|nullable';

    const TYPE_ARRAY = 'array';
    const TYPE_ARRAY_NULLABLE = 'array|nullable';

    const TYPE_NULLABLE = 'nullable';

    /**
     * @var array
     */
    protected static $definitions = [
        'casts' => [
            'stringFields'  => 'castValueAsString',
            'floatFields'   => 'castValueAsFloat',
            'intFields'     => 'castValueAsInt',
            'booleanFields' => 'castValueAsBool',
            'arrayFields'   => 'castValueAsArray',
        ],
        'setters' => [
            'deny'              => 'denyFields',
            self::TYPE_STRING   => 'stringFields',
            self::TYPE_NULLABLE => 'nullableFields',
            self::TYPE_INT      => 'intFields',
            self::TYPE_FLOAT    => 'floatFields',
            self::TYPE_BOOL     => 'booleanFields',
            self::TYPE_ARRAY    => 'arrayFields',
        ],
    ];

    /**
     * @var \Closure[]
     */
    protected static $customCallbacks = [];

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
     * @var array
     */
    protected $stringFields = [];

    /**
     * @var array|true
     */
    protected $intFields = [];

    /**
     * @var array|true
     */
    protected $floatFields = [];

    /**
     * @var array|true
     */
    protected $booleanFields = [];

    /**
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
    protected $fieldCallbacks = [];

    /**
     * @var \Closure[]
     */
    protected $castedCallbacks = [];

    /**
     * @var array
     */
    protected $customValues = [];

    /**
     * 设置允许的字段.
     *
     * @param string|array $fields
     *
     * @return $this
     */
    public function fields($fields)
    {
        $this->allowedFields = array_merge($this->allowedFields, (array) $fields);

        return $this;
    }

    /**
     * 转化单个字段.
     *
     * @param string|array $field
     * @param \Closure $callback
     *
     * @return $this
     */
    public function castField($field, \Closure $callback)
    {
        foreach ((array) $field as $f) {
            if (! isset($this->fieldCallbacks[$f])) {
                $this->fieldCallbacks[$f] = [];
            }

            $this->fieldCallbacks[$f][] = $callback;
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
        return $this->casted(function ($row) use ($key, $value) {
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
        return $this->casted(function ($row) use ($values) {
            return array_merge($row, $values);
        });
    }

    /**
     * @param \Closure $closure
     *
     * @return $this
     */
    public function casted(\Closure $closure)
    {
        $this->castedCallbacks[] = $closure;

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
    public function cast(array $row, bool $addAllAllowedFields = false)
    {
        if (! array_has_string_key($row)) {
            $newRows = [];
            foreach ($row as &$v) {
                if (is_array($v)) {
                    $newRows[] = $this->cast($v, $addAllAllowedFields);
                }
            }

            return $newRows;
        }

        $newRow = [];

        foreach ($this->getAllowedFields($row) as $field => $fieldType) {
            if (is_int($field)) {
                $field = $fieldType;
                $fieldType = null;
            }

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

            // 要转化的类型
            $fieldType = $fieldType ? explode('|', $fieldType) : [];

            // 判断是否允许null类型
            $nullable = in_array(static::TYPE_NULLABLE, $fieldType)
                ? true
                : ($this->nullableFields === true ? true : in_array($field, $this->nullableFields));

            // 获取字段值，如果没有则取默认值
            $newRow[$field] = $this->getValue($row, $field);

            // 如果定义了类型则转化
            $value = $this->castValue($field, $fieldType, $newRow, $nullable, $casted);
            if ($casted) {
                $newRow[$field] = $value;
                $newRow[$field] = $this->applyFieldCallbacks($field, $newRow, $row);

                continue;
            }

            // 默认转化为string类型，如果不是标量变量，则不做处理
            if (is_null($newRow[$field]) || is_scalar($newRow[$field])) {
                $newRow[$field] = static::castValueAsString($newRow, $field, $nullable);
            }

            // 默认格式化为字符串类型
            $newRow[$field] = $this->applyFieldCallbacks($field, $newRow, $row);
        }

        return $this->applyCastedCallbacks($newRow, $row);
    }

    /**
     * @param string $field
     * @param array  $fieldType
     * @param array  $newRow
     * @param bool   $nullable
     * @param mixed  $casted
     *
     * @return mixed|void
     */
    protected function castValue(string $field, array $fieldType, array &$newRow, bool $nullable, &$casted)
    {
        // 自定义类型优先处理
        foreach (static::$customCallbacks as $method => $callback) {
            if (
                $this->isField($this->customValues[$method] ?? null, $field)
                || in_array($method, $fieldType)
            ) {
                $casted = true;

                return $callback($newRow, $field, $nullable);
            }
        }

        // 取出要转化的类型
        $type = null;
        foreach ($fieldType as $v) {
            if ($v !== static::TYPE_NULLABLE) {
                $type = $v;

                break;
            }
        }

        // 转化类型
        $typeProperty = static::$definitions['setters'][$type] ?? null;
        if (! empty(static::$definitions['casts'][$typeProperty])) {
            $castMethod = static::$definitions['casts'][$typeProperty];

            $casted = true;

            return static::$castMethod($newRow, $field, $nullable);
        }

        // 转化类型
        foreach (static::$definitions['casts'] as $property => $method) {
            if ($this->isField($this->$property, $field)) {
                $casted = true;

                return static::$method($newRow, $field, $nullable);
            }
        }

        $casted = false;
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
    protected function applyCastedCallbacks(array &$newRow, array &$row)
    {
        $newRow = $this->replaceKey($newRow);

        if ($this->castedCallbacks) {
            foreach ($this->castedCallbacks as $f) {
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
    protected function applyFieldCallbacks($field, array $newRow, array $row)
    {
        if (empty($this->fieldCallbacks[$field])) {
            return $newRow[$field];
        }

        foreach ($this->fieldCallbacks[$field] as $callback) {
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
     * @param string       $type
     * @param string|array $field
     */
    protected function setCustomValue($type, $field)
    {
        if (! isset($this->customValues[$type])) {
            $this->customValues[$type] = [];
        }

        $this->customValues[$type] = array_merge($this->customValues[$type], (array) $field);
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return $this
     */
    public function __call($name, $arguments)
    {
        if (isset(static::$customCallbacks[$name])) {
            $this->setCustomValue($name, $arguments[0] ?? []);
        } elseif ($name === 'allow') {
            $this->fields($arguments[0] ?? []);
        } elseif (isset(static::$definitions['setters'][$name])) {
            if ($name === 'deny' || $name === 'nullable') {
                $fields = (array) ($arguments[0] ?? []);
            } else {
                $fields = $arguments[0] ?? true;
                $fields = $fields === true ? true : (array) $fields;
            }

            $p = static::$definitions['setters'][$name];

            if ($fields === true) {
                $this->$p = $fields;
            } else {
                $this->$p = is_array($this->$p) ? array_merge($this->$p, $fields) : $fields;
            }
        } elseif ($name === 'rename') {
            if (is_array($arguments[0])) {
                $this->newNameFields = array_merge($this->newNameFields, $arguments[0]);
            } else {
                $this->newNameFields[$arguments[0]] = $arguments[1] ?? null;
            }
        } elseif ($name === 'default') {
            if (is_array($arguments[0])) {
                $this->defaultValues = array_merge($this->defaultValues, $arguments[0]);
            } else {
                $this->defaultValues[$arguments[0]] = $arguments[1] ?? null;
            }
        } else {
            if ($arguments[0] instanceof \Closure) {
                $this->castField($name, $arguments[0]);
            } else {
                throw new \Exception("Call undefined method [$name]!");
            }
        }

        return $this;
    }

    /**
     * @param array $row
     * @param $field
     * @param bool $nullable
     *
     * @return string|null
     */
    public static function castValueAsString(array $row, string $field, bool $nullable)
    {
        if ($nullable) {
            return isset($row[$field]) ? (string) $row[$field] : null;
        }

        return (string) $row[$field];
    }

    /**
     * @param array $row
     * @param string $field
     * @param bool $nullable
     *
     * @return array|null
     */
    public static function castValueAsArray(array $row, string $field, bool $nullable)
    {
        if ($nullable) {
            return isset($row[$field]) ? (array) $row[$field] : null;
        }

        return (array) ($row[$field] ?? []);
    }

    /**
     * @param array $row
     * @param string $field
     * @param bool $nullable
     *
     * @return int|null
     */
    public static function castValueAsInt(array $row, string $field, bool $nullable)
    {
        if ($nullable) {
            return isset($row[$field]) ? (int) $row[$field] : null;
        }

        return (int) ($row[$field] ?? 0);
    }

    /**
     * @param array $row
     * @param string $field
     * @param bool $nullable
     *
     * @return bool|null
     */
    public static function castValueAsBool(array $row, string $field, bool $nullable)
    {
        if ($nullable) {
            return isset($row[$field]) ? (bool) $row[$field] : null;
        }

        return (bool) ($row[$field] ?? false);
    }

    /**
     * @param array $row
     * @param string $field
     * @param bool $nullable
     *
     * @return float|void
     */
    public static function castValueAsFloat(array $row, string $field, bool $nullable)
    {
        if ($nullable) {
            return isset($row[$field]) ? (float) $row[$field] : null;
        }

        return (float) ($row[$field] ?? 0);
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
            ->cast($arguments[1], (bool) ($arguments[2] ?? false));
    }

    /**
     * 转换格式.
     *
     * @param array $row 单行或多行数组
     * @param bool  $addAllAllowedFields
     *
     * @return array
     */
    public static function transform(array $row, bool $addAllAllowedFields = false)
    {
        return static::make()->cast($row, $addAllAllowedFields);
    }

    /**
     * @param mixed ...$params
     *
     * @return static
     */
    public static function make(...$params)
    {
        return new static(...$params);
    }

    /**
     * 注册自定义字段格式化工具.
     *
     * @param string $method
     * @param \Closure $formatter
     *
     * @return void
     */
    public static function extend(string $method, \Closure $formatter)
    {
        static::$customCallbacks[$method] = $formatter;
    }
}

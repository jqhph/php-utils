<?php

namespace Dcat\Utils\Field;

/**
 * @method $this allow($fields) 设置允许的字段
 * @method $this deny($fields) 设置要排除的字段
 * @method $this nullable($fields = true) 设置允许为 null 值的字段
 * @method $this string($fields = true) 设置字符串类型字段
 * @method $this integer($fields = true) 设置整型字段
 * @method $this float($fields = true) 设置浮点型字段
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
        'formatter' => [
            'stringFields' => 'formatStringField',
            'floatFields'  => 'formatFloadField',
            'intFields'    => 'formatIntField',
            'arrayFields'  => 'formatArrayField',
        ],
        'setter' => [
            'allow'    => 'allowedFields',
            'deny'     => 'denyFields',
            'nullable' => 'nullableFields',
            'integer'  => 'intFields',
            'float'    => 'floatFields',
            'array'    => 'arrayFields',
        ],
    ];

    /**
     * 自定义格式化回调.
     *
     * @var array
     */
    protected $customFormatters = [];

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
     * 当值为true时则所有字段都将转化为 array.
     *
     * @var array|true
     */
    protected $arrayFields = [];

    /**
     * @var array
     */
    protected $renameFields = [];

    /**
     * 默认值设置.
     *
     * @var array
     */
    protected $defaultValues = [];

    /**
     * @var \Closure[]
     */
    protected $formatedCallbacks = [];

    public function __construct(array $allowedFields = [], array $denyFields = [])
    {
        $this->allow($allowedFields);
        $this->deny($denyFields);

        $this->init();
    }

    /**
     * 初始化自定义字段格式化功能.
     */
    protected function init()
    {
    }

    /**
     * 注册自定义字段格式化回调方法.
     *
     * @param string|array $field
     * @param \Closure $callback
     *
     * @return $this
     */
    public function register($field, \Closure $callback)
    {
        foreach ((array) $field as $f) {
            if (! isset($this->customFormatters[$f])) {
                $this->customFormatters[$f] = [];
            }

            $this->customFormatters[$f][] = $callback;
        }

        return $this;
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
     * 格式化单行数据.
     *
     * @param array    $row
     * @param \Closure $callback
     *
     * @return array
     */
    public function format(array $row, $callback = null)
    {
        if (! array_is_assoc($row)) {
            $newRows = [];
            foreach ($row as &$v) {
                if (is_array($v)) {
                    $newRows[] = $this->format($v, $callback);
                }
            }

            return $newRows;
        }

        $this->setupAllowedFields($row);

        $newRow = [];

        foreach ($this->allowedFields as $field) {
            if ($this->denyFields && in_array($field, $this->denyFields)) {
                continue;
            }

            // 获取字段值，如果没有则追加默认值
            $newRow[$field] = $this->getValue($row, $field);

            // 判断是否允许null类型
            $nullable = $this->nullableFields === true ? true : in_array($field, $this->nullableFields);

            foreach (static::$map['formatter'] as $property => $method) {
                // 格式化字段值
                if (
                    $this->$property
                    && ($this->$property === true || in_array($field, $this->$property))
                ) {
                    $newRow[$field] = $this->$method($newRow, $field, $nullable);
                    $newRow[$field] = $this->formatValue($field, $newRow, $row);

                    continue 2;
                }
            }

            if (is_scalar($newRow[$field])) {
                // 默认转化为string类型，如果不是标量变量，则不做处理
                $newRow[$field] = $this->formatStringField($newRow, $field, $nullable);
            }

            // 默认格式化为字符串类型
            $newRow[$field] = $this->formatValue($field, $newRow, $row);
        }

        return $this->formatRow($newRow, $row, $callback);
    }

    /**
     * @param array    $newRow
     * @param array    $row
     * @param \Closure $callback
     *
     * @return array
     */
    protected function formatRow(array &$newRow, array &$row, $callback)
    {
        $newRow = $this->replaceKey($newRow);

        if ($this->formatedCallbacks) {
            foreach ($this->formatedCallbacks as $f) {
                $newRow = $f($newRow, $row);
            }
        }

        if ($callback && $callback instanceof \Closure) {
            return $callback($newRow, $row);
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
        foreach ($this->renameFields as $field => $newField) {
            if (! isset($row[$field])) {
                continue;
            }

            $row[$newField] = $row[$field];
            unset($row[$field]);
        }

        return $row;
    }

    /**
     * @param string $field
     * @param array  $row
     * @param array  $orginalRow
     *
     * @return mixed
     */
    protected function formatValue($field, array $row, array $orginalRow)
    {
        if (empty($this->customFormatters[$field])) {
            return $row[$field];
        }

        foreach ($this->customFormatters[$field] as $callback) {
            $value = $callback($row[$field], $row, $orginalRow, $field);
        }

        return $value;
    }

    /**
     * @param array $row
     * @param $field
     * @param bool $nullable
     *
     * @return string|null
     */
    protected function formatStringField(array $row, $field, bool $nullable)
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
    protected function formatArrayField(array $row, $field, bool $nullable)
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
    protected function formatIntField(array $row, $field, bool $nullable)
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
     * @return float|void
     */
    protected function formatFloadField(array $row, $field, bool $nullable)
    {
        if ($nullable) {
            return isset($row[$field]) ? (float) $row[$field] : null;
        }

        return (float) (isset($row[$field]) ? $row[$field] : 0);
    }

    /**
     * @param array $row
     * @param string $field
     *
     * @return mixed|null
     */
    protected function getValue(array &$row, $field)
    {
        if (! isset($row[$field]) && isset($this->defaultValues[$field])) {
            return $this->defaultValues[$field];
        }

        return $row[$field] ?? null;
    }

    /**
     * 设置所有允许的字段.
     *
     * @param array $row
     *
     * @return void
     */
    protected function setupAllowedFields(array &$row)
    {
        if (! $this->allowedFields) {
            $this->allowedFields = array_keys($row);
        }
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return $this
     */
    public function __call($name, $arguments)
    {
        if (isset(static::$map['setter'][$name])) {
            $fields = $arguments[0] ?? true;
            $fields = $fields === true ? true : (array) $fields;

            $this->{static::$map['setter'][$name]} = $fields;
        } elseif ($name === 'rename') {
            if (is_array($arguments[0])) {
                $this->renameFields = $arguments[0];
            } else {
                $this->renameFields[$arguments[0]] = $arguments[1] ?? null;
            }
        } elseif ($name === 'default') {
            if (is_array($arguments[0])) {
                $this->defaultValues = $arguments[0];
            } else {
                $this->defaultValues[$arguments[0]] = $arguments[1] ?? null;
            }
        } else {
            if ($arguments[0] instanceof \Closure) {
                $this->register($name, $arguments[0]);
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
            ->format($arguments[1], $arguments[2] ?? null);
    }

    /**
     * 格式化并返回一个新数组.
     *
     * @param array    $row 单行或多行数组
     * @param \Closure $callback
     *
     * @return array
     */
    public static function transform(array $row, \Closure $callback = null)
    {
        return static::make()->format($row, $callback);
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
}

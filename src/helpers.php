<?php

if (! function_exists('array_chunk_each')) {
    /**
     * 数组分块处理.
     *
     * @param array    $items
     * @param int      $len
     * @param callable $callback
     */
    function array_chunk_each(array $items, int $len, callable $callback)
    {
        if (! $items) {
            return;
        }

        if (count($items) <= $len) {
            call_user_func($callback, $items, 1);

            return;
        }

        $i = 0;
        while ($new = array_slice($items, $i * $len, $len)) {
            $i++;

            call_user_func($callback, $new, $i);
        }
    }
}

if (! function_exists('paginate_array')) {
    /**
     * 获取分页数组.
     *
     * @param int                   $total
     * @param array|object|\Closure $list
     *
     * @return array ['total' => $total, 'list' => $list]
     */
    function paginate_array($total, $list)
    {
        if ($list instanceof \Closure) {
            return paginate_array($total, $total > 0 ? $list($total) : []);
        }

        if (is_object($list) && method_exists($list, 'toArray')) {
            $list = $list->toArray();
        }

        return [
            'total' => $total,
            'list'  => &$list,
        ];
    }
}

if (! function_exists('str_snake')) {
    /**
     * Convert a string to snake case.
     *
     * @param string $value
     * @param string $delimiter
     * @return string
     */
    function str_snake($value, $delimiter = '_')
    {
        static $snakeCache = [];

        $key = $value;

        if (isset($snakeCache[$key][$delimiter])) {
            return $snakeCache[$key][$delimiter];
        }

        if (! ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));

            $value = mb_strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1'.$delimiter, $value), 'UTF-8');
        }

        return $snakeCache[$key][$delimiter] = $value;
    }
}

if (! function_exists('array_is_assoc')) {
    /**
     * 判断是否是关联数组.
     *
     * 如果数组的 key 不是以 0 开始的连续的数字，则该数组是关联数组
     *
     * @param  array  $array
     *
     * @return bool
     */
    function array_is_assoc(array $array)
    {
        if (! $array) {
            return false;
        }

        $keys = array_keys($array);

        return array_keys($keys) !== $keys;
    }
}

if (! function_exists('array_has_string_key')) {
    /**
     * 判断数组是否包含字符串类型的 key.
     *
     * @param  array  $array
     *
     * @return bool
     */
    function array_has_string_key(array $array)
    {
        if (! $array) {
            return false;
        }

        foreach (array_keys($array) as &$k) {
            if (! is_int($k)) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('array_rename')) {
    /**
     * 数组key重命名.
     *
     * @param array|ArrayAccess $items
     * @param array $keys
     *
     * @return array|ArrayAccess
     */
    function array_rename($items, array $keys)
    {
        if (! is_array($items) && ! $items instanceof \ArrayAccess) {
            return $items;
        }

        foreach ($items as $k => &$v) {
            if (! isset($keys[$k])) {
                continue;
            }

            $items[$keys[$k]] = $v;
            unset($items[$k]);
        }

        return $items;
    }
}

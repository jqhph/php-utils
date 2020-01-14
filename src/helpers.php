<?php

if (! function_exists('array_chunk_process')) {
    /**
     * 数组分块处理.
     *
     * @param array    $items
     * @param int      $len
     * @param callable $callback
     */
    function array_chunk_process(array $items, int $len, callable $callback)
    {
        $count = count($items);

        if ($count <= $len) {
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

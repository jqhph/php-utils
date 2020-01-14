<?php

namespace Dcat\Utils\Backtrace;

class BacktraceFormatter
{
    /**
     * @var array
     */
    protected $traces = [];

    public function __construct(array $traces)
    {
        $this->traces = $traces;
    }

    /**
     * 转化成字符串.
     *
     * @return string
     */
    public function format(int $start = 0, int $maxLen = null)
    {
        $string = '';

        foreach (array_slice($this->traces, $start, $maxLen) as $i => &$item) {
            $string .= $this->formatRow($i, $item);
        }

        return $string;
    }

    /**
     * @param int   $num
     * @param array $trace
     *
     * @return string
     */
    protected function formatRow(int $num, array $trace)
    {
        $file = '';
        if (! empty($trace['file'])) {
            $file = "{$trace['file']}({$trace['line']}): ";
        }

        $call = $this->formatCallString($trace);
        $args = $this->formatArgs(isset($trace['args']) ? $trace['args'] : []);

        return "#{$num} {$file}{$call}{$args}\n";
    }

    /**
     * @param array $trace
     *
     * @return string
     */
    protected function formatCallString(array $trace)
    {
        if (! empty($trace['class'])) {
            return "{$trace['class']}{$trace['type']}{$trace['function']}";
        }

        return "{$trace['function']}";
    }

    /**
     * @param array $args
     *
     * @return string
     */
    protected function formatArgs(array $args)
    {
        if (! $args) {
            return '()';
        }

        $result = [];
        foreach ($args as $k => $v) {
            if (is_string($v)) {
                if (class_exists($v)) {
                    $result[] = "Object($v)";
                    continue;
                }
                if (mb_strlen($v) > 20) {
                    $v = mb_substr($v, 0, 20).'...';
                }
                $result[] = '"'.$v.'"';
                continue;
            }
            if (is_object($v)) {
                $v = get_class($v);
                $result[] = "Object($v)";
            }

            if (is_array($v)) {
                $count = count($v);
                $result[] = "Array($count)";
                continue;
            }
            $result[] = $v;
        }

        return '('.implode(', ', $result).')';
    }

    /**
     * @param array    $traces
     * @param int      $start
     * @param int|null $maxLen
     *
     * @return string
     */
    public static function string(array $traces, int $start = 0, int $maxLen = null)
    {
        return (new static($traces))->format($start, $maxLen);
    }
}

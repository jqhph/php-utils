<?php

namespace Dcat\Utils\Collection;

use Illuminate\Support\Collection;

class CollectionExtension
{
    /**
     * key重命名.
     */
    public static function rename()
    {
        Collection::macro('rename', function (array $keys) {
            if (array_has_string_key($this->toArray())) {
                $this->items = array_rename($this->items, $keys);
            } else {
                foreach ($this->items as $key => &$item) {
                    $item = array_rename($item, $keys);
                }
            }

            return $this;
        });
    }

    public static function duplicates()
    {
        // 获取所有重复的元素
        Collection::macro('allDuplicates', function ($callback, $strict = false) {
            /* @var Collection $items */
            $items = $this->map($this->valueRetriever($callback));

            $uniqueItems = $items->unique(null, $strict);

            $compare = $this->duplicateComparator($strict);

            $duplicates = [];

            $firstDuplicates = [];

            foreach ($items as $key => $value) {
                if ($uniqueItems->isNotEmpty() && $compare($value, $uniqueItems->first())) {
                    $uniqueItems->shift();

                    $firstDuplicates[$key] = $value;
                } else {
                    foreach ($firstDuplicates as $k => $v) {
                        if (! isset($duplicates[$k]) && $compare($v, $value)) {
                            $duplicates[$k] = $v;

                            unset($firstDuplicates[$k]);
                        }
                    }

                    $duplicates[$key] = $value;
                }
            }

            return $this->filter(function ($v, $k) use (&$duplicates) {
                return isset($duplicates[$k]);
            });
        });

        // 移除所有重复的项
        Collection::macro('rejectAllDuplicates', function ($callback, $strict = false) {
            /* @var Collection $this */
            /* @var Collection $duplicates */
            $duplicates = $this->allDuplicates($callback, $strict);
            if ($duplicates->isEmpty()) {
                return $this;
            }

            return $this->filter(function ($item, $k) use ($duplicates) {
                return ! $duplicates->has($k);
            });
        });

        // 移除重复的项，只保留第一个
        Collection::macro('rejectDuplicates', function ($callback, $strict = false) {
            /* @var Collection $this */
            /* @var Collection $duplicates */
            $duplicates = $this->allDuplicates($callback, $strict);

            if ($duplicates->isEmpty()) {
                return $this;
            }

            $duplicates->forget($duplicates->keys()->first());

            return $this->filter(function ($item, $k) use ($duplicates) {
                return ! $duplicates->has($k);
            });
        });
    }

    public static function rejectFirstOrLast()
    {
        // 移除第一个元素，与shift方法不同，此方法不会改变key
        Collection::macro('rejectFirst', function () {
            /* @var Collection $this */
            if ($this->isEmpty()) {
                return $this;
            }

            $results = new static($this->items);

            return $results->forget($this->keys()->first());
        });

        // 移除最后一个元素，与pop方法不同，此方法不会改变key
        Collection::macro('rejectLast', function () {
            /* @var Collection $this */
            if ($this->isEmpty()) {
                return $this;
            }

            $results = new static($this->items);

            return $results->forget($this->keys()->last());
        });
    }

    public static function splitBy()
    {
        // 把元素分割为两半
        Collection::macro('splitBy', function ($callback) {
            $firstItem = $lastItems = [];

            /* @var Collection $this */
            if ($this->isEmpty()) {
                return [$firstItem, $lastItems];
            }

            $callback = $this->valueRetriever($callback);

            foreach ($this->items as $key => $item) {
                if (call_user_func($callback, $item, $key)) {
                    $firstItem[$key] = $item;
                } else {
                    $lastItems[$key] = $item;
                }
            }

            return [$firstItem, $lastItems];
        });
    }
}

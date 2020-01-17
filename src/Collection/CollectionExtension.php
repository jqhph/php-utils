<?php

namespace Dcat\Utils\Collection;

use Illuminate\Support\Collection;

class CollectionExtension
{
    /**
     * key重命名
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

    /**
     * 获取所有重复的元素
     */
    public static function allDuplicates()
    {
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

            return $this->filter(function ($v, $k) use(&$duplicates) {
                return isset($duplicates[$k]);
            });
        });
    }
}

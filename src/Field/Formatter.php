<?php

namespace Dcat\Utils\Field;

class Formatter
{
    /**
     * @param array $row
     * @param $field
     * @param bool $nullable
     *
     * @return string|null
     */
    public static function formatStringField(array $row, string $field, bool $nullable)
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
    public static function formatArrayField(array $row, string $field, bool $nullable)
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
    public static function formatIntField(array $row, string $field, bool $nullable)
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
    public static function formatBoolField(array $row, string $field, bool $nullable)
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
    public static function formatFloadField(array $row, string $field, bool $nullable)
    {
        if ($nullable) {
            return isset($row[$field]) ? (float) $row[$field] : null;
        }

        return (float) (isset($row[$field]) ? $row[$field] : 0);
    }
}

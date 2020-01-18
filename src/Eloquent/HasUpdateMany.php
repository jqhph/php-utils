<?php

namespace Dcat\Utils\Eloquent;

use Exception;
use Illuminate\Support\Facades\DB;

trait HasUpdateMany
{
    /**
     * 批量更新操作.
     *
     * UPDATE table SET
     *     columns1 = CASE id
     *         WHEN 1 THEN 3
     *         WHEN 2 THEN 4
     *         WHEN 3 THEN 5
     * END,
     *     columns2 = CASE id
     *         WHEN 1 THEN 'New Title 1'
     *         WHEN 2 THEN 'New Title 2'
     *         WHEN 3 THEN 'New Title 3'
     * END
     *     WHERE id IN (1,2,3)
     *
     * 示例：根据goods_code和has_image修改 has_image,created_at,updated_at,spec等数据内容
     *
     * $this->updateMany(
     *     [
     *         ['goods_code'=>1000001,'has_image'=>1,'created_at'=>1552039739,'updated_at'=>$time_t,'spec'=>'2.5克×18粒'],
     *         ['goods_code'=>1000002,'has_image'=>1,'created_at'=>1552039737,'updated_at'=>$time_t,'spec'=>'8粒']
     *     ],
     *     ['goods_code', 'has_image']
     * );
     *
     * // 使用sql模板方式
     * $this->updateMany(
     *     [
     *         ['goods_code'=>1000001,'has_image'=>1,'created_at'=>1552039739,'updated_at'=>$time_t,'spec'=>'2.5克×18粒'],
     *         ['goods_code'=>1000002,'has_image'=>1,'created_at'=>1552039737,'updated_at'=>$time_t,'spec'=>'8粒']
     *     ],
     *     "goods_code IN(:goods_code) AND (has_image = ? OR state = ?)",
     *     [
     *         'bindings' => [1, 1]
     *     ]
     * );
     *
     *
     * @param array $rows 需要更新的数组
     * @param array|string $condition 支持直接传字段，sql模板（字段存在多个值的不支持OR语句）。
     *        e.g:
     *          支持   id IN(:id) AND name IN(:name) AND (state = ? OR is_new = ?)
     *          不支持 id IN(:id) OR name IN(:name)
     *
     * @param array $options 配置数组
     *      [
     *          [table] {string} 指定表名
     *          [bindings] {array} 待绑定到模板上的数据，注意这里只能绑定模板最末尾的字段 [1, 2, 3]
     *      ]
     *
     * @return int
     * @throws Exception
     */
    public function updateMany($rows, $condition = 'id', array $options = [])
    {
        $rows = convert_to_array($rows);
        if (! $rows) {
            throw new \InvalidArgumentException('批量修改数组不能为空');
        }

        // 指定连接名称
        $query = DB::connection($this->getConnectionName());

        // 表名
        $tableName = $options['table'] ?? null;
        $tableName = $tableName ?: ($query->getTablePrefix().$this->getTable());

        // 待绑定到条件模板上的数据
        $conditionBindings = $options['bindings'] ?? [];

        $firstRow = current($rows);

        // 更新条件字段数组
        [$conditionColumns, $conditionWhereTemplate] = $this->prepareConditionColumns($firstRow, $condition);

        // 需要更新的字段
        $updateColumns = array_filter(array_keys($firstRow), function ($column) use ($conditionColumns) {
            // 排除条件字段
            return ! in_array($column, $conditionColumns);
        });

        // 拼接sql语句
        $updateSql = "UPDATE {$tableName} SET ";

        $sets = [];
        $bindings = [];

        foreach ($updateColumns as $column) {
            $setSql = "`{$column}` = CASE ";

            foreach ($rows as $data) {
                $andConditions = [];
                foreach ($conditionColumns as $conditionColumn) {
                    $andConditions[] = "`{$conditionColumn}` = ?";

                    $bindings[] = $data[$conditionColumn] ?? null;
                }
                $setSql .= 'WHEN '.implode(' AND ', $andConditions).' THEN ? ';

                $bindings[] = $data[$column] ?? null;
            }

            $setSql .= "ELSE `{$column}` END ";
            $sets[] = $setSql;
        }

        // 构建whereIn条件值数组
        [$whereBindings, $whereSql] = $this->buildUpdateWhereSql($rows, $conditionColumns, $conditionWhereTemplate);

        $bindings = array_merge($bindings, $whereBindings, $conditionBindings);

        $updateSql .= implode(', ', $sets).' WHERE '.$whereSql;

        // 传入预处理sql语句和对应绑定数据
        return $query->update($updateSql, $bindings);
    }

    /**
     * 构建where sql语句.
     *
     * @param array       $rows
     * @param array       $conditionColumns
     * @param string|null $conditionWhereTemplate
     *
     * @return array
     */
    protected function buildUpdateWhereSql(array &$rows, array $conditionColumns, ?string $conditionWhereTemplate)
    {
        // 构建条件值数组
        $values = $this->prepareConditionValues($rows, $conditionColumns);

        // 有模板，直接返回sql模板
        if (! empty($conditionWhereTemplate)) {
            $values->each(function ($values, $column) use (&$conditionWhereTemplate) {
                $values = rtrim(str_repeat('?,', count($values)), ',');

                // 把 :column 替换成 ?,?,?,? 形式
                $conditionWhereTemplate = str_replace(':'.$column, $values, $conditionWhereTemplate);
            });

            return [$values->flatten()->toArray(), $conditionWhereTemplate];
        }

        // 没有模板
        // 所有条件用 WHERE IN(...) AND ... 的方式组合
        $where = [];
        $values->each(function ($values, $column) use (&$where, &$updateSql, $conditionColumns) {
            $values = rtrim(str_repeat('?,', count($values)), ',');

            $where[] = "`{$column}` IN ({$values})";
        });

        return [$values->flatten()->toArray(), implode(' AND ', $where)];
    }

    /**
     * 条件查询的值
     *
     * @param array $rows
     * @param array $conditionColumns
     *
     * @return \Illuminate\Support\Collection
     */
    protected function prepareConditionValues(array &$rows, array $conditionColumns)
    {
        $values = [];
        foreach ($rows as &$row) {
            foreach ($conditionColumns as $column) {
                if (! isset($values[$column])) {
                    $values[$column] = [];
                }

                $values[$column][] = $row[$column];
            }
        }

        return collect($values)->map(function ($v) {
            // 去重
            return array_unique($v);
        });
    }

    /**
     * @param array $firstRow
     * @param $condition
     *
     * @return array [$columns, $template]
     */
    protected function prepareConditionColumns(array $firstRow, $condition)
    {
        if (is_string($condition) && strpos($condition, ':') !== false) {
            // 有SQL模板
            preg_match_all('/:{1}([\w_-]+)/u', $condition, $matches);

            return [$matches[1], $condition];
        }

        // 纯字段
        $columns = convert_to_array($condition);

        foreach ($columns as $column) {
            if (! array_key_exists($column, $firstRow)) {
                throw new \RuntimeException("批量修改失败，不存在条件字段：$column");
            }
        }

        return [$columns, null];
    }
}

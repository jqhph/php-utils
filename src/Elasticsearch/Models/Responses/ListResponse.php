<?php

namespace Dcat\Utils\Elasticsearch\Models\Responses;

class ListResponse extends Response
{
    /**
     * @var array
     */
    protected $sources;

    /**
     * @var array
     */
    protected $aggregations = [];

    /**
     * @var array
     */
    protected $ids;

    /**
     * @var int
     */
    protected $total = 0;

    public function toArray(): array
    {
        $this->parse();

        return $this->sources;
    }

    public function getTotal()
    {
        $this->parse();

        return $this->total;
    }

    /**
     * @param string|null $key
     * @return array
     */
    public function getBuckets(string $key = null)
    {
        $this->parse();

        if ($key === null) {
            return $this->aggregations;
        }
        return $this->aggregations[$key] ?? [];
    }

    /**
     * 获取id数组
     *
     * @return array
     */
    public function getAllId(): array
    {
        $this->parse();

        return $this->ids;
    }

    /**
     * 获取指定字段数组
     *
     * @param string $field
     * @return array
     */
    public function column(string $field)
    {
        $this->parse();

        $columns = [];
        foreach ($this->sources as &$source) {
            if (isset($source[$field])) {
                $columns[] = $columns;
            }
        }
        return $columns;
    }

    protected function parse()
    {
        if ($this->sources !== null) return;

        $this->sources = $this->ids = [];
        foreach (($this->content['hits']['hits'] ?? []) as &$hit) {
            $this->ids[] = $hit['_source']['_id'] = $hit['_id'];

            $this->sources[] = $hit['_source'];
        }

        foreach (($this->content['aggregations'] ?? []) as $key => &$val) {
            $this->aggregations[$key] = $val['buckets'];
        }

        $this->total = $this->content['hits']['total'] ?? [];
        $this->total = $this->total['value'] ?? 0;
    }
}

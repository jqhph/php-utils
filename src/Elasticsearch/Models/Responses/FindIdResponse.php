<?php

namespace Dcat\Utils\Elasticsearch\Models\Responses;

use Dcat\Utils\Elasticsearch\Models\ElasticsearchModel;

class FindIdResponse extends Response
{
    protected $model;

    public function __construct(array $content, ElasticsearchModel $model)
    {
        parent::__construct($content);

        $this->model = $model;
    }

    /**
     * 判断是否查找成功
     *
     * @return bool
     */
    public function found()
    {
        $found = $this->content['found'] ?? false;

        return $found === true;
    }

    /**
     * @return ElasticsearchModel
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * 转化为数组.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->found() ? $this->content['_source'] : [];
    }

    /**
     * 转化为json.
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}

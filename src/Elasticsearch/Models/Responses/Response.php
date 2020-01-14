<?php

namespace Dcat\Utils\Elasticsearch\Models\Responses;

/**
 * Created by PhpStorm.
 * User: jqh
 * Date: 18-8-31
 * Time: 上午10:18.
 */
abstract class Response
{
    /**
     * @var array
     */
    protected $content = [];

    public function __construct(array $content)
    {
        $this->content = $content;
    }

    /**
     * @return array
     */
    public function getContent(): array
    {
        return $this->content;
    }
}

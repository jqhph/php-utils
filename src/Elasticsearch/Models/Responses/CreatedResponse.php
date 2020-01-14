<?php

namespace Dcat\Utils\Elasticsearch\Models\Responses;

class CreatedResponse extends Response
{
    /**
     * 获取新增id
     *
     * @return string
     */
    public function getId()
    {
        return $this->content['_id'] ?? '';
    }

    /**
     * 判断是否成功
     *
     * @return bool
     */
    public function isSuccessful()
    {
        $created = $this->content['result'] ?? false;

        return $created === 'created' || $created === 'updated';
    }
}

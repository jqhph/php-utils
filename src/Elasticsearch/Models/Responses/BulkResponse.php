<?php

namespace Dcat\Utils\Elasticsearch\Models\Responses;

class BulkResponse extends Response
{
    // {"took":9,"errors":false,"items":[{"index":{"_index":"yxb_message","_type":"msg","_id":"AWWN0gIU7Nf83CZJnSfI","_version":1,"result":"created","_shards":{"total":2,"successful":1,"failed":0},"created":true,"status":201}},{"index":{"_index":"yxb_message","_type":"msg","_id":"AWWN0gIU7Nf83CZJnSfJ","_version":1,"result":"created","_shards":{"total":2,"successful":1,"failed":0},"created":true,"status":201}}]}

    /**
     * 获取添加成功的数量
     *
     * @return int
     */
    public function getAffected()
    {
        $num = 0;

        foreach ($this->getItems() as &$item) {
            $result = $item['index']['result'] ?? null;

            if ($result) {
                $num++;
            }
        }
        return $num;
    }

    /**
     * 判断是否全部成功
     *
     * @return bool
     */
    public function isSuccessful()
    {
        $r = $this->content['errors'] ?? false;

        return $r ? false : true;
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        return $this->content['items'] ?? [];
    }

    /**
     * 获取所有新增成功的id
     *
     * @return array
     */
    public function getAllId(): array
    {
        $ids = [];

        foreach ($this->getItems() as &$item) {
            $result = $item['index']['result'] ?? null;

            if ($result) {
                $ids[] = $item['index']['_id'];
            }
        }
        return $ids;

    }

}

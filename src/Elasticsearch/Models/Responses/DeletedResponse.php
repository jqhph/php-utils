<?php

namespace Dcat\Utils\Elasticsearch\Models\Responses;

class DeletedResponse extends Response
{
    // {"found":true,"_index":"yxb_message","_type":"msg","_id":"AWWN0gIU7Nf83CZJnSfJ","_version":9,"result":"deleted","_shards":{"total":2,"successful":1,"failed":0}}

    /**
     * @return bool
     */
    public function isSuccessful()
    {
        return ($this->content['result'] ?? '') == 'deleted' ? true : false;
    }

}

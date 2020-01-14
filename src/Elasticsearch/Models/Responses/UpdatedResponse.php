<?php

namespace Dcat\Utils\Elasticsearch\Models\Responses;

class UpdatedResponse extends Response
{
    /**
     * 获取修改成功数
     *
     * @return int
     */
    public function getAffected()
    {
        if (!empty($this->content['result'])) {
            // 单条update
            return $this->content['result'] == 'updated' ? 1 : 0;
        }

        $num = 0;
        foreach ($this->content['items'] ?? [] as &$item) {
            if ($item['update']['result'] == 'updated') {
                $num++;
            }
        }

        return $num;
    }

    /**
     * 判断是否成功
     *
     * @return bool
     */
    public function isSuccessful()
    {
        $r = $this->content['result'] ?? false;

        return $r === 'updated';
    }
}

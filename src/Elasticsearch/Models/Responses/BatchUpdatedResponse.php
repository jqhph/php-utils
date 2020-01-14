<?php

namespace Dcat\Utils\Elasticsearch\Models\Responses;

class BatchUpdatedResponse extends Response
{
    /**
     * @return bool
     */
    public function isSuccessful()
    {
        if (isset($this->content['updated'])) {
            return ($this->content['updated'] ?? '') ? true : false;
        }

        return ($this->content['errors'] ?? '') ? false : true;
    }

    public function getAffected()
    {
        if (isset($this->content['items'])) {
            $num = 0;
            foreach ($this->content['items'] as $item) {
                $r = $item['update']['result'] ?? null;
                if ($r) {
                    $num++;
                }
            }

            return $num;
        }

        return $this->content['updated'] ?? 0;
    }
}

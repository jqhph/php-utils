<?php

namespace Dcat\Utils\Elasticsearch\Models;

use Dcat\Utils\Elasticsearch\BuilderFactory;
use Dcat\Utils\Elasticsearch\Models\Responses\BatchUpdatedResponse;
use Dcat\Utils\Elasticsearch\Models\Responses\BulkResponse;
use Dcat\Utils\Elasticsearch\Models\Responses\CreatedResponse;
use Dcat\Utils\Elasticsearch\Models\Responses\DeletedResponse;
use Dcat\Utils\Elasticsearch\Models\Responses\ListResponse;
use Dcat\Utils\Elasticsearch\Models\Responses\UpdatedResponse;
use Elasticsearch\Client;

/**
 * ES模型基类（支持ES7.0或以上版本）.
 *
 * Created by PhpStorm.
 * User: jqh
 * Date: 18-8-30
 * Time: 下午4:48
 *
 * @mixin Query
 *
 * @method static CreatedResponse insert(array $attributes, ?string $index = null)
 * @method static BulkResponse batchInsert(array $rows, ?string $index = null)
 * @method static DeletedResponse deleteById($id, ?string $index = null)
 * @method static DeletedResponse deleteByIds(array $ids, ?string $index = null)
 * @method static DeletedResponse deleteByQuery(array $condition, ?string $index = null)
 * @method static array findById($id, $fields = null, array $options = [], ?string $index = null)
 * @method static array first(array $condition, $fields = null, $sort = null, array $options = [], ?string $index = null)
 * @method static ListResponse find(array $condition, $fields = null, $sort = [], ?int $from = null, ?int $size = null, ?array $opts = [], ?string $index = null)
 * @method static ListResponse all($fields = null, $sort = [], ?int $from = null, ?int $size = null, ?array $opts = [], ?string $index = null)
 * @method static ListResponse aggregate(array $aggs, array $condition = [], $fields  = null, $sort = null, $from = null, $size = null, $index = null)
 * @method static int count(array $condition = null, $index = null)
 * @method static BatchUpdatedResponse updateByQuery(array $attributes, array $condition, $index = null)
 * @method static BatchUpdatedResponse batchUpdate(array $rows, $index = null)
 * @method static BatchUpdatedResponse updateByIds($attributes, array $ids, $index = null)
 * @method static UpdatedResponse updateById(array $attributes, $id, $index = null)
 * @method static UpdatedResponse appendToEmbeddedByIds(array $ids, string $column, array $content, $index = null)
 */
class ElasticsearchModel
{
    /**
     * 索引名称（如果索引定义了别名，此处也可以使用别名代替）.
     *
     * @var string
     */
    protected $index;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var Client
     */
    protected $client;

    /**
     * 获取索引名称.
     *
     * @return string
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param string $index
     *
     * @return void
     */
    public function setIndex(string $index)
    {
        $this->index = $index;
    }

    /**
     * @param string $type
     *
     * @return void
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    public static function query()
    {
        return static::make()->newQuery();
    }

    /**
     * @return Query
     */
    public function newQuery()
    {
        return new Query($this);
    }

    /**
     * @param mixed $client
     */
    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    /**
     * 获取ES客户端.
     *
     * @return Client
     */
    public function getClient()
    {
        return $this->client ?: ($this->client = BuilderFactory::getClient());
    }

    /**
     * @param mixed ...$params
     *
     * @return $this
     */
    public static function make(...$params)
    {
        return new static(...$params);
    }

    public function __call($method, array $args = [])
    {
        return $this->newQuery()->$method(...$args);
    }

    public static function __callStatic($method, array $args = [])
    {
        return (new static())->newQuery()->$method(...$args);
    }
}

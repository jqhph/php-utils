<?php

namespace Dcat\Utils\Elasticsearch\Models;

use Dcat\Utils\Elasticsearch\BuilderFactory;
use Elasticsearch\Client;

/**
 * 索引定义.
 *
 * Class Index
 */
abstract class ElasticsearchIndex
{
    const TYPE_STRING = 'string'; // string类型在ElasticSearch 旧版本中使用较多，从ElasticSearch 5.x开始不再支持string，由text和keyword类型替代。
    const TYPE_TEXT = 'text'; // 当一个字段是要被全文搜索的，比如Email内容、产品描述，应该使用text类型。设置text类型以后，字段内容会被分析，在生成倒排索引以前，字符串会被分析器分成一个一个词项。text类型的字段不用于排序，很少用于聚合。
    const TYPE_KEYWORD = 'keyword'; // keyword类型适用于索引结构化的字段，比如email地址、主机名、状态码和标签。如果字段需要进行过滤(比如查找已发布博客中status属性为published的文章)、排序、聚合。keyword类型的字段只能通过精确值搜索到。

    const TYPE_LONG = 'long'; // -2^63~2^63-1
    const TYPE_INTEGER = 'integer'; // -2^31~2^31-1
    const TYPE_SHORT = 'short'; // -32768~32767
    const TYPE_BYTE = 'byte'; // -128~127

    const TYPE_DOUBLE = 'double'; // 64位双精度IEEE 754浮点类型
    const TYPE_FLOAT = 'float'; // 32位单精度IEEE 754浮点类型
    const TYPE_HALF_FLOAT = 'half_float'; // 16位半精度IEEE 754浮点类型
    const TYPE_SCALED_FLOAT = 'scaled_float'; // 缩放类型的的浮点数

    const TYPE_BOOLEAN = 'boolean'; // 逻辑类型（布尔类型）可以接受true/false/”true”/”false”值
    const TYPE_RANGE = 'range';
    const TYPE_BINARY = 'binary'; // 二进制字段是指用base64来表示索引中存储的二进制数据，可用来存储二进制形式的数据，例如图像。默认情况下，该类型的字段只存储不索引。二进制类型只支持index_name属性。

    const TYPE_DATE = 'date';

//    const TYPE_ARRAY = 'array'; // 在同一个数组中，数组元素的数据类型是相同的，ElasticSearch不支持元素为多个数据类型，7.0版本已废弃
    const TYPE_OBJECT = 'object';
    const TYPE_NESTED = 'nested';

    const TYPE_GEO_POINT = 'geo_point';
    const TYPE_GEO_SHAPE = 'geo_shape';
    const TYPE_IP = 'ip';
    const TYPE_COMPLETION = 'completion'; // 范围类型
    const TYPE_ATTACHMENT = 'attachment';
    const TYPE_PERCOLATOR = 'percolator';
    const TYPE_TOKEN_COUNT = 'token_count';

    // 日期时间格式化
    const FORMAT_DATETIME = 'YYYY-MM-dd HH:mm:ss';
    const FORMAT_DATE = 'YYYY-MM-dd';

    /**
     * 保存所有字段.
     *
     * @var array
     */
    protected static $fields = [];

    /**
     * 索引名称.
     *
     * @var string
     */
    const NAME = null;

    /**
     * 索引别名，不能与索引名称相同.
     *
     * @var array
     */
    public static $alias = [];

    /**
     * @var Client
     */
    protected $client;

    /**
     * 索引分片设置.
     *
     * @var array
     */
    protected static $setting = [
        'max_result_window' => 2000000, // 最多能翻到200万条数据

        // 分片应根据实际情况设置，不宜设置过多或过少
        // 每个分片最大存储空间推荐为30G，所以此处默认开启2个分片即可
        'number_of_shards' => 2,
        // 备份分片
        'number_of_replicas' => 1,

        // 分片刷新时间
        'refresh_interval' => '2s',

        'search.slowlog.threshold.query.warn' => '5s', // 超过5秒的query产生1个warn日志
        'search.slowlog.threshold.query.info' => '1s', // 超过1秒的query产生1个info日志

        'search.slowlog.threshold.fetch.warn' => '1s',
        'search.slowlog.threshold.fetch.info' => '800ms',

        'indexing.slowlog.threshold.index.warn' => '5s', // 索引数据超过5秒产生一个warn日志
        'indexing.slowlog.threshold.index.info' => '1s',

        //        'threadpool.index.queue_size' => 80000,
        //        'threadpool.bulk.queue_size' => 10000,

    ];

    /**
     * 索引详情.
     *
     * @var array
     */
    protected static $mapping = [

    ];

    public function __construct(Client $client = null)
    {
        $this->client = $client ?: BuilderFactory::getClient();
    }

    /**
     * 创建索引.
     *
     * @return array
     */
    public function create()
    {
        $result = $this->client->indices()->create([
            'index' => static::NAME,
            'body' => [
                'settings' => static::$setting,
                'mappings' => static::$mapping,
            ],
        ]);

        if ($result) {
            $this->putAlias();
        }

        return $result;
    }

    public function putAlias()
    {
        if (static::$alias) {
            foreach (static::$alias as $alias) {
                $this->client->indices()->putAlias([
                    'index' => static::NAME,
                    'name'  => $alias,
                ]);
            }
        }
    }

    /**
     * 删除索引.
     *
     * @return array
     */
    public function delete()
    {
        $result = $this->client->indices()->delete([
            'index' => static::NAME,
        ]);

        return $result;
    }

    /**
     * 获取所有字段.
     *
     * @return array
     */
    public static function fields()
    {
        if (isset(static::$fields[static::class])) {
            return static::$fields[static::class];
        }

        return static::$fields[static::class] = array_keys(static::$mapping['properties'] ?? []);
    }

    public static function make(...$params)
    {
        return new static(...$params);
    }
}

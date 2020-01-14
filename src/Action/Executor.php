<?php

namespace Dcat\Utils\Action;

use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Arr;

/**
 * 动作执行器.
 */
class Executor
{
    /**
     * @var Action
     */
    protected $action;

    /**
     * 用户输入数据.
     *
     * @var array
     */
    protected $input = [];

    /**
     * 公共中间件.
     *
     * @var array
     */
    protected static $middlewares = [];

    /**
     * 分组后的中间件数组.
     *
     * @var array
     */
    protected static $middlewareGroup = [];

    public function __construct(ActionInterface $action, array $input = [])
    {
        $this->action = $action;
        $this->input = $input;
    }

    /**
     * 执行动作.
     *
     * @param \Closure $handler
     * @return mixed
     */
    public function handle(\Closure $handler)
    {
        $this->action->callExecuting($this->input);

        // 执行动作
        $value = $this->executeAction($handler);

        $this->action->callExecuted($this->input, $value);

        return $value;
    }

    /**
     * 执行动作.
     *
     * @param \Closure $handler
     * @return mixed
     */
    protected function executeAction(\Closure $handler)
    {
        $middlewares = $this->mergeMiddlewares();

        if (! $middlewares) {
            // 没有设置中间件则直接处理消息
            return $handler($this->input);
        }

        /* @var Pipeline $pipeline */
        $pipeline = app(Pipeline::class);

        return $pipeline->through($middlewares)
            ->via('handle')
            ->send([$this->action, $this->input])
            ->then(function ($data) use ($handler) {
                return $handler((array) ($data[1] ?? []));
            });
    }

    /**
     * 注册公共中间件.
     *
     * @param  array|string  $middleware
     * @param  array   $options
     * @return ActionMiddlewareOptions
     */
    public static function middleware($middleware, array $options = [])
    {
        foreach ((array) $middleware as $m) {
            static::$middlewares[] = [
                'middleware' => $m,
                'options' => &$options,
            ];
        }

        return new ActionMiddlewareOptions($options);
    }

    /**
     * 合并公共中间件.
     *
     * @return array
     */
    protected function mergeMiddlewares()
    {
        if (! $group = $this->generateMiddlewareGroup()) {
            return $this->action->middlewares();
        }

        return array_merge($group[0], $this->action->middlewares(), $group[1]);
    }

    /**
     * 生成中间件分组.
     *
     * @return array
     */
    protected function generateMiddlewareGroup()
    {
        if (static::$middlewareGroup) {
            return static::$middlewareGroup;
        }

        if (! static::$middlewares) {
            return [];
        }

        $map = function ($value) {
            return $value['middleware'];
        };

        static::$middlewareGroup[] = Arr::flatten(
            array_map(
                $map,
                array_filter(static::$middlewares, function ($value) {
                    return $value['options']['first'] ?? false;
                })
            )
        );

        static::$middlewareGroup[] = Arr::flatten(
            array_map(
                $map,
                array_filter(static::$middlewares, function ($value) {
                    return empty($value['options']['first']) && ($value['options']['latest'] ?? true);
                })
            )
        );

        return static::$middlewareGroup;
    }
}

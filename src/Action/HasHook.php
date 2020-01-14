<?php

namespace Dcat\Utils\Action;

trait HasHook
{
    /**
     * @var array
     */
    protected static $hooks = [
        'executing' => [],
        'executed'  => [],
    ];

    /**
     * 执行中.
     *
     * @param callable|string $handler
     * @return void
     */
    public static function executing($handler)
    {
        static::$hooks['executing'][] = $handler;
    }

    /**
     * 执行完毕.
     *
     * @param callable|string $handler
     * @return void
     */
    public static function executed($handler)
    {
        static::$hooks['executed'][] = $handler;
    }

    /**
     * 触发执行中钩子.
     *
     * @param array $input
     */
    public function callExecuting(array $input)
    {
        static::callHook('executing', $input);
    }

    /**
     * 触发执行完毕钩子.
     *
     * @param array $input 用户输入参数
     * @param mixed $result 动作执行结果
     */
    public function callExecuted(array $input, $result)
    {
        static::callHook('executed', $input, $result);
    }

    /**
     * 触发钩子.
     *
     * @param string $key
     * @param mixed ...$params
     */
    public static function callHook(string $key, ...$params)
    {
        $hooks = static::$hooks[$key] ?? null;

        if (! $hooks) {
            return;
        }

        foreach ($hooks as $hook) {
            if (! $hook = static::resolveHook($hook)) {
                continue;
            }

            if (call_user_func($hook, ...$params) === false) {
                return;
            }
        }
    }

    /**
     * @param mixed $hook
     * @return callable|void
     */
    protected static function resolveHook($hook)
    {
        if (! $hook) {
            return;
        }

        // 解析 Class 或 Class@method 形式
        if (is_string($hook)) {
            $hook = explode('@', $hook);

            $class = $hook[0];
            $method = ($hook[1] ?? '') ?: 'handle';

            if (! class_exists($class)) {
                return;
            }

            $hook = [app($hook), $method];
        }

        // 解析 对象
        if (! $hook instanceof \Closure && is_object($hook)) {
            if (! method_exists($hook, 'handle')) {
                return;
            }

            $hook = [$hook, 'handle'];
        }

        if (is_callable($hook)) {
            return $hook;
        }
    }
}

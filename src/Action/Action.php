<?php

namespace Dcat\Utils\Action;

use Illuminate\Support\Facades\Validator as ValidatorFactory;

/**
 * 动作类基类.
 */
abstract class Action implements ActionInterface
{
    use HasHook;

    /**
     * 中间件.
     *
     * @var array
     */
    protected $middlewares = [];

    /**
     * 验证器规则.
     *
     * @var array
     * @example {"data": "required|json"}
     */
    protected $rules = [];

    /**
     * @var bool
     */
    protected $withoutRules = false;

    /**
     * 验证器错误信息.
     *
     * @var array
     * @example {"data.required": "data字段是必须的"}
     */
    protected $messages = [];

    /**
     * @var array
     */
    protected $input = [];

    /**
     * 获取中间件.
     *
     * @return array
     */
    public function middlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * 返回验证器规则.
     *
     * @return array {"data": "required|json"}
     */
    protected function rules(): array
    {
        return $this->rules;
    }

    /**
     * 返回验证器错误信息.
     *
     * @return array {"data.required": "data字段是必须的"}
     */
    protected function messages(): array
    {
        return $this->messages;
    }

    /**
     * 在验证参数之前过滤用户输入参数.
     *
     * @param array $input
     * @return void
     */
    protected function prepareInput(array &$input): void
    {
    }

    /**
     * 验证用户输入参数.
     *
     * @param  array|null $input 用户输入数据
     * @return void
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate(array $input = null): void
    {
        $input = $input ?: $this->input;

        $validator = ValidatorFactory::make($input, $this->rules(), $this->messages());

        $validator->validate();
    }

    /**
     * 跳过验证
     *
     * @param array $input
     * @return $this
     */
    public function withoutValidation()
    {
        $this->withoutRules = true;

        return $this;
    }

    /**
     * 执行动作.
     *
     * @param array $input
     * @return mixed
     */
    final public function execute(array $input = [])
    {
        if (method_exists($this, 'init')) {
            $this->init($input);
        }

        $excutor = new Executor($this, $input);

        return $excutor->handle(function (array $input) {
            // 过滤用户参数
            $this->prepareInput($input);

            $this->input = $input;

            if (method_exists($this, 'validating')) {
                $this->validating($input);
            }

            // 验证参数
            if ($this->withoutRules === false) {
                $this->validate($input);
            }

            if (method_exists($this, 'validated')) {
                $this->validated($input);
            }

            // 执行动作逻辑
            return $this->process($input);
        });
    }

    /**
     * 执行动作逻辑.
     *
     * @param  array  $input
     * @return mixed
     */
    abstract protected function process(array $input = []);

    /**
     * 实例化当前动作类.
     *
     * @param  mixed  ...$parameters
     * @return static
     */
    public static function make(...$parameters)
    {
        return app(static::class, $parameters);
    }

    /**
     * 实例化当前动作类并执行.
     *
     * @param array $input
     * @return mixed
     */
    public static function makeAndExecute(array $input = [])
    {
        return static::make()->execute($input);
    }
}

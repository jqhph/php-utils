<?php

namespace Dcat\Utils\Action;

class ActionMiddlewareOptions
{
    /**
     * The middleware options.
     *
     * @var array
     */
    protected $options;

    /**
     * Create a new middleware option instance.
     *
     * @param  array  $options
     * @return void
     */
    public function __construct(array &$options)
    {
        $this->options = &$options;
    }

    /**
     * 首先执行.
     *
     * @return $this
     */
    public function prepend()
    {
        $this->options['first'] = true;

        return $this;
    }

    /**
     * 最后执行.
     *
     * @return $this
     */
    public function append()
    {
        $this->options['latest'] = true;

        return $this;
    }
}

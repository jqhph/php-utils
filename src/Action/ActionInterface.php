<?php

namespace Dcat\Utils\Action;

/**
 * 动作类接口.
 */
interface ActionInterface
{
    /**
     * 执行动作.
     *
     * @param array $options
     * @return mixed
     */
    public function execute(array $input = []);
}

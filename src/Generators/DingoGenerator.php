<?php

namespace Mpociot\ApiDoc\Generators;

class DingoGenerator extends AbstractGenerator
{
    /**
     * Prepares / Disables route middlewares.
     *
     * @param  bool $disable
     *
     * @return  void
     */
    public function prepareMiddleware($disable = true)
    {
        // Not needed by Dingo
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function callRoute($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $dispatcher = app('Dingo\Api\Dispatcher')->raw();

        collect($server)->map(function ($key, $value) use ($dispatcher) {
            $dispatcher->header($value, $key);
        });

        return call_user_func_array([$dispatcher, strtolower($method)], [$uri]);
    }
}

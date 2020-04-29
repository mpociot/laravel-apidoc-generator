<?php

namespace Mpociot\ApiDoc\Extracting\Strategies\ResponseParameters;

use Illuminate\Routing\Route;
use Mpociot\ApiDoc\Extracting\RouteDocBlocker;
use Mpociot\ApiDoc\Extracting\Strategies\Strategy;
use Mpociot\ApiDoc\Extracting\TransformerHelpers;
use Mpociot\Reflection\DocBlock;
use ReflectionClass;
use ReflectionMethod;

class GetFromTransformerParamTag extends Strategy
{
    use FromDocBlockHelper;
    use TransformerHelpers;

    public function __invoke(Route $route, ReflectionClass $controller, ReflectionMethod $method, array $routeRules, array $context = [])
    {
        $docBlocks = RouteDocBlocker::getDocBlocksFromRoute($route);
        /** @var DocBlock $methodDocBlock */
        $methodDocBlock = $docBlocks['method'];

        $tag = $this->getTransformerTag($methodDocBlock->getTags());

        if (empty($tag)) {
            return null;
        }

        [$statusCode, $transformer] = $this->getStatusCodeAndTransformerClass($tag);

        // Reflect the transformer
        $reflection = new ReflectionClass($transformer);
        $method = 'transform';

        if (!$reflection->hasMethod('transform')) {
            $method = '__invoke';
        }
        if (!$reflection->hasMethod($method)) {
            return null;
        }

        return $this->getResponseParametersFromDocBlock(
            (new DocBlock($reflection->getMethod($method)->getDocComment() ?: ''))->getTags()
        );
    }
}

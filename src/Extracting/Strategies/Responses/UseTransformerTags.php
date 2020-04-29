<?php

namespace Mpociot\ApiDoc\Extracting\Strategies\Responses;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Model as IlluminateModel;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use Mpociot\ApiDoc\Extracting\RouteDocBlocker;
use Mpociot\ApiDoc\Extracting\Strategies\Strategy;
use Mpociot\ApiDoc\Extracting\TransformerHelpers;
use Mpociot\ApiDoc\Tools\Flags;
use Mpociot\ApiDoc\Tools\Utils;
use Mpociot\Reflection\DocBlock;
use Mpociot\Reflection\DocBlock\Tag;
use ReflectionClass;
use ReflectionMethod;

/**
 * Parse a transformer response from the docblock ( @transformer || @transformercollection ).
 */
class UseTransformerTags extends Strategy
{
    use TransformerHelpers;

    /**
     * @param Route $route
     * @param ReflectionClass $controller
     * @param ReflectionMethod $method
     * @param array $rulesToApply
     * @param array $context
     *
     * @throws \Exception
     *
     * @return array|null
     */
    public function __invoke(Route $route, ReflectionClass $controller, ReflectionMethod $method, array $rulesToApply, array $context = [])
    {
        $docBlocks = RouteDocBlocker::getDocBlocksFromRoute($route);
        /** @var DocBlock $methodDocBlock */
        $methodDocBlock = $docBlocks['method'];

        return $this->getTransformerResponse($methodDocBlock->getTags(), $route);
    }

    /**
     * Get a response from the transformer tags.
     *
     * @param array $tags
     * @param Route $route
     *
     * @return array|null
     */
    protected function getTransformerResponse(array $tags, Route $route)
    {
        try {
            if (empty($transformerTag = $this->getTransformerTag($tags))) {
                return null;
            }

            [$statusCode, $transformer] = $this->getStatusCodeAndTransformerClass($transformerTag);
            $model = $this->getClassToBeTransformed($tags, (new ReflectionClass($transformer))->getMethod('transform'));
            $modelInstance = $this->instantiateTransformerModel($model);

            $fractal = new Manager();

            if (! is_null(config('apidoc.fractal.serializer'))) {
                $fractal->setSerializer(app(config('apidoc.fractal.serializer')));
            }

            $resource = (strtolower($transformerTag->getName()) == 'transformercollection')
                ? new Collection(
                    [$modelInstance, $this->instantiateTransformerModel($model)],
                    new $transformer()
                )
                : new Item($modelInstance, new $transformer());

            $response = response($fractal->createData($resource)->toJson());

            return [
                [
                    'status' => $statusCode ?: $response->getStatusCode(),
                    'content' => $response->getContent(),
                ],
            ];
        } catch (Exception $e) {
            echo 'Exception thrown when fetching transformer response for [' . implode(',', $route->methods) . "] {$route->uri}.\n";
            if (Flags::$shouldBeVerbose) {
                Utils::dumpException($e);
            } else {
                echo "Run this again with the --verbose flag to see the exception.\n";
            }

            return null;
        }
    }
}

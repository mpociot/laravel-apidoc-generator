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

    /**
     * @param Tag $tag
     *
     * @return array
     */
    private function getStatusCodeAndTransformerClass($tag): array
    {
        $content = $tag->getContent();
        preg_match('/^(\d{3})?\s?([\s\S]*)$/', $content, $result);
        $status = $result[1] ?: 200;
        $transformerClass = $result[2];

        return [$status, $transformerClass];
    }

    /**
     * @param array $tags
     * @param ReflectionMethod $transformerMethod
     *
     * @throws Exception
     *
     * @return string
     */
    private function getClassToBeTransformed(array $tags, ReflectionMethod $transformerMethod): string
    {
        $modelTag = Arr::first(array_filter($tags, function ($tag) {
            return ($tag instanceof Tag) && strtolower($tag->getName()) == 'transformermodel';
        }));

        $type = null;
        if ($modelTag) {
            $type = $modelTag->getContent();
        } else {
            $parameter = Arr::first($transformerMethod->getParameters());
            if ($parameter->hasType() && ! $parameter->getType()->isBuiltin() && class_exists($parameter->getType()->getName())) {
                // Ladies and gentlemen, we have a type!
                $type = $parameter->getType()->getName();
            }
        }

        if ($type == null) {
            throw new Exception('Failed to detect a transformer model. Please specify a model using @transformerModel.');
        }

        return $type;
    }

    /**
     * @param string $type
     *
     * @return Model|object
     */
    protected function instantiateTransformerModel(string $type)
    {
        try {
            // try Eloquent model factory

            // Factories are usually defined without the leading \ in the class name,
            // but the user might write it that way in a comment. Let's be safe.
            $type = ltrim($type, '\\');

            return factory($type)->make();
        } catch (Exception $e) {
            if (Flags::$shouldBeVerbose) {
                echo "Eloquent model factory failed to instantiate {$type}; trying to fetch from database.\n";
            }

            $instance = new $type();
            if ($instance instanceof IlluminateModel) {
                try {
                    // we can't use a factory but can try to get one from the database
                    $firstInstance = $type::first();
                    if ($firstInstance) {
                        return $firstInstance;
                    }
                } catch (Exception $e) {
                    // okay, we'll stick with `new`
                    if (Flags::$shouldBeVerbose) {
                        echo "Failed to fetch first {$type} from database; using `new` to instantiate.\n";
                    }
                }
            }
        }

        return $instance;
    }

    /**
     * @param array $tags
     *
     * @return Tag|null
     */
    private function getTransformerTag(array $tags)
    {
        $transformerTags = array_values(
            array_filter($tags, function ($tag) {
                return ($tag instanceof Tag) && in_array(strtolower($tag->getName()), ['transformer', 'transformercollection']);
            })
        );

        return Arr::first($transformerTags);
    }
}

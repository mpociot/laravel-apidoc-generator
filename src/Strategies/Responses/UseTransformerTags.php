<?php

namespace Mpociot\ApiDoc\Strategies\Responses;

use ReflectionClass;
use ReflectionMethod;
use Illuminate\Support\Arr;
use League\Fractal\Manager;
use Illuminate\Routing\Route;
use Mpociot\ApiDoc\Tools\Flags;
use Mpociot\Reflection\DocBlock;
use League\Fractal\Resource\Item;
use Mpociot\Reflection\DocBlock\Tag;
use League\Fractal\Resource\Collection;
use Mpociot\ApiDoc\Strategies\Strategy;
use Mpociot\ApiDoc\Tools\RouteDocBlocker;

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
    public function __invoke(Route $route, \ReflectionClass $controller, \ReflectionMethod $method, array $rulesToApply, array $context = [])
    {
        $docBlocks = RouteDocBlocker::getDocBlocksFromRoute($route);
        /** @var DocBlock $methodDocBlock */
        $methodDocBlock = $docBlocks['method'];

        return $this->getTransformerResponse($methodDocBlock->getTags());
    }

    /**
     * Get a response from the transformer tags.
     *
     * @param array $tags
     *
     * @return array|null
     */
    protected function getTransformerResponse(array $tags)
    {
        try {
            if (empty($transformerTag = $this->getTransformerTag($tags))) {
                return null;
            }

            list($statusCode, $transformer) = $this->getStatusCodeAmdTransformerClass($transformerTag);
            $model = $this->getClassToBeTransformed($tags, (new ReflectionClass($transformer))->getMethod('transform'));
            $modelInstance = $this->instantiateTransformerModel($model);

            $fractal = new Manager();

            if (! is_null(config('apidoc.fractal.serializer'))) {
                $fractal->setSerializer(app(config('apidoc.fractal.serializer')));
            }

            $resource = (strtolower($transformerTag->getName()) == 'transformercollection')
                ? new Collection([$modelInstance, $modelInstance], new $transformer)
                : new Item($modelInstance, new $transformer);

            return [$statusCode => response($fractal->createData($resource)->toJson())->getContent()];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param Tag $tag
     *
     * @return array
     */
    private function getStatusCodeAmdTransformerClass($tag): array
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
     * @return null|string
     */
    private function getClassToBeTransformed(array $tags, ReflectionMethod $transformerMethod)
    {
        $modelTag = Arr::first(array_filter($tags, function ($tag) {
            return ($tag instanceof Tag) && strtolower($tag->getName()) == 'transformermodel';
        }));

        $type = null;
        if ($modelTag) {
            $type = $modelTag->getContent();
        } else {
            $parameter = Arr::first($transformerMethod->getParameters());
            if ($parameter->hasType() && ! $parameter->getType()->isBuiltin() && class_exists((string) $parameter->getType())) {
                // ladies and gentlemen, we have a type!
                $type = (string) $parameter->getType();
            }
        }

        return $type;
    }

    /**
     * @param string $type
     *
     * @return mixed
     */
    protected function instantiateTransformerModel(string $type)
    {
        try {
            // try Eloquent model factory
            return factory($type)->make();
        } catch (\Exception $e) {
            if (Flags::$shouldBeVerbose) {
                echo "Eloquent model factory failed to instantiate {$type}; trying to fetch from database";
            }

            $instance = new $type;
            if ($instance instanceof \Illuminate\Database\Eloquent\Model) {
                try {
                    // we can't use a factory but can try to get one from the database
                    $firstInstance = $type::first();
                    if ($firstInstance) {
                        return $firstInstance;
                    }
                } catch (\Exception $e) {
                    // okay, we'll stick with `new`
                    if (Flags::$shouldBeVerbose) {
                        echo "Failed to fetch first {$type} from database; using `new` to instantiate";
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
        $transFormerTags = array_values(
            array_filter($tags, function ($tag) {
                return ($tag instanceof Tag) && in_array(strtolower($tag->getName()), ['transformer', 'transformercollection']);
            })
        );

        return Arr::first($transFormerTags);
    }
}

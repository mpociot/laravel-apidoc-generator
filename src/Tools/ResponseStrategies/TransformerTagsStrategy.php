<?php

namespace Mpociot\ApiDoc\Tools\ResponseStrategies;

use ReflectionClass;
use ReflectionMethod;
use League\Fractal\Manager;
use Illuminate\Routing\Route;
use League\Fractal\Resource\Item;
use Mpociot\Reflection\DocBlock\Tag;
use League\Fractal\Resource\Collection;

/**
 * Parse a transformer response from the docblock ( @transformer || @transformercollection ).
 */
class TransformerTagsStrategy
{
    /**
     * @param Route $route
     * @param array $tags
     * @param array $routeProps
     *
     * @return array|null
     */
    public function __invoke(Route $route, array $tags, array $routeProps)
    {
        return $this->getTransformerResponse($tags);
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
                return;
            }

            $transformer = $this->getTransformerClass($transformerTag);
            $model = $this->getClassToBeTransformed($tags, (new ReflectionClass($transformer))->getMethod('transform'));
            $modelInstance = $this->instantiateTransformerModel($model);

            $fractal = new Manager();

            if (! is_null(config('apidoc.fractal.serializer'))) {
                $fractal->setSerializer(app(config('apidoc.fractal.serializer')));
            }

            $resource = (strtolower($transformerTag->getName()) == 'transformercollection')
                ? new Collection([$modelInstance, $modelInstance], new $transformer)
                : new Item($modelInstance, new $transformer);

            return [response($fractal->createData($resource)->toJson())];
        } catch (\Exception $e) {
            return;
        }
    }

    /**
     * @param Tag $tag
     *
     * @return string|null
     */
    private function getTransformerClass($tag)
    {
        return $tag->getContent();
    }

    /**
     * @param array $tags
     * @param ReflectionMethod $transformerMethod
     *
     * @return null|string
     */
    private function getClassToBeTransformed(array $tags, ReflectionMethod $transformerMethod)
    {
        $modelTag = array_first(array_filter($tags, function ($tag) {
            return ($tag instanceof Tag) && strtolower($tag->getName()) == 'transformermodel';
        }));

        $type = null;
        if ($modelTag) {
            $type = $modelTag->getContent();
        } else {
            $parameter = array_first($transformerMethod->getParameters());
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

        return array_first($transFormerTags);
    }
}

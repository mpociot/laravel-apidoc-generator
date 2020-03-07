<?php

namespace Mpociot\ApiDoc\Extracting\Strategies\Responses;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use League\Fractal\Resource\Collection;
use Mpociot\ApiDoc\Extracting\RouteDocBlocker;
use Mpociot\ApiDoc\Extracting\Strategies\Strategy;
use Mpociot\ApiDoc\Tools\Flags;
use Mpociot\ApiDoc\Tools\Utils;
use Mpociot\Reflection\DocBlock;
use Mpociot\Reflection\DocBlock\Tag;
use ReflectionClass;
use ReflectionMethod;

/**
 * Parse an Eloquent API resource response from the docblock ( @apiResource || @apiResourcecollection ).
 */
class UseApiResourceTags extends Strategy
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

        return $this->getApiResourceResponse($methodDocBlock->getTags(), $route);
    }

    /**
     * Get a response from the transformer tags.
     *
     * @param array $tags
     *
     * @return array|null
     */
    protected function getApiResourceResponse(array $tags, Route $route)
    {
        try {
            if (empty($apiResourceTag = $this->getApiResourceTag($tags))) {
                return null;
            }

            list($statusCode, $apiResourceClass) = $this->getStatusCodeAndApiResourceClass($apiResourceTag);
            $model = $this->getClassToBeTransformed($tags);
            $modelInstance = $this->instantiateApiResourceModel($model);

            try {
                $resource = new $apiResourceClass($modelInstance);
            } catch (\Exception $e) {
                // If it is a ResourceCollection class, it might throw an error
                // when trying to instantiate with something other than a collection
                $resource = new $apiResourceClass(collect([$modelInstance]));
            }
            if (strtolower($apiResourceTag->getName()) == 'apiresourcecollection') {
                // Collections can either use the regular JsonResource class (via `::collection()`,
                // or a ResourceCollection (via `new`)
                // See https://laravel.com/docs/5.8/eloquent-resources
                $models = [$modelInstance, $this->instantiateApiResourceModel($model)];
                $resource = $resource instanceof ResourceCollection
                    ? new $apiResourceClass(collect($models))
                    : $apiResourceClass::collection(collect($models));
            }

            /** @var Response $response */
            $response = $resource->toResponse(app(Request::class));

            return [
                [
                    'status' => $statusCode ?: $response->getStatusCode(),
                    'content' => $response->getContent(),
                ],
            ];
        } catch (\Exception $e) {
            echo 'Exception thrown when fetching Eloquent API resource response for [' . implode(',', $route->methods) . "] {$route->uri}.\n";
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
    private function getStatusCodeAndApiResourceClass($tag): array
    {
        $content = $tag->getContent();
        preg_match('/^(\d{3})?\s?([\s\S]*)$/', $content, $result);
        $status = $result[1] ?: 0;
        $apiResourceClass = $result[2];

        return [$status, $apiResourceClass];
    }

    /**
     * @param array $tags
     *
     * @return string
     */
    private function getClassToBeTransformed(array $tags): string
    {
        $modelTag = Arr::first(array_filter($tags, function ($tag) {
            return ($tag instanceof Tag) && strtolower($tag->getName()) == 'apiresourcemodel';
        }));

        $type = $modelTag->getContent();

        if (empty($type)) {
            throw new Exception('Failed to detect an Eloquent API resource model. Please specify a model using @apiResourceModel.');
        }

        return $type;
    }

    /**
     * @param string $type
     *
     * @return Model|object
     */
    protected function instantiateApiResourceModel(string $type)
    {
        try {
            // Try Eloquent model factory

            // Factories are usually defined without the leading \ in the class name,
            // but the user might write it that way in a comment. Let's be safe.
            $type = ltrim($type, '\\');

            return factory($type)->make();
        } catch (\Exception $e) {
            if (Flags::$shouldBeVerbose) {
                echo "Eloquent model factory failed to instantiate {$type}; trying to fetch from database.\n";
            }

            $instance = new $type();
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
    private function getApiResourceTag(array $tags)
    {
        $apiResourceTags = array_values(
            array_filter($tags, function ($tag) {
                return ($tag instanceof Tag) && in_array(strtolower($tag->getName()), ['apiresource', 'apiresourcecollection']);
            })
        );

        return Arr::first($apiResourceTags);
    }
}

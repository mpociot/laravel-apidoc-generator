<?php

namespace Mpociot\ApiDoc\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Mpociot\ApiDoc\Extracting\Generator;
use Mpociot\ApiDoc\Matching\RouteMatcher\Match;
use Mpociot\ApiDoc\Matching\RouteMatcherInterface;
use Mpociot\ApiDoc\Tools\DocumentationConfig;
use Mpociot\ApiDoc\Tools\Flags;
use Mpociot\ApiDoc\Tools\Utils;
use Mpociot\ApiDoc\Writing\Writer;
use Mpociot\Reflection\DocBlock;
use ReflectionClass;
use ReflectionException;

class GenerateDocumentation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apidoc:generate
                            {--force : Force rewriting of existing routes}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate your API documentation from existing Laravel routes.';

    /**
     * @var DocumentationConfig
     */
    private $docConfig;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * Execute the console command.
     *
     * @param RouteMatcherInterface $routeMatcher
     *
     * @return void
     */
    public function handle(RouteMatcherInterface $routeMatcher)
    {
        // Using a global static variable here, so fuck off if you don't like it.
        // Also, the --verbose option is included with all Artisan commands.
        Flags::$shouldBeVerbose = $this->option('verbose');

        $this->docConfig = new DocumentationConfig(config('apidoc'));
        $this->baseUrl = $this->docConfig->get('base_url') ?? config('app.url');

        URL::forceRootUrl($this->baseUrl);

        $routes = $routeMatcher->getRoutes($this->docConfig->get('routes'), $this->docConfig->get('router'));

        $generator = new Generator($this->docConfig);
        $parsedRoutes = $this->processRoutes($generator, $routes);

        $groupedRoutes = collect($parsedRoutes)
            ->groupBy('metadata.groupName')
            ->sortBy(static function ($group) {
                /* @var $group Collection */
                return $group->first()['metadata']['groupName'];
            }, SORT_NATURAL);
        $writer = new Writer(
            $this,
            $this->docConfig,
            $this->option('force')
        );
        $writer->writeDocs($groupedRoutes);
    }

    /**
     * @param \Mpociot\ApiDoc\Extracting\Generator $generator
     * @param Match[] $routes
     *
     * @throws \ReflectionException
     *
     * @return array
     */
    private function processRoutes(Generator $generator, array $routes)
    {
        $parsedRoutes = [];
        foreach ($routes as $routeItem) {
            $route = $routeItem->getRoute();
            /** @var Route $route */
            $messageFormat = '%s route: [%s] %s';
            $routeMethods = implode(',', $generator->getMethods($route));
            $routePath = $generator->getUri($route);

            if ($this->isClosureRoute($route->getAction())) {
                $this->warn(sprintf($messageFormat, 'Skipping', $routeMethods, $routePath) . ': Closure routes are not supported.');
                continue;
            }

            $routeControllerAndMethod = Utils::getRouteClassAndMethodNames($route->getAction());
            if (! $this->isValidRoute($routeControllerAndMethod)) {
                $this->warn(sprintf($messageFormat, 'Skipping invalid', $routeMethods, $routePath));
                continue;
            }

            if (! $this->doesControllerMethodExist($routeControllerAndMethod)) {
                $this->warn(sprintf($messageFormat, 'Skipping', $routeMethods, $routePath) . ': Controller method does not exist.');
                continue;
            }

            if (! $this->isRouteVisibleForDocumentation($routeControllerAndMethod)) {
                $this->warn(sprintf($messageFormat, 'Skipping', $routeMethods, $routePath) . ': @hideFromAPIDocumentation was specified.');
                continue;
            }

            try {
                $parsedRoutes[] = $generator->processRoute($route, $routeItem->getRules());
                $this->info(sprintf($messageFormat, 'Processed', $routeMethods, $routePath));
            } catch (\Exception $exception) {
                $this->warn(sprintf($messageFormat, 'Skipping', $routeMethods, $routePath) . '- Exception ' . get_class($exception) . ' encountered : ' . $exception->getMessage());
            }
        }

        return $parsedRoutes;
    }

    /**
     * @param array $routeControllerAndMethod
     *
     * @return bool
     */
    private function isValidRoute(array $routeControllerAndMethod = null)
    {
        if (is_array($routeControllerAndMethod)) {
            $routeControllerAndMethod = implode('@', $routeControllerAndMethod);
        }

        return ! is_callable($routeControllerAndMethod) && ! is_null($routeControllerAndMethod);
    }

    /**
     * @param array $routeAction
     *
     * @return bool
     */
    private function isClosureRoute(array $routeAction)
    {
        return $routeAction['uses'] instanceof \Closure;
    }

    /**
     * @param array $routeControllerAndMethod
     *
     * @throws ReflectionException
     *
     * @return bool
     */
    private function doesControllerMethodExist(array $routeControllerAndMethod)
    {
        [$class, $method] = $routeControllerAndMethod;
        $reflection = new ReflectionClass($class);

        if (! $reflection->hasMethod($method)) {
            return false;
        }

        return true;
    }

    /**
     * @param array $routeControllerAndMethod
     *
     * @throws ReflectionException
     *
     * @return bool
     */
    private function isRouteVisibleForDocumentation(array $routeControllerAndMethod)
    {
        [$class, $method] = $routeControllerAndMethod;
        $reflection = new ReflectionClass($class);

        $tags = collect();

        foreach (
            array_filter([
                $reflection->getDocComment(),
                $reflection->getMethod($method)->getDocComment()
            ]) as $comment
        ) {
            $phpdoc = new DocBlock($comment);
            $tags = $tags->concat($phpdoc->getTags());
        }

        return $tags->filter(function ($tag) {
            return $tag->getName() === 'hideFromAPIDocumentation';
        })->isEmpty();
    }
}

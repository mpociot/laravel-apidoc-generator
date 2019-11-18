<?php

namespace Mpociot\ApiDoc\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Mpociot\ApiDoc\Extracting\Generator;
use Mpociot\ApiDoc\Matching\RouteMatcher;
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

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // Using a global static variable here, so fuck off if you don't like it.
        // Also, the --verbose option is included with all Artisan commands.
        Flags::$shouldBeVerbose = $this->option('verbose');

        $this->docConfig = new DocumentationConfig(config('apidoc'));
        $this->baseUrl = $this->docConfig->get('base_url') ?? config('app.url');

        URL::forceRootUrl($this->baseUrl);

        $routeMatcher = new RouteMatcher($this->docConfig->get('routes'), $this->docConfig->get('router'));
        $routes = $routeMatcher->getRoutes();

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
     * @param array $routes
     *
     * @return array
     */
    private function processRoutes(Generator $generator, array $routes)
    {
        $parsedRoutes = [];
        foreach ($routes as $routeItem) {
            $route = $routeItem['route'];
            /** @var Route $route */
            $messageFormat = '%s route: [%s] %s';
            $routeMethods = implode(',', $generator->getMethods($route));
            $routePath = $generator->getUri($route);

            if (! $this->isValidRoute($route) || ! $this->isRouteVisibleForDocumentation($route->getAction())) {
                $this->warn(sprintf($messageFormat, 'Skipping', $routeMethods, $routePath));
                continue;
            }

            try {
                $parsedRoutes[] = $generator->processRoute($route, $routeItem['apply'] ?? []);
                $this->info(sprintf($messageFormat, 'Processed', $routeMethods, $routePath));
            } catch (\Exception $exception) {
                $this->warn(sprintf($messageFormat, 'Skipping', $routeMethods, $routePath).' - '.$exception->getMessage());
            }
        }

        return $parsedRoutes;
    }

    /**
     * @param Route $route
     *
     * @return bool
     */
    private function isValidRoute(Route $route)
    {
        $action = Utils::getRouteClassAndMethodNames($route->getAction());
        if (is_array($action)) {
            $action = implode('@', $action);
        }

        return ! is_callable($action) && ! is_null($action);
    }

    /**
     * @param array $action
     *
     * @throws ReflectionException
     *
     * @return bool
     */
    private function isRouteVisibleForDocumentation(array $action)
    {
        list($class, $method) = Utils::getRouteClassAndMethodNames($action);
        $reflection = new ReflectionClass($class);

        if (! $reflection->hasMethod($method)) {
            return false;
        }

        $comment = $reflection->getMethod($method)->getDocComment();

        if ($comment) {
            $phpdoc = new DocBlock($comment);

            return collect($phpdoc->getTags())
                ->filter(function ($tag) {
                    return $tag->getName() === 'hideFromAPIDocumentation';
                })
                ->isEmpty();
        }

        return true;
    }
}

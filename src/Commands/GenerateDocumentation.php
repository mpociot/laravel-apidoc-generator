<?php

namespace Mpociot\ApiDoc\Commands;

use ReflectionClass;
use ReflectionException;
use Illuminate\Routing\Route;
use Illuminate\Console\Command;
use Mpociot\Reflection\DocBlock;
use Illuminate\Support\Collection;
use Mpociot\ApiDoc\Tools\Generator;
use Mpociot\ApiDoc\Tools\RouteMatcher;
use Mpociot\Documentarian\Documentarian;
use Mpociot\ApiDoc\Postman\CollectionWriter;

class GenerateDocumentation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apidoc:generate
                            {--force : Force rewriting of existing routes}
                            {--only-tags= : Comma-separated list of tags to generate}
                            {--skip-tags= : Comma-separated list of tags to skip}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate your API documentation from existing Laravel routes.';

    private $routeMatcher;

    public function __construct(RouteMatcher $routeMatcher)
    {
        parent::__construct();
        $this->routeMatcher = $routeMatcher;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $usingDingoRouter = strtolower(config('apidoc.router')) == 'dingo';
        if ($usingDingoRouter) {
            $routes = $this->routeMatcher->getDingoRoutesToBeDocumented(config('apidoc.routes'));
        } else {
            $routes = $this->routeMatcher->getLaravelRoutesToBeDocumented(config('apidoc.routes'));
        }

        $generator = new Generator();
        $parsedRoutes = $this->processRoutes($generator, $routes);
        $parsedRoutes = collect($parsedRoutes)->groupBy('group')
            ->sortBy(static function ($group) {
                /* @var $group Collection */
                return $group->first()['group'];
            }, SORT_NATURAL);

        $this->writeMarkdown($parsedRoutes);
    }

    /**
     * @param  Collection $parsedRoutes
     *
     * @return void
     */
    private function writeMarkdown($parsedRoutes)
    {
        $outputPath = config('apidoc.output');
        $targetFile = $outputPath.DIRECTORY_SEPARATOR.'source'.DIRECTORY_SEPARATOR.'index.md';
        $compareFile = $outputPath.DIRECTORY_SEPARATOR.'source'.DIRECTORY_SEPARATOR.'.compare.md';
        $prependFile = $outputPath.DIRECTORY_SEPARATOR.'source'.DIRECTORY_SEPARATOR.'prepend.md';
        $appendFile = $outputPath.DIRECTORY_SEPARATOR.'source'.DIRECTORY_SEPARATOR.'append.md';

        $infoText = view('apidoc::partials.info')
            ->with('outputPath', ltrim($outputPath, 'public/'))
            ->with('showPostmanCollectionButton', config('apidoc.postman'));

        $parsedRouteOutput = $parsedRoutes->map(function ($routeGroup) {
            return $routeGroup->map(function ($route) {
                $route['output'] = (string) view('apidoc::partials.route')->with('route', $route)->render();

                return $route;
            });
        });

        $frontmatter = view('apidoc::partials.frontmatter');
        /*
         * In case the target file already exists, we should check if the documentation was modified
         * and skip the modified parts of the routes.
         */
        if (file_exists($targetFile) && file_exists($compareFile)) {
            $generatedDocumentation = file_get_contents($targetFile);
            $compareDocumentation = file_get_contents($compareFile);

            if (preg_match('/---(.*)---\\s<!-- START_INFO -->/is', $generatedDocumentation, $generatedFrontmatter)) {
                $frontmatter = trim($generatedFrontmatter[1], "\n");
            }

            $parsedRouteOutput->transform(function ($routeGroup) use ($generatedDocumentation, $compareDocumentation) {
                return $routeGroup->transform(function ($route) use ($generatedDocumentation, $compareDocumentation) {
                    if (preg_match('/<!-- START_'.$route['id'].' -->(.*)<!-- END_'.$route['id'].' -->/is', $generatedDocumentation, $existingRouteDoc)) {
                        $routeDocumentationChanged = (preg_match('/<!-- START_'.$route['id'].' -->(.*)<!-- END_'.$route['id'].' -->/is', $compareDocumentation, $lastDocWeGeneratedForThisRoute) && $lastDocWeGeneratedForThisRoute[1] !== $existingRouteDoc[1]);
                        if ($routeDocumentationChanged === false || $this->option('force')) {
                            if ($routeDocumentationChanged) {
                                $this->warn('Discarded manual changes for route ['.implode(',', $route['methods']).'] '.$route['uri']);
                            }
                        } else {
                            $this->warn('Skipping modified route ['.implode(',', $route['methods']).'] '.$route['uri']);
                            $route['modified_output'] = $existingRouteDoc[0];
                        }
                    }

                    return $route;
                });
            });
        }

        $prependFileContents = file_exists($prependFile)
            ? file_get_contents($prependFile)."\n" : '';
        $appendFileContents = file_exists($appendFile)
            ? "\n".file_get_contents($appendFile) : '';

        $documentarian = new Documentarian();

        $markdown = view('apidoc::documentarian')
            ->with('writeCompareFile', false)
            ->with('frontmatter', $frontmatter)
            ->with('infoText', $infoText)
            ->with('prependMd', $prependFileContents)
            ->with('appendMd', $appendFileContents)
            ->with('outputPath', config('apidoc.output'))
            ->with('showPostmanCollectionButton', config('apidoc.postman'))
            ->with('parsedRoutes', $parsedRouteOutput);

        if (! is_dir($outputPath)) {
            $documentarian->create($outputPath);
        }

        // Write output file
        file_put_contents($targetFile, $markdown);

        // Write comparable markdown file
        $compareMarkdown = view('apidoc::documentarian')
            ->with('writeCompareFile', true)
            ->with('frontmatter', $frontmatter)
            ->with('infoText', $infoText)
            ->with('prependMd', $prependFileContents)
            ->with('appendMd', $appendFileContents)
            ->with('outputPath', config('apidoc.output'))
            ->with('showPostmanCollectionButton', config('apidoc.postman'))
            ->with('parsedRoutes', $parsedRouteOutput);

        file_put_contents($compareFile, $compareMarkdown);

        $this->info('Wrote index.md to: '.$outputPath);

        $this->info('Generating API HTML code');

        $documentarian->generate($outputPath);

        $this->info('Wrote HTML documentation to: '.$outputPath.'/index.html');

        if (config('apidoc.postman')) {
            $this->info('Generating Postman collection');

            file_put_contents($outputPath.DIRECTORY_SEPARATOR.'collection.json', $this->generatePostmanCollection($parsedRoutes));
        }

        if ($logo = config('apidoc.logo')) {
            copy(
                $logo,
                $outputPath.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'logo.png'
            );
        }
    }

    /**
     * @param Generator $generator
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
            if ($this->isValidRoute($route) && $this->isRouteVisibleForDocumentation($route)) {
                $parsedRoutes[] = $generator->processRoute($route, $routeItem['apply']);
                $this->info('Processed route: ['.implode(',', $generator->getMethods($route)).'] '.$generator->getUri($route));
            } else {
                $this->warn('Skipping route: ['.implode(',', $generator->getMethods($route)).'] '.$generator->getUri($route));
            }
        }

        return $parsedRoutes;
    }

    /**
     * @param $route
     *
     * @return bool
     */
    private function isValidRoute(Route $route)
    {
        return ! is_callable($route->getAction()['uses']) && ! is_null($route->getAction()['uses']);
    }

    /**
     * @param $route
     *
     * @throws ReflectionException
     *
     * @return bool
     */
    private function isRouteVisibleForDocumentation($route)
    {
        list($class, $method) = explode('@', $route->getAction()['uses']);
        $reflection = new ReflectionClass($class);

        if (! $reflection->hasMethod($method)) {
            return false;
        }

        $comment = $reflection->getMethod($method)->getDocComment();

        $allowedTags = str_replace(',,', ',', $this->option('only-tags'));
        $disallowedTags = str_replace(',,', ',', $this->option('skip-tags'));

        $allowedTags = trim($allowedTags) ? explode(',', $allowedTags) : [];
        $disallowedTags = trim($disallowedTags) ? explode(',', $disallowedTags) : [];

        $routeTags = $route->getAction('tags');

        if ($routeTags) {
            if (! is_array($routeTags)) {
                $routeTags = [$routeTags];
            }
            if (! $this->skipRouteWithTags($routeTags, $allowedTags, $disallowedTags)) {
                return true;
            }
        }

        if ($comment) {
            $phpdoc = new DocBlock($comment);

            if (count($allowedTags) && ! $phpdoc->hasTag('tags')) {
                return false;
            }

            return collect($phpdoc->getTags())
                ->filter(function ($tag) use ($allowedTags, $disallowedTags) {
                    if ((count($allowedTags) || count($disallowedTags)) &&
                        $tag->getName() == 'tags') {
                        $tags = explode(' ', $tag->getContent());
                        return $this->skipRouteWithTags($tags, $allowedTags, $disallowedTags);
                    }

                    return $tag->getName() === 'hideFromAPIDocumentation';
                })
                ->isEmpty();
        } elseif (count($allowedTags)) {
            return false;
        }

        return true;
    }

    private function skipRouteWithTags(array $tags, array $allowedTags, array $disallowedTags)
    {
        $containedAllowedTags = array_intersect($tags, $allowedTags);
        $containedDisallowedTags = array_intersect($tags, $disallowedTags);

        return ! count($containedAllowedTags) || count($containedDisallowedTags);
    }

    /**
     * Generate Postman collection JSON file.
     *
     * @param Collection $routes
     *
     * @return string
     */
    private function generatePostmanCollection(Collection $routes)
    {
        $writer = new CollectionWriter($routes);

        return $writer->getCollection();
    }
}

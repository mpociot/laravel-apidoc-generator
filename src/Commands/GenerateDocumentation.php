<?php

namespace Mpociot\ApiDoc\Commands;

use ReflectionClass;
use Illuminate\Routing\Route;
use Illuminate\Console\Command;
use Mpociot\Reflection\DocBlock;
use Illuminate\Support\Collection;
use Mpociot\ApiDoc\Tools\RouteMatcher;
use Mpociot\Documentarian\Documentarian;
use Mpociot\ApiDoc\Postman\CollectionWriter;
use Mpociot\ApiDoc\Generators\DingoGenerator;
use Mpociot\ApiDoc\Generators\LaravelGenerator;
use Mpociot\ApiDoc\Generators\AbstractGenerator;

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

    private $routeMatcher;

    public function __construct(RouteMatcher $routeMatcher)
    {
        parent::__construct();
        $this->routeMatcher = $routeMatcher;
    }

    /**
     * Execute the console command.
     *
     * @return false|null
     */
    public function handle()
    {
        $usingDIngoRouter = config('apidoc.router') == 'dingo';
        if ($usingDIngoRouter) {
            $routes = $this->routeMatcher->getDingoRoutesToBeDocumented(config('apidoc.routes'));
            $generator = new DingoGenerator();
        } else {
            $routes = $this->routeMatcher->getLaravelRoutesToBeDocumented(config('apidoc.routes'));
            $generator = new LaravelGenerator();
        }

        $parsedRoutes = $this->processRoutes($generator, $routes);
        $parsedRoutes = collect($parsedRoutes)->groupBy('resource')
            ->sort(function ($a, $b) {
                return strcmp($a->first()['resource'], $b->first()['resource']);
            });

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
                $route['output'] = (string) view('apidoc::partials.route')->with('parsedRoute', $route)->render();

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
    }

    /**
     * @param AbstractGenerator $generator
     * @param array $routes
     *
     * @return array
     */
    private function processRoutes(AbstractGenerator $generator, array $routes)
    {
        $parsedRoutes = [];
        foreach ($routes as $routeItem) {
            $route = $routeItem['route'];
            /** @var Route $route */
            if ($this->isValidRoute($route) && $this->isRouteVisibleForDocumentation($route->getAction()['uses'])) {
                $parsedRoutes[] = $generator->processRoute($route) + $routeItem['apply'];
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
     * @return bool
     */
    private function isRouteVisibleForDocumentation($route)
    {
        list($class, $method) = explode('@', $route);
        $reflection = new ReflectionClass($class);
        $comment = $reflection->getMethod($method)->getDocComment();
        if ($comment) {
            $phpdoc = new DocBlock($comment);

            return collect($phpdoc->getTags())
                ->filter(function ($tag) use ($route) {
                    return $tag->getName() === 'hideFromAPIDocumentation';
                })
                ->isEmpty();
        }

        return true;
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

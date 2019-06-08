<?php

namespace Mpociot\ApiDoc\Commands;

use ReflectionClass;
use ReflectionException;
use Illuminate\Routing\Route;
use Illuminate\Console\Command;
use Mpociot\ApiDoc\Tools\Utils;
use Mpociot\Reflection\DocBlock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Mpociot\ApiDoc\Tools\Generator;
use Mpociot\ApiDoc\Tools\RouteMatcher;
use Mpociot\Documentarian\Documentarian;
use Mpociot\ApiDoc\Postman\CollectionWriter;
use Mpociot\ApiDoc\Tools\DocumentationConfig;

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

    /**
     * @var DocumentationConfig
     */
    private $docConfig;

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
        $docConfigs = config('apidoc');

        foreach ($docConfigs as $docConfig) {
            $this->docConfig = new DocumentationConfig($docConfig);

            try {
                URL::forceRootUrl(config('app.url'));
            } catch (\Exception $e) {
                echo "Warning: Couldn't force base url for {$this->docConfig->get('id')} as Lumen currently doesn't have the forceRootUrl method.\n";
                echo "You should probably double check URLs in your generated documentation.\n";
            }

            $usingDingoRouter = strtolower($this->docConfig->get('router')) == 'dingo';
            $routes = $this->docConfig->get('routes');
            if ($usingDingoRouter) {
                $routes = $this->routeMatcher->getDingoRoutesToBeDocumented($routes);
            } else {
                $routes = $this->routeMatcher->getLaravelRoutesToBeDocumented($routes);
            }

            $generator = new Generator($this->docConfig);
            $parsedRoutes = $this->processRoutes($generator, $routes);
            $parsedRoutes = collect($parsedRoutes)->groupBy('group')
                ->sortBy(static function ($group) {
                    /* @var $group Collection */
                    return $group->first()['group'];
                }, SORT_NATURAL);

            $this->writeMarkdown($parsedRoutes);
        }
    }

    /**
     * @param  Collection $parsedRoutes
     *
     * @return void
     */
    private function writeMarkdown($parsedRoutes)
    {
        $slash = DIRECTORY_SEPARATOR;

        $isStatic = $this->docConfig->get('output') === 'static';
        $path = $this->docConfig->get('id');
        $sourcePath = resource_path("apidoc/$path");
        $outputPath = $isStatic ? public_path("apidoc/$path") : resource_path('views/apidoc');

        $targetFile = $sourcePath.$slash.'source'.$slash.'index.md';
        $compareFile = $sourcePath.$slash.'source'.$slash.'.compare.md';
        $prependFile = $sourcePath.$slash.'source'.$slash.'prepend.md';
        $appendFile = $sourcePath.$slash.'source'.$slash.'append.md';

        $infoText = view('apidoc::partials.info')
            ->with('showPostmanCollectionButton', $this->shouldGeneratePostmanCollection());

        $settings = ['languages' => $this->docConfig->get('example_languages')];
        $parsedRouteOutput = $parsedRoutes->map(function ($routeGroup) use ($settings) {
            return $routeGroup->map(function ($route) use ($settings) {
                if (count($route['cleanBodyParameters']) && ! isset($route['headers']['Content-Type'])) {
                    $route['headers']['Content-Type'] = 'application/json';
                }
                $route['output'] = (string) view('apidoc::partials.route')
                    ->with('route', $route)
                    ->with('settings', $settings)
                    ->render();

                return $route;
            });
        });

        $frontmatter = view('apidoc::partials.frontmatter')
            ->with('settings', $settings);
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
            ->with('showPostmanCollectionButton', $this->shouldGeneratePostmanCollection())
            ->with('parsedRoutes', $parsedRouteOutput);

        if (! is_dir($sourcePath)) {
            $documentarian->create($sourcePath);
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
            ->with('showPostmanCollectionButton', $this->shouldGeneratePostmanCollection())
            ->with('parsedRoutes', $parsedRouteOutput);

        file_put_contents($compareFile, $compareMarkdown);

        $this->info('Wrote index.md to: '.$sourcePath);

        $this->info('Generating API HTML code');

        // Documentarian expects output path and source path to be the same,
        // so we deceive it by copying output over to source momentarily
        rcopy($sourcePath, $outputPath);
        $documentarian->generate($outputPath);
        if ($outputPath !== $sourcePath) {
            Utils::deleteFolderWithFiles($outputPath.'/source');
        }

        $this->info('Wrote HTML documentation to: '.$outputPath.'/index.html');

        if ($this->shouldGeneratePostmanCollection()) {
            $this->info('Generating Postman collection');
            $collectionName = $outputPath.DIRECTORY_SEPARATOR.($isStatic ? 'collection.json' : "$path.json");
            file_put_contents($collectionName, $this->generatePostmanCollection($parsedRoutes));
        }

        if ($logo = $this->docConfig->get('logo')) {
            copy(
                $logo,
                $outputPath.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'logo.png'
            );
        }

        if (! $isStatic) {
            Utils::moveFilesFromFolder(
                "$outputPath{$slash}images",
                public_path("apidoc/$path/images")
            );
            Utils::moveFilesFromFolder(
                "$outputPath{$slash}css",
                public_path("apidoc/$path/css")
            );
            Utils::moveFilesFromFolder(
                "$outputPath{$slash}js",
                public_path("apidoc/$path/js")
            );
            $filename = $outputPath."/$path.blade.php";
            rename($outputPath.'/index.html', $filename);
            $doc = file_get_contents($filename);
            $doc = str_replace('href="css/', "href=\"apidoc/$path/css/", $doc);
            $doc = str_replace('src="js/', "src=\"apidoc/$path/js/", $doc);
            $doc = str_replace('src="images/', "src=\"apidoc/$path/images/", $doc);
            file_put_contents($filename, $doc);
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
            if ($this->isValidRoute($route) && $this->isRouteVisibleForDocumentation($route->getAction()['uses'])) {
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
        list($class, $method) = explode('@', $route);
        $reflection = new ReflectionClass($class);

        if (! $reflection->hasMethod($method)) {
            return false;
        }

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

    /**
     * Checks config if it should generate Postman collection.
     *
     * @return bool
     */
    private function shouldGeneratePostmanCollection()
    {
        return $this->docConfig->get('postman.enabled', is_bool($this->docConfig->get('postman')) ? $this->docConfig->get('postman') : false);
    }
}

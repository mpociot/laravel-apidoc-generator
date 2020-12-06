<?php

namespace Mpociot\ApiDoc\Writing;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Mpociot\ApiDoc\Tools\DocumentationConfig;
use Mpociot\Documentarian\Documentarian;

class Writer
{
    /**
     * @var Command
     */
    protected $output;

    /**
     * @var DocumentationConfig
     */
    private $config;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var bool
     */
    private $forceIt;

    /**
     * @var bool
     */
    private $shouldGeneratePostmanCollection = true;

    /**
     * @var Documentarian
     */
    private $documentarian;

    /**
     * @var bool
     */
    private $isStatic;

    /**
     * @var string
     */
    private $sourceOutputPath;

    /**
     * @var string
     */
    private $outputPath;

    public function __construct(Command $output, DocumentationConfig $config = null, bool $forceIt = false)
    {
        // If no config is injected, pull from global
        $this->config = $config ?: new DocumentationConfig(config('apidoc'));
        $this->baseUrl = $this->config->get('base_url') ?? config('app.url');
        $this->forceIt = $forceIt;
        $this->output = $output;
        $this->shouldGeneratePostmanCollection = $this->config->get('postman.enabled', false);
        $this->documentarian = new Documentarian();
        $this->isStatic = $this->config->get('type') === 'static';
        $this->sourceOutputPath = 'resources/docs';
        $this->outputPath = $this->isStatic ? ($this->config->get('output_folder') ?? 'public/docs') : 'resources/views/apidoc';
    }

    public function writeDocs(Collection $routes)
    {
        // The source files (index.md, js/, css/, and images/) always go in resources/docs/source.
        // The static assets (js/, css/, and images/) always go in public/docs/.
        // For 'static' docs, the output files (index.html, collection.json) go in public/docs/.
        // For 'laravel' docs, the output files (index.blade.php, collection.json)
        // go in resources/views/apidoc/ and storage/app/apidoc/ respectively.

        $this->writeMarkdownAndSourceFiles($routes);

        $this->writeHtmlDocs();

        $this->writePostmanCollection($routes);
    }

    /**
     * @param  Collection $parsedRoutes
     *
     * @return void
     */
    public function writeMarkdownAndSourceFiles(Collection $parsedRoutes)
    {
        $targetFile = $this->sourceOutputPath . '/source/index.md';
        $compareFile = $this->sourceOutputPath . '/source/.compare.md';

        $infoText = view('apidoc::partials.info')
            ->with('outputPath', 'docs')
            ->with('showPostmanCollectionButton', $this->shouldGeneratePostmanCollection);

        $settings = ['languages' => $this->config->get('example_languages')];
        // Generate Markdown for each route
        $parsedRouteOutput = $this->generateMarkdownOutputForEachRoute($parsedRoutes, $settings);

        $frontmatter = view('apidoc::partials.frontmatter')
            ->with('settings', $settings);

        /*
         * If the target file already exists,
         * we check if the documentation was modified
         * and skip the modified parts of the routes.
         */
        if (file_exists($targetFile) && file_exists($compareFile)) {
            $generatedDocumentation = file_get_contents($targetFile);
            $compareDocumentation = file_get_contents($compareFile);

            $parsedRouteOutput->transform(function (Collection $routeGroup) use ($generatedDocumentation, $compareDocumentation) {
                return $routeGroup->transform(function (array $route) use ($generatedDocumentation, $compareDocumentation) {
                    if (preg_match('/<!-- START_' . $route['id'] . ' -->(.*)<!-- END_' . $route['id'] . ' -->/is', $generatedDocumentation, $existingRouteDoc)) {
                        $routeDocumentationChanged = (preg_match('/<!-- START_' . $route['id'] . ' -->(.*)<!-- END_' . $route['id'] . ' -->/is', $compareDocumentation, $lastDocWeGeneratedForThisRoute) && $lastDocWeGeneratedForThisRoute[1] !== $existingRouteDoc[1]);
                        if ($routeDocumentationChanged === false || $this->forceIt) {
                            if ($routeDocumentationChanged) {
                                $this->output->warn('Discarded manual changes for route [' . implode(',', $route['methods']) . '] ' . $route['uri']);
                            }
                        } else {
                            $this->output->warn('Skipping modified route [' . implode(',', $route['methods']) . '] ' . $route['uri']);
                            $route['modified_output'] = $existingRouteDoc[0];
                        }
                    }

                    return $route;
                });
            });
        }

        $prependFileContents = $this->getMarkdownToPrepend();
        $appendFileContents = $this->getMarkdownToAppend();

        $markdown = view('apidoc::documentarian')
            ->with('writeCompareFile', false)
            ->with('frontmatter', $frontmatter)
            ->with('infoText', $infoText)
            ->with('prependMd', $prependFileContents)
            ->with('appendMd', $appendFileContents)
            ->with('outputPath', $this->config->get('output'))
            ->with('showPostmanCollectionButton', $this->shouldGeneratePostmanCollection)
            ->with('parsedRoutes', $parsedRouteOutput);

        $this->output->info('Writing index.md and source files to: ' . $this->sourceOutputPath);

        if (! is_dir($this->sourceOutputPath)) {
            $documentarian = new Documentarian();
            $documentarian->create($this->sourceOutputPath);
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
            ->with('outputPath', $this->config->get('output'))
            ->with('showPostmanCollectionButton', $this->shouldGeneratePostmanCollection)
            ->with('parsedRoutes', $parsedRouteOutput);

        file_put_contents($compareFile, $compareMarkdown);

        $this->output->info('Wrote index.md and source files to: ' . $this->sourceOutputPath);
    }

    public function generateMarkdownOutputForEachRoute(Collection $parsedRoutes, array $settings): Collection
    {
        $parsedRouteOutput = $parsedRoutes->map(function (Collection $routeGroup) use ($settings) {
            return $routeGroup->map(function (array $route) use ($settings) {
                if (count($route['cleanBodyParameters']) && ! isset($route['headers']['Content-Type'])) {
                    // Set content type if the user forgot to set it
                    $route['headers']['Content-Type'] = 'application/json';
                }

                $hasRequestOptions = ! empty($route['headers']) || ! empty($route['cleanQueryParameters']) || ! empty($route['cleanBodyParameters']);
                $route['output'] = (string) view('apidoc::partials.route')
                    ->with('hasRequestOptions', $hasRequestOptions)
                    ->with('route', $route)
                    ->with('settings', $settings)
                    ->with('baseUrl', $this->baseUrl)
                    ->render();

                return $route;
            });
        });

        return $parsedRouteOutput;
    }

    protected function writePostmanCollection(Collection $parsedRoutes): void
    {
        if ($this->shouldGeneratePostmanCollection) {
            $this->output->info('Generating Postman collection');

            $collection = $this->generatePostmanCollection($parsedRoutes);
            if ($this->isStatic) {
                $collectionPath = "{$this->outputPath}/collection.json";
                file_put_contents($collectionPath, $collection);
            } else {
                $storageInstance = Storage::disk($this->config->get('storage'));
                $storageInstance->put('apidoc/collection.json', $collection, 'public');
                if ($this->config->get('storage') == 'local') {
                    $collectionPath = 'storage/app/apidoc/collection.json';
                } else {
                    $collectionPath = $storageInstance->url('collection.json');
                }
            }

            $this->output->info("Wrote Postman collection to: {$collectionPath}");
        }
    }

    /**
     * Generate Postman collection JSON file.
     *
     * @param Collection $routes
     *
     * @return string
     */
    public function generatePostmanCollection(Collection $routes)
    {
        /** @var PostmanCollectionWriter $writer */
        $writer = app()->makeWith(
            PostmanCollectionWriter::class,
            ['routeGroups' => $routes, 'baseUrl' => $this->baseUrl]
        );

        return $writer->getCollection();
    }

    protected function getMarkdownToPrepend(): string
    {
        $prependFile = $this->sourceOutputPath . '/source/prepend.md';
        $prependFileContents = file_exists($prependFile)
            ? file_get_contents($prependFile) . "\n" : '';

        return $prependFileContents;
    }

    protected function getMarkdownToAppend(): string
    {
        $appendFile = $this->sourceOutputPath . '/source/append.md';
        $appendFileContents = file_exists($appendFile)
            ? "\n" . file_get_contents($appendFile) : '';

        return $appendFileContents;
    }

    protected function copyAssetsFromSourceFolderToPublicFolder(): void
    {
        $publicPath = $this->config->get('output_folder') ?? 'public/docs';
        if (! is_dir($publicPath)) {
            mkdir($publicPath, 0777, true);
            mkdir("{$publicPath}/css");
            mkdir("{$publicPath}/js");
        }
        copy("{$this->sourceOutputPath}/js/all.js", "{$publicPath}/js/all.js");
        rcopy("{$this->sourceOutputPath}/images", "{$publicPath}/images");
        rcopy("{$this->sourceOutputPath}/css", "{$publicPath}/css");

        if ($logo = $this->config->get('logo')) {
            copy($logo, "{$publicPath}/images/logo.png");
        }
    }

    protected function moveOutputFromSourceFolderToTargetFolder(): void
    {
        if ($this->isStatic) {
            // Move output (index.html, css/style.css and js/all.js) to public/docs
            rename("{$this->sourceOutputPath}/index.html", "{$this->outputPath}/index.html");
        } else {
            // Move output to resources/views
            if (! is_dir($this->outputPath)) {
                mkdir($this->outputPath);
            }
            rename("{$this->sourceOutputPath}/index.html", "$this->outputPath/index.blade.php");
            $contents = file_get_contents("$this->outputPath/index.blade.php");
            //
            $contents = str_replace('href="css/style.css"', 'href="{{ asset(\'/docs/css/style.css\') }}"', $contents);
            $contents = str_replace('src="js/all.js"', 'src="{{ asset(\'/docs/js/all.js\') }}"', $contents);
            $contents = str_replace('src="images/', 'src="/docs/images/', $contents);
            $contents = preg_replace('#href="https?://.+?/docs/collection.json"#', 'href="{{ route("apidoc.json") }}"', $contents);
            file_put_contents("$this->outputPath/index.blade.php", $contents);
        }
    }

    public function writeHtmlDocs(): void
    {
        $this->output->info('Generating API HTML code');

        $this->documentarian->generate($this->sourceOutputPath);

        // Move assets to public folder
        $this->copyAssetsFromSourceFolderToPublicFolder();

        $this->moveOutputFromSourceFolderToTargetFolder();

        $this->output->info("Wrote HTML documentation to: {$this->outputPath}");
    }
}

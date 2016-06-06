<?php

namespace Mpociot\ApiDoc\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Mpociot\ApiDoc\Generators\AbstractGenerator;
use Mpociot\ApiDoc\Generators\DingoGenerator;
use Mpociot\ApiDoc\Generators\LaravelGenerator;
use Mpociot\Documentarian\Documentarian;

class GenerateDocumentation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:generate 
                            {--output=public/docs : The output path for the generated documentation}
                            {--routePrefix= : The route prefix to use for generation}
                            {--routes=* : The route names to use for generation}
                            {--actAsUserId= : The user ID to use for API response calls}
                            {--router=laravel : The router to be used (Laravel or Dingo)}
                            {--bindings= : Route Model Bindings}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate your API documentation from existing Laravel routes.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return false|null
     */
    public function handle()
    {
        if ($this->option('router') === 'laravel') {
            $generator = new LaravelGenerator();
        } else {
            $generator = new DingoGenerator();
        }

        $allowedRoutes = $this->option('routes');
        $routePrefix = $this->option('routePrefix');

        $this->setUserToBeImpersonated($this->option('actAsUserId'));

        if ($routePrefix === null && ! count($allowedRoutes)) {
            $this->error('You must provide either a route prefix or a route to generate the documentation.');

            return false;
        }

        if ($this->option('router') === 'laravel') {
            $parsedRoutes = $this->processLaravelRoutes($generator, $allowedRoutes, $routePrefix);
        } else {
            $parsedRoutes = $this->processDingoRoutes($generator, $allowedRoutes, $routePrefix);
        }
        $parsedRoutes = collect($parsedRoutes)->sortBy('resource')->groupBy('resource');

        $this->writeMarkdown($parsedRoutes);
    }

    /**
     * @param  Collection $parsedRoutes
     *
     * @return void
     */
    private function writeMarkdown($parsedRoutes)
    {
        $outputPath = $this->option('output');

        $documentarian = new Documentarian();

        $markdown = view('apidoc::documentarian')->with('parsedRoutes', $parsedRoutes->all());

        if (! is_dir($outputPath)) {
            $documentarian->create($outputPath);
        }

        file_put_contents($outputPath.DIRECTORY_SEPARATOR.'source'.DIRECTORY_SEPARATOR.'index.md', $markdown);

        $this->info('Wrote index.md to: '.$outputPath);

        $this->info('Generating API HTML code');

        $documentarian->generate($outputPath);

        $this->info('Wrote HTML documentation to: '.$outputPath.'/public/index.html');
    }

    /**
     * @return array
     */
    private function getBindings()
    {
        $bindings = $this->option('bindings');
        if (empty($bindings)) {
            return [];
        }
        $bindings = explode('|', $bindings);
        $resultBindings = [];
        foreach ($bindings as $binding) {
            list($name, $id) = explode(',', $binding);
            $resultBindings[$name] = $id;
        }

        return $resultBindings;
    }

    /**
     * @param $actAs
     */
    private function setUserToBeImpersonated($actAs)
    {
        if (! empty($actAs)) {
            if (version_compare($this->laravel->version(), '5.2.0', '<')) {
                $userModel = config('auth.model');
                $user = $userModel::find($actAs);
                $this->laravel['auth']->setUser($user);
            } else {
                $userModel = config('auth.providers.users.model');
                $user = $userModel::find($actAs);
                $this->laravel['auth']->guard()->setUser($user);
            }
        }
    }

    /**
     * @return mixed
     */
    private function getRoutes()
    {
        if ($this->option('router') === 'laravel') {
            return Route::getRoutes();
        } else {
            return app('Dingo\Api\Routing\Router')->getRoutes()[$this->option('routePrefix')];
        }
    }

    /**
     * @param AbstractGenerator  $generator
     * @param $allowedRoutes
     * @param $routePrefix
     *
     * @return array
     */
    private function processLaravelRoutes(AbstractGenerator $generator, $allowedRoutes, $routePrefix)
    {
        $routes = $this->getRoutes();
        $bindings = $this->getBindings();
        $parsedRoutes = [];
        foreach ($routes as $route) {
            if (in_array($route->getName(), $allowedRoutes) || str_is($routePrefix, $route->getUri())) {
                if ($this->isValidRoute($route)) {
                    $parsedRoutes[] = $generator->processRoute($route, $bindings);
                    $this->info('Processed route: '.$route->getUri());
                } else {
                    $this->warn('Skipping route: '.$route->getUri().' - contains closure.');
                }
            }
        }

        return $parsedRoutes;
    }

    /**
     * @param AbstractGenerator $generator
     * @param $allowedRoutes
     * @param $routePrefix
     *
     * @return array
     */
    private function processDingoRoutes(AbstractGenerator $generator, $allowedRoutes, $routePrefix)
    {
        $routes = $this->getRoutes();
        $bindings = $this->getBindings();
        $parsedRoutes = [];
        foreach ($routes as $route) {
            if (empty($allowedRoutes) || in_array($route->getName(), $allowedRoutes) || str_is($routePrefix, $route->uri())) {
                $parsedRoutes[] = $generator->processRoute($route, $bindings);
                $this->info('Processed route: '.$route->uri());
            }
        }

        return $parsedRoutes;
    }

    /**
     * @param $route
     *
     * @return bool
     */
    private function isValidRoute($route)
    {
        return ! is_callable($route->getAction()['uses']);
    }
}

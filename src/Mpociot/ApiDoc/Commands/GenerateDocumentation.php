<?php

namespace Mpociot\ApiDoc\Commands;

use Illuminate\Console\Command;
use Mpociot\ApiDoc\ApiDocGenerator;
use Illuminate\Support\Facades\Route;
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
        $allowedRoutes = $this->option('routes');
        $routePrefix = $this->option('routePrefix');
        $actAs = $this->option('actAsUserId');

        if ($routePrefix === null && ! count($allowedRoutes)) {
            $this->error('You must provide either a route prefix or a route to generate the documentation.');

            return false;
        }

        if ($actAs !== null) {
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

        $routes = Route::getRoutes();

        $generator = new ApiDocGenerator();

        /* @var \Illuminate\Routing\Route $route */
        $parsedRoutes = [];
        foreach ($routes as $route) {
            if (in_array($route->getName(), $allowedRoutes) || str_is($routePrefix, $route->getUri())) {
                $parsedRoutes[] = $generator->processRoute($route);
                $this->info('Processed route: '.$route->getUri());
            }
        }

        $this->writeMarkdown($parsedRoutes);
    }

    /**
     * @param  array  $parsedRoutes
     *
     * @return void
     */
    private function writeMarkdown($parsedRoutes)
    {
        $outputPath = $this->option('output');

        $documentarian = new Documentarian();

        $markdown = view('apidoc::documentarian')->with('parsedRoutes', $parsedRoutes);

        if (! is_dir($outputPath)) {
            $documentarian->create($outputPath);
        }

        file_put_contents($outputPath.DIRECTORY_SEPARATOR.'source'.DIRECTORY_SEPARATOR.'index.md', $markdown);

        $this->info('Wrote index.md to: '.$outputPath);

        $this->info('Generating API HTML code');

        $documentarian->generate($outputPath);

        $this->info('Wrote HTML documentation to: '.$outputPath.'/public/index.html');
    }
}

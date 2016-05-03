<?php

namespace Mpociot\ApiDoc\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Mpociot\ApiDoc\ApiDocGenerator;
use phpDocumentor\Reflection\DocBlock;
use Symfony\Component\Process\Process;

class GenerateDocumentation extends Command
{
    /**
     * The Whiteboard repository URL
     */
    const WHITEBOARD_REPOSITORY = 'https://github.com/mpociot/whiteboard.git';

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
    protected $description = 'Command description';

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
     * @return mixed
     */
    public function handle()
    {
        $generator = new ApiDocGenerator();
        $allowedRoutes = $this->option('routes');
        $routePrefix = $this->option('routePrefix');
        $actAs = $this->option('actAsUserId');

        if ($routePrefix === null && !count($allowedRoutes)) {
            $this->error('You must provide either a route prefix or a route to generate the documentation.');
            return false;
        }

        if ($actAs !== null) {
            $userModel = config('auth.providers.users.model');
            $user = $userModel::find($actAs);
            $this->laravel['auth']->guard()->setUser($user);
        }

        $routes = Route::getRoutes();

        /** @var \Illuminate\Routing\Route $route */
        $parsedRoutes = [];
        foreach ($routes as $route) {
            if (in_array($route->getName(), $allowedRoutes) || str_is($routePrefix, $route->getUri())) {
                $parsedRoutes[] = $generator->processRoute($route);
                $this->info('Processed route: ' . $route->getUri());
            }
        }

        $this->writeMarkdown($parsedRoutes);
    }

    /**
     * @param $parsedRoutes
     */
    private function writeMarkdown($parsedRoutes)
    {
        $outputPath = $this->option('output');

        $markdown = view('apidoc::whiteboard')->with('parsedRoutes', $parsedRoutes);

        if (!is_dir($outputPath)) {
            $this->cloneWhiteboardRepository();

            if ($this->confirm('Would you like to install the NPM dependencies?', true)) {
                $process = (new Process('npm set progress=false && npm install', $outputPath))->setTimeout(null);
                if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
                    $process->setTty(true);
                }
                $process->run(function ($type, $line) {
                    $this->info($line);
                });
            }
        }

        file_put_contents($outputPath . DIRECTORY_SEPARATOR . 'source' . DIRECTORY_SEPARATOR . 'index.md', $markdown);

        $this->info('Wrote index.md to: ' . $outputPath);

        $this->info('Generating API HTML code');

        $process = (new Process('npm run-script generate', $outputPath))->setTimeout(null);
        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            $process->setTty(true);
        }
        $process->run(function ($type, $line) {
            $this->info($line);
        });

        $this->info('Wrote HTML documentation to: ' . $outputPath . '/public/index.html');
    }

    /**
     * Clone the Whiteboard nodejs repository
     */
    private function cloneWhiteboardRepository()
    {
        $outputPath = $this->option('output');

        mkdir($outputPath, 0777, true);

        // Clone whiteboard
        $this->info('Cloning whiteboard repository.');

        $process = (new Process('git clone ' . self::WHITEBOARD_REPOSITORY . ' ' . $outputPath))->setTimeout(null);
        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            $process->setTty(true);
        }
        $process->run(function ($type, $line) {
            $this->info($line);
        });
    }

}

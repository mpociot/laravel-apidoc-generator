<?php

namespace Mpociot\ApiDoc\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\DocBlock;
use Sami\Parser\DocBlockParser;
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
                $parsedRoutes[] = $this->processRoute($route);
                $this->info('Processed route: ' . $route->getUri());
            }
        }

        $this->writeMarkdown($parsedRoutes);
    }

    /**
     * @param \Illuminate\Routing\Route $route
     * @return array
     */
    private function processRoute(\Illuminate\Routing\Route $route)
    {
        $routeAction = $route->getAction();
        $response = $this->getRouteResponse($route);
        $routeDescription = $this->getRouteDescription($routeAction['uses']);
        $routeData = [
            'title' => $routeDescription['short'],
            'description' => $routeDescription['long'],
            'methods' => $route->getMethods(),
            'uri' => $route->getUri(),
            'parameters' => [],
            'response' => ($response->headers->get('Content-Type') === 'application/json') ? json_encode(json_decode($response->getContent()), JSON_PRETTY_PRINT) : $response->getContent()
        ];

        $validator = Validator::make([], $this->getRouteRules($routeAction['uses']));
        foreach ($validator->getRules() as $attribute => $rules) {
            $attributeData = [
                'required' => false,
                'type' => 'string',
                'default' => '',
                'description' => []
            ];
            foreach ($rules as $rule) {
                $this->parseRule($rule, $attributeData);
            }
            $routeData['parameters'][$attribute] = $attributeData;
        }

        return $routeData;
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


    /**
     * @param $rule
     * @param $attributeData
     */
    protected function parseRule($rule, &$attributeData)
    {
        $parsedRule = $this->parseStringRule($rule);
        $parsedRule[0] = $this->normalizeRule($parsedRule[0]);
        list($rule, $parameters) = $parsedRule;

        switch ($rule) {
            case 'required':
                $attributeData['required'] = true;
                break;
            case 'in':
                $attributeData['description'][] = implode(' or ', $parameters);
                break;
            case 'not_in':
                $attributeData['description'][] = 'Not in: ' . implode(' or ', $parameters);
                break;
            case 'min':
                $attributeData['description'][] = 'Minimum: `' . $parameters[0] . '`';
                break;
            case 'max':
                $attributeData['description'][] = 'Maximum: `' . $parameters[0] . '`';
                break;
            case 'between':
                $attributeData['description'][] = 'Between: `' . $parameters[0] . '` and ' . $parameters[1];
                break;
            case 'date_format':
                $attributeData['description'][] = 'Date format: ' . $parameters[0];
                break;
            case 'mimetypes':
            case 'mimes':
                $attributeData['description'][] = 'Allowed mime types: ' . implode(', ', $parameters);
                break;
            case 'required_if':
                $attributeData['description'][] = 'Required if `' . $parameters[0] . '` is `' . $parameters[1] . '`';
                break;
            case 'exists':
                $attributeData['description'][] = 'Valid ' . Str::singular($parameters[0]) . ' ' . $parameters[1];
                break;
            case 'active_url':
                $attributeData['type'] = 'url';
                break;
            case 'boolean':
            case 'email':
            case 'image':
            case 'string':
            case 'integer':
            case 'json':
            case 'numeric':
            case 'url':
            case 'ip':
                $attributeData['type'] = $rule;
                break;
        }
    }

    /**
     * @param $route
     * @return array
     */
    private function getRouteRules($route)
    {
        list($class, $method) = explode('@', $route);
        $reflection = new \ReflectionClass($class);
        $reflectionMethod = $reflection->getMethod($method);

        foreach ($reflectionMethod->getParameters() as $parameter) {
            $parameterType = $parameter->getType();
            if (!is_null($parameterType) && class_exists($parameterType)) {
                $className = $parameterType->__toString();
                $parameterReflection = new $className;
                if ($parameterReflection instanceof FormRequest) {
                    if (method_exists($parameterReflection, 'validator')) {
                        return $parameterReflection->validator()->getRules();
                    } else {
                        return $parameterReflection->rules();
                    }
                }
            }
        }

        return [];
    }

    /**
     * @param $route
     * @return string
     */
    private function getRouteDescription($route)
    {
        list($class, $method) = explode('@', $route);
        $reflection = new \ReflectionClass($class);
        $reflectionMethod = $reflection->getMethod($method);

        $comment = $reflectionMethod->getDocComment();
        $phpdoc = new DocBlock($comment);
        return [
            'short' => $phpdoc->getShortDescription(),
            'long' => $phpdoc->getLongDescription()->getContents()
        ];
    }

    /**
     * @param \Illuminate\Routing\Route $route
     * @return \Illuminate\Http\Response
     */
    private function getRouteResponse(\Illuminate\Routing\Route $route)
    {
        $methods = $route->getMethods();
        $response = $this->callRoute(array_shift($methods), $route->getUri());
        return $response;
    }

    /**
     * Call the given URI and return the Response.
     *
     * @param  string $method
     * @param  string $uri
     * @param  array $parameters
     * @param  array $cookies
     * @param  array $files
     * @param  array $server
     * @param  string $content
     * @return \Illuminate\Http\Response
     */
    public function callRoute($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $kernel = App::make('Illuminate\Contracts\Http\Kernel');
        App::instance('middleware.disable', true);

        $server = [
            'CONTENT_TYPE' => 'application/json',
            'Accept' => 'application/json',
        ];

        $request = Request::create(
            $uri, $method, $parameters,
            $cookies, $files, $this->transformHeadersToServerVars($server), $content
        );

        $response = $kernel->handle($request);

        $kernel->terminate($request, $response);

        return $response;
    }

    /**
     * Transform headers array to array of $_SERVER vars with HTTP_* format.
     *
     * @param  array $headers
     * @return array
     */
    protected function transformHeadersToServerVars(array $headers)
    {
        $server = [];
        $prefix = 'HTTP_';

        foreach ($headers as $name => $value) {
            $name = strtr(strtoupper($name), '-', '_');

            if (!starts_with($name, $prefix) && $name != 'CONTENT_TYPE') {
                $name = $prefix . $name;
            }

            $server[$name] = $value;
        }

        return $server;
    }

    /**
     * Parse a string based rule.
     *
     * @param  string $rules
     * @return array
     */
    protected function parseStringRule($rules)
    {
        $parameters = [];

        // The format for specifying validation rules and parameters follows an
        // easy {rule}:{parameters} formatting convention. For instance the
        // rule "Max:3" states that the value may only be three letters.
        if (strpos($rules, ':') !== false) {
            list($rules, $parameter) = explode(':', $rules, 2);

            $parameters = $this->parseParameters($rules, $parameter);
        }

        return [strtolower(trim($rules)), $parameters];
    }

    /**
     * Parse a parameter list.
     *
     * @param  string $rule
     * @param  string $parameter
     * @return array
     */
    protected function parseParameters($rule, $parameter)
    {
        if (strtolower($rule) == 'regex') {
            return [$parameter];
        }

        return str_getcsv($parameter);
    }

    /**
     * Normalizes a rule so that we can accept short types.
     *
     * @param  string $rule
     * @return string
     */
    protected function normalizeRule($rule)
    {
        switch ($rule) {
            case 'int':
                return 'integer';
            case 'bool':
                return 'boolean';
            default:
                return $rule;
        }
    }
}

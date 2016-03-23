<?php

namespace Themsaid\RoutesPublisher;

use Illuminate\Foundation\Application;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use ReflectionMethod;
use ReflectionClass;

class RoutesPublisherCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'themsaid:publishRoutes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish implicit controller routes into routes.php';

    /**
     * The controllers namespace.
     *
     * @var string
     */
    protected $controllersNamespace = 'Http\Controllers';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Application $application)
    {
        parent::__construct();

        $this->controllersNamespace = $application->getNamespace().$this->controllersNamespace;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $exactFileContent = file_get_contents(app_path('Http/routes.php'));

        $preparedFileContent = $this->prepareFileContent($exactFileContent);

        $output = '';

        foreach (explode("\n", $preparedFileContent) as $line) {
            $preparedLine = $this->prepareLine($line);

            if (preg_match('/controller(?:[^s\(]*)\(([^,]*),([^,)]*)/', $preparedLine, $matches)) {
                if (count($matches) != 3) {
                    $this->error($preparedLine.' Looks weird, unable to parse it.');
                }

                preg_match('/^( *)/', $line, $spaces);

                $output .= $this->extractController(trim($matches[1], '\''), trim($matches[2], '\''), $spaces[1]);
            } elseif (preg_match('/controllers(?:[^\(]*)\((.*)\)/', $preparedLine, $matches)) {
                preg_match('/^( *)/', $line, $spaces);

                $output .= $this->extractControllers($matches[1], $spaces[1]);
            } else {
                $output .= $line."\n";
            }
        }

        file_put_contents(app_path('Http/routes.php.generated'), $output);

        file_put_contents(app_path('Http/routes.php.backup'), $preparedFileContent);

        $this->info('Done! Generated file was published in "'.app_path('Http/routes.php.generated').'"');

        $this->info('Also a backup of routes.php was published in "'.app_path('Http/routes.php.backup').'"');
    }

    /**
     * Prepare file content for parsing.
     *
     * @param string $fileContent
     *
     * @return string
     */
    private function prepareFileContent($fileContent)
    {
        // Remove 1 line comments
        $fileContent = preg_replace("#(.*)//(.*)#", " ", $fileContent);

        // Remove multi-line comments
        $fileContent = preg_replace("#/\*(.*)\*?/#", "", $fileContent);

        // Remove all new line characters
        $fileContent = preg_replace("/\n*/", "", $fileContent);

        // Add 2 new line characters after <?php
        $fileContent = str_replace("<?php", "<?php\n\n", $fileContent);

        // Add 1 new line characters after ;
        $fileContent = str_replace(";", ";\n", $fileContent);

        // Add 1 new line characters after {
        $fileContent = str_replace("{ ", "{\n", $fileContent);

        // Add 1 new line character after });
        $fileContent = str_replace("});", "});\n", $fileContent);

        return $fileContent;
    }

    /**
     * Prepare a single line for parsing.
     *
     * @param string $line
     *
     * @return string
     */
    private function prepareLine($line)
    {
        return str_replace(' ', '', $line);
    }

    /**
     * @param string $arrayString
     * @param string $initialSpace
     *
     * @return string
     */
    private function extractControllers($arrayString, $initialSpace = '')
    {
        $controllers = preg_match_all('/[\'"]([^\'"]*)[\'"]=>[\'"]([^\'"]*)[\'"]/', $arrayString, $matches);

        $routes = '';

        foreach (array_combine($matches[1], $matches[2]) as $path => $controller) {
            $routes .= $this->extractController($path, $controller, $initialSpace);
        }

        return $routes;
    }

    /**
     * Extract routes from a given controller
     *
     * @param string $path
     * @param string $controller
     *
     * @param string $initialSpace
     *
     * @return string
     */
    private function extractController($path, $controller, $initialSpace = '')
    {
        $class = new ReflectionClass($this->controllersNamespace.'\\'.$controller);

        $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);

        $routables = array_filter($methods, function ($method) {
            return
                $method->class != 'Illuminate\Routing\Controller' &&
                Str::startsWith($method->name, ['get', 'post', 'put', 'delete', 'patch']);
        });

        $routes = "\n$initialSpace// $controller\n";

        foreach ($routables as $routable) {
            $routes .= $initialSpace.$this->createRoute($routable, $path, $controller);
        }

        return $routes;
    }

    /**
     * @param ReflectionMethod $routable
     * @param string $path
     * @param string $controller
     *
     * @return string
     */
    private function createRoute(ReflectionMethod $routable, $path, $controller)
    {
        $verb = $this->getVerb($routable->name);

        $uri = $this->getUri($routable, $path);

        return 'Route::'."$verb('$uri', '$controller@{$routable->name}');\n";
    }

    /**
     * Get the verb of the route from name.
     *
     * @param  string $name
     *
     * @return string
     */
    public function getVerb($name)
    {
        return head(explode('_', Str::snake($name)));
    }

    /**
     * Determine the URI from the given method name.
     *
     * @param  ReflectionMethod $routable
     * @param  string $path
     *
     * @return string
     */
    public function getUri(ReflectionMethod $routable, $path)
    {
        $uri =
            $path
            .'/'
            .implode('-', array_slice(explode('_', Str::snake($routable->name)), 1))
            .'/'
            .$this->getWildcards($routable);

        $uri = str_replace('//', '/', $uri);

        $uri = rtrim($uri, '/');

        if (Str::endsWith($uri, 'index')) {
            $uri = Str::replaceLast('index', '', $uri);
        }

        return $uri;
    }

    /**
     * Get the wildcards string for the route URI.
     *
     * @param ReflectionMethod $routable
     *
     * @return string
     */
    private function getWildcards(ReflectionMethod $routable)
    {
        $output = '';

        foreach ($routable->getParameters() as $parameter) {
            if ($parameter->hasType()) {
                continue;
            }

            $wildCard = Str::snake($parameter->getName()).($parameter->isDefaultValueAvailable() ? '?' : '');

            $output .= '{'.$wildCard.'}/';
        }

        return $output;
    }
}

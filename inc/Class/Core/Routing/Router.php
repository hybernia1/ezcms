<?php

declare(strict_types=1);

namespace Core\Routing;

use InvalidArgumentException;
use RuntimeException;

final class Router
{
    /**
     * @var list<Route>
     */
    private array $routes = [];

    private string $basePath;

    public function __construct(string $basePath = '')
    {
        $this->basePath = '/' . trim($basePath, '/');
        if ($this->basePath === '/') {
            $this->basePath = '';
        }
    }

    public function add(
        callable $handler,
        string $pattern,
        array|string $methods = 'GET',
        ?string $name = null,
        array $defaults = [],
        array $requirements = [],
        int $priority = 0,
    ): Route {
        $methods = $this->normalizeMethods($methods);
        $pattern = $this->normalizePattern($pattern);

        $route = new Route($methods, $pattern, $handler, $name, $defaults, $requirements, $priority);
        $this->routes[] = $route;
        usort($this->routes, static function (Route $a, Route $b): int {
            return $b->getPriority() <=> $a->getPriority();
        });

        return $route;
    }

    public function get(callable $handler, string $pattern, ?string $name = null, array $defaults = [], array $requirements = [], int $priority = 0): Route
    {
        return $this->add($handler, $pattern, 'GET', $name, $defaults, $requirements, $priority);
    }

    public function post(callable $handler, string $pattern, ?string $name = null, array $defaults = [], array $requirements = [], int $priority = 0): Route
    {
        return $this->add($handler, $pattern, 'POST', $name, $defaults, $requirements, $priority);
    }

    public function map(array|string $methods, callable $handler, string $pattern, ?string $name = null, array $defaults = [], array $requirements = [], int $priority = 0): Route
    {
        return $this->add($handler, $pattern, $methods, $name, $defaults, $requirements, $priority);
    }

    public function match(string $method, string $path): ?RouteMatch
    {
        $path = $this->stripQueryString($path);
        $path = $this->stripBasePath($path);

        foreach ($this->routes as $route) {
            if (!$this->methodMatches($method, $route->getMethods())) {
                continue;
            }

            $compiled = $this->compilePattern($route->getPattern(), $route->getRequirements());
            if (!preg_match($compiled['regex'], $path, $matches)) {
                continue;
            }

            $params = $route->getDefaults();
            foreach ($compiled['variables'] as $variable) {
                if (array_key_exists($variable, $matches)) {
                    $params[$variable] = $matches[$variable];
                }
            }

            return new RouteMatch($route, $params);
        }

        return null;
    }

    public function dispatch(string $method, string $path): mixed
    {
        $match = $this->match($method, $path);
        if ($match === null) {
            throw new RuntimeException(sprintf('Žádná shoda pro %s %s', $method, $path));
        }

        return ($match->getHandler())($match);
    }

    public function generate(string $name, array $parameters = []): string
    {
        foreach ($this->routes as $route) {
            if ($route->getName() !== $name) {
                continue;
            }

            return $this->buildPath($route->getPattern(), $route->getDefaults(), $parameters);
        }

        throw new RuntimeException(sprintf('Routa "%s" nebyla nalezena.', $name));
    }

    /**
     * @param list<string> $methods
     */
    private function methodMatches(string $method, array $methods): bool
    {
        $method = strtoupper($method);
        foreach ($methods as $candidate) {
            if ($candidate === '*' || $candidate === $method) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{regex: string, variables: list<string>}
     */
    private function compilePattern(string $pattern, array $requirements): array
    {
        $variables = [];
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_-]*)\}/', static function (array $matches) use (&$variables, $requirements): string {
            $variable = $matches[1];
            $variables[] = $variable;
            $constraint = $requirements[$variable] ?? '[^/]+';
            return sprintf('(?P<%s>%s)', $variable, $constraint);
        }, $pattern);

        if ($regex === null) {
            throw new RuntimeException('Neplatný regulární výraz pro pattern: ' . $pattern);
        }

        $regex = '#^' . $regex . '$#u';

        return [
            'regex' => $regex,
            'variables' => $variables,
        ];
    }

    private function stripQueryString(string $path): string
    {
        if (($pos = strpos($path, '?')) !== false) {
            return substr($path, 0, $pos);
        }

        return $path;
    }

    private function stripBasePath(string $path): string
    {
        $path = '/' . ltrim($path, '/');

        if ($this->basePath === '') {
            return $path;
        }

        if (!str_starts_with($path, $this->basePath)) {
            return $path;
        }

        $stripped = substr($path, strlen($this->basePath));

        return $stripped === '' ? '/' : $stripped;
    }

    private function normalizePattern(string $pattern): string
    {
        $normalized = '/' . ltrim($pattern, '/');
        $normalized = preg_replace('#/+#', '/', $normalized);
        if (!is_string($normalized)) {
            throw new RuntimeException('Chyba při normalizaci patternu.');
        }

        return $normalized === '' ? '/' : $normalized;
    }

    /**
     * @param list<string>|string $methods
     * @return list<string>
     */
    private function normalizeMethods(array|string $methods): array
    {
        if (is_string($methods)) {
            $methods = [$methods];
        }

        if ($methods === []) {
            throw new InvalidArgumentException('Routa musí mít alespoň jednu HTTP metodu.');
        }

        return array_values(array_unique(array_map(static fn (string $method): string => strtoupper($method), $methods)));
    }

    private function buildPath(string $pattern, array $defaults, array $parameters): string
    {
        $replaced = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_-]*)\}/', static function (array $matches) use (&$parameters, $defaults): string {
            $parameter = $matches[1];
            if (array_key_exists($parameter, $parameters)) {
                $value = $parameters[$parameter];
                unset($parameters[$parameter]);
                return (string) $value;
            }

            if (array_key_exists($parameter, $defaults)) {
                return $defaults[$parameter];
            }

            throw new RuntimeException(sprintf('Chybí parametr "%s" pro generování URL.', $parameter));
        }, $pattern);

        if (!is_string($replaced)) {
            throw new RuntimeException('Nepodařilo se vygenerovat URL.');
        }

        if ($parameters !== []) {
            $query = http_build_query($parameters);
            if ($query !== '') {
                $replaced .= '?' . $query;
            }
        }

        if ($this->basePath !== '') {
            $replaced = rtrim($this->basePath, '/') . $replaced;
        }

        return $replaced;
    }
}

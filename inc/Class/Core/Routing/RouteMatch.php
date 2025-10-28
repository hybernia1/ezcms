<?php

declare(strict_types=1);

namespace Core\Routing;

final class RouteMatch
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        private readonly Route $route,
        private readonly array $parameters,
    ) {
    }

    public function getRoute(): Route
    {
        return $this->route;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getHandler(): callable
    {
        return $this->route->getHandler();
    }
}

<?php

declare(strict_types=1);

namespace Core\Routing;

final class Route
{
    /**
     * @param list<string> $methods
     * @param array<string, string> $defaults
     * @param array<string, string> $requirements
     */
    public function __construct(
        private readonly array $methods,
        private readonly string $pattern,
        private $handler,
        private readonly ?string $name = null,
        private readonly array $defaults = [],
        private readonly array $requirements = [],
        private readonly int $priority = 0,
    ) {
    }

    /**
     * @return list<string>
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function getHandler(): callable
    {
        return $this->handler;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return array<string, string>
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }

    /**
     * @return array<string, string>
     */
    public function getRequirements(): array
    {
        return $this->requirements;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }
}

<?php

declare(strict_types=1);

namespace Core\App;

use RuntimeException;

/**
 * Lightweight dependency container tailored for EZCMS builders.
 */
final class Container
{
    /**
     * @var array<string, callable(self):mixed>
     */
    private array $definitions = [];

    /**
     * @var array<string, mixed>
     */
    private array $instances = [];

    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(private array $parameters = [])
    {
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->definitions) || array_key_exists($id, $this->instances);
    }

    public function set(string $id, callable $factory): void
    {
        $this->definitions[$id] = $factory;
        unset($this->instances[$id]);
    }

    public function setInstance(string $id, mixed $instance): void
    {
        $this->instances[$id] = $instance;
        unset($this->definitions[$id]);
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (!array_key_exists($id, $this->definitions)) {
            throw new RuntimeException(sprintf('Služba "%s" nebyla nalezena v kontejneru.', $id));
        }

        $factory = $this->definitions[$id];
        $service = $factory($this);
        $this->instances[$id] = $service;

        return $service;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function mergeParameters(array $parameters): void
    {
        $this->parameters = array_replace_recursive($this->parameters, $parameters);
    }

    public function getParameter(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = $this->parameters;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}

<?php

declare(strict_types=1);

namespace Core\Http;

final class Request
{
    /**
     * @param array<string, mixed> $queryParams
     * @param array<string, mixed> $parsedBody
     * @param array<string, mixed> $server
     * @param array<string, mixed> $cookies
     * @param array<string, mixed> $files
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $queryParams = [],
        private readonly array $parsedBody = [],
        private readonly array $server = [],
        private readonly array $cookies = [],
        private readonly array $files = [],
        private readonly array $attributes = [],
    ) {
    }

    public static function fromGlobals(): self
    {
        $server = $_SERVER ?? [];
        $method = strtoupper($server['REQUEST_METHOD'] ?? 'GET');
        $uri = $server['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $queryString = parse_url($uri, PHP_URL_QUERY) ?: '';

        $queryParams = [];
        if (is_string($queryString) && $queryString !== '') {
            parse_str($queryString, $queryParams);
        }

        $parsedBody = $_POST ?? [];

        return new self(
            $method,
            is_string($path) ? $path : '/',
            is_array($queryParams) ? $queryParams : [],
            is_array($parsedBody) ? $parsedBody : [],
            $server,
            $_COOKIE ?? [],
            $_FILES ?? [],
        );
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path === '' ? '/' : $this->path;
    }

    /**
     * @return array<string, mixed>
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function getQueryParam(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParsedBody(): array
    {
        return $this->parsedBody;
    }

    public function getPost(string $key, mixed $default = null): mixed
    {
        return $this->parsedBody[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function getServerParams(): array
    {
        return $this->server;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute(string $name, mixed $value): self
    {
        $attributes = $this->attributes;
        $attributes[$name] = $value;

        return new self(
            $this->method,
            $this->path,
            $this->queryParams,
            $this->parsedBody,
            $this->server,
            $this->cookies,
            $this->files,
            $attributes,
        );
    }

    public function withParsedBody(array $parsedBody): self
    {
        return new self(
            $this->method,
            $this->path,
            $this->queryParams,
            $parsedBody,
            $this->server,
            $this->cookies,
            $this->files,
            $this->attributes,
        );
    }
}

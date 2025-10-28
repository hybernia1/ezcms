<?php

declare(strict_types=1);

namespace Core\Http;

use JsonException;
use RuntimeException;

final class Response
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly string $body = '',
        private readonly int $statusCode = 200,
        private readonly array $headers = [],
    ) {
    }

    public static function html(string $content, int $statusCode = 200, array $headers = []): self
    {
        $headers = ['Content-Type' => 'text/html; charset=UTF-8'] + $headers;

        return new self($content, $statusCode, $headers);
    }

    /**
     * @param array<mixed> $data
     */
    public static function json(array $data, int $statusCode = 200, array $headers = []): self
    {
        try {
            $body = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        } catch (JsonException $exception) {
            throw new RuntimeException('Nepodařilo se serializovat JSON odpověď.', 0, $exception);
        }

        $headers = ['Content-Type' => 'application/json; charset=UTF-8'] + $headers;

        return new self($body, $statusCode, $headers);
    }

    public static function redirect(string $location, int $statusCode = 302, array $headers = []): self
    {
        $headers = ['Location' => $location] + $headers;

        return new self('', $statusCode, $headers);
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);
            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value, true);
            }
        }

        echo $this->body;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withHeader(string $name, string $value): self
    {
        $headers = $this->headers;
        $headers[$name] = $value;

        return new self($this->body, $this->statusCode, $headers);
    }

    public function withStatus(int $statusCode): self
    {
        return new self($this->body, $statusCode, $this->headers);
    }
}

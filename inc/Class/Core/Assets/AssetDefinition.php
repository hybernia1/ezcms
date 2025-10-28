<?php

declare(strict_types=1);

namespace Core\Assets;

/**
 * Internal immutable DTO representing a single asset.
 */
final class AssetDefinition
{
    public const TYPE_STYLE  = 'style';
    public const TYPE_SCRIPT = 'script';

    public const SECTION_HEAD   = 'head';
    public const SECTION_FOOTER = 'footer';

    /**
     * @param string[] $dependencies
     * @param array<string, string|int|float|bool|null> $attributes
     */
    public function __construct(
        private readonly string $handle,
        private readonly string $type,
        private readonly string $uri,
        private readonly array $dependencies = [],
        private readonly array $attributes = [],
        private readonly ?string $version = null,
        private readonly string $section = self::SECTION_HEAD,
    ) {
    }

    public function getHandle(): string
    {
        return $this->handle;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * @return array<string, string|int|float|bool|null>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function getSection(): string
    {
        return $this->section;
    }

    public function withSection(string $section): self
    {
        return new self(
            $this->handle,
            $this->type,
            $this->uri,
            $this->dependencies,
            $this->attributes,
            $this->version,
            $section,
        );
    }
}

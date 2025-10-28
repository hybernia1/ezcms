<?php

declare(strict_types=1);

namespace Core\Assets;

use InvalidArgumentException;
use RuntimeException;

/**
 * Lightweight asset registry with dependency-aware rendering.
 *
 * Supports registering styles and scripts, enqueueing them per-request and
 * rendering HTML tags for the desired section (head/footer).
 */
final class AssetManager
{
    /**
     * @var array<string, AssetDefinition>
     */
    private array $registry = [];

    /**
     * @var array<string, list<string>> keyed by section (head/footer)
     */
    private array $enqueued = [];

    /**
     * Custom URI resolver, allows injecting CDN/base-path logic.
     *
     * @var callable|null
     */
    private $uriResolver = null;

    public function __construct(?callable $uriResolver = null)
    {
        if ($uriResolver !== null) {
            $this->setUriResolver($uriResolver);
        }
    }

    public function setUriResolver(callable $resolver): void
    {
        $this->uriResolver = $resolver;
    }

    /**
     * @param string[] $dependencies
     * @param array<string, string|int|float|bool|null> $attributes
     */
    public function registerStyle(
        string $handle,
        string $uri,
        array $dependencies = [],
        array $attributes = [],
        ?string $version = null,
        string $section = AssetDefinition::SECTION_HEAD,
    ): void {
        $this->register(
            new AssetDefinition(
                $handle,
                AssetDefinition::TYPE_STYLE,
                $uri,
                $dependencies,
                $attributes,
                $version,
                $section,
            ),
        );
    }

    /**
     * @param string[] $dependencies
     * @param array<string, string|int|float|bool|null> $attributes
     */
    public function registerScript(
        string $handle,
        string $uri,
        array $dependencies = [],
        array $attributes = [],
        ?string $version = null,
        string $section = AssetDefinition::SECTION_FOOTER,
    ): void {
        $this->register(
            new AssetDefinition(
                $handle,
                AssetDefinition::TYPE_SCRIPT,
                $uri,
                $dependencies,
                $attributes,
                $version,
                $section,
            ),
        );
    }

    public function enqueue(string $handle, ?string $section = null): void
    {
        $asset = $this->requireAsset($handle);

        if ($section !== null && $section !== $asset->getSection()) {
            $asset = $asset->withSection($section);
            $this->registry[$handle] = $asset;
        }

        $section = $asset->getSection();
        $this->enqueued[$section] ??= [];

        if (!in_array($handle, $this->enqueued[$section], true)) {
            $this->enqueued[$section][] = $handle;
        }
    }

    public function enqueueStyle(string $handle, ?string $section = null): void
    {
        $this->ensureType($handle, AssetDefinition::TYPE_STYLE);
        $this->enqueue($handle, $section);
    }

    public function enqueueScript(string $handle, ?string $section = null): void
    {
        $this->ensureType($handle, AssetDefinition::TYPE_SCRIPT);
        $this->enqueue($handle, $section);
    }

    /**
     * Render HTML tags for all enqueued styles located in the requested section.
     */
    public function renderStyles(string $section = AssetDefinition::SECTION_HEAD): string
    {
        return $this->renderByType(AssetDefinition::TYPE_STYLE, $section);
    }

    /**
     * Render HTML tags for all enqueued scripts located in the requested section.
     */
    public function renderScripts(string $section = AssetDefinition::SECTION_FOOTER): string
    {
        return $this->renderByType(AssetDefinition::TYPE_SCRIPT, $section);
    }

    private function ensureType(string $handle, string $expectedType): void
    {
        $asset = $this->requireAsset($handle);
        if ($asset->getType() !== $expectedType) {
            throw new InvalidArgumentException(sprintf(
                'Asset "%s" je registrován jako typ "%s", nikoliv "%s".',
                $handle,
                $asset->getType(),
                $expectedType,
            ));
        }
    }

    private function register(AssetDefinition $asset): void
    {
        $handle = $asset->getHandle();
        if ($handle === '') {
            throw new InvalidArgumentException('Handle assetu nesmí být prázdný.');
        }

        $this->registry[$handle] = $asset;
    }

    private function requireAsset(string $handle): AssetDefinition
    {
        if (!isset($this->registry[$handle])) {
            throw new RuntimeException(sprintf('Asset "%s" není registrován.', $handle));
        }

        return $this->registry[$handle];
    }

    private function renderByType(string $type, string $section): string
    {
        $handles = $this->enqueued[$section] ?? [];
        if ($handles === []) {
            return '';
        }

        $orderedHandles = $this->resolveOrder($handles);
        $html = [];

        foreach ($orderedHandles as $handle) {
            $asset = $this->registry[$handle];
            if ($asset->getType() !== $type || $asset->getSection() !== $section) {
                continue;
            }

            $uri = $this->resolveUri($asset);
            $attributes = $this->stringifyAttributes($asset->getAttributes());
            $versionSuffix = $asset->getVersion() !== null ? $this->buildVersionSuffix($uri, $asset->getVersion()) : $uri;

            if ($asset->getType() === AssetDefinition::TYPE_STYLE) {
                $html[] = sprintf('<link rel="stylesheet" href="%s"%s>', $versionSuffix, $attributes);
            } else {
                $html[] = sprintf('<script src="%s"%s></script>', $versionSuffix, $attributes);
            }
        }

        return implode(PHP_EOL, $html);
    }

    /**
     * @param list<string> $handles
     * @return list<string>
     */
    private function resolveOrder(array $handles): array
    {
        $resolved = [];
        $visiting = [];

        $visit = function (string $handle) use (&$resolved, &$visiting, &$visit): void {
            if (in_array($handle, $resolved, true)) {
                return;
            }
            if (in_array($handle, $visiting, true)) {
                throw new RuntimeException(sprintf('Kruhová závislost assetů v "%s".', $handle));
            }

            $visiting[] = $handle;
            $asset = $this->requireAsset($handle);

            foreach ($asset->getDependencies() as $dependency) {
                $visit($dependency);
            }

            $resolved[] = $handle;
            array_pop($visiting);
        };

        foreach ($handles as $handle) {
            $visit($handle);
        }

        return $resolved;
    }

    private function resolveUri(AssetDefinition $asset): string
    {
        $uri = $asset->getUri();
        if ($this->uriResolver !== null) {
            $uri = (string) ($this->uriResolver)($uri, $asset);
        }

        return $uri;
    }

    private function stringifyAttributes(array $attributes): string
    {
        if ($attributes === []) {
            return '';
        }

        $parts = [];
        foreach ($attributes as $name => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            if ($value === true) {
                $parts[] = sprintf(' %s', $name);
                continue;
            }

            $parts[] = sprintf(' %s="%s"', $name, htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        }

        return implode('', $parts);
    }

    private function buildVersionSuffix(string $uri, string $version): string
    {
        $delimiter = str_contains($uri, '?') ? '&' : '?';
        return $uri . $delimiter . 'v=' . rawurlencode($version);
    }
}

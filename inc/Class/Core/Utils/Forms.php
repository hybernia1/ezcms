<?php
declare(strict_types=1);

namespace Core\Utils;

/**
 * Helper utilities for building HTML forms in PHP templates.
 */
final class Forms
{
    private function __construct() {}

    /**
     * Render the opening tag of a form with optional attribute overrides.
     *
     * For HTTP verbs other than GET/POST remember to add a hidden spoofing
     * field via {@see Forms::methodField()}.
     */
    public static function open(string $action, string $method = 'post', array $attributes = []): string
    {
        $method = strtolower($method);
        $supported = ['get', 'post'];
        $formMethod = in_array($method, $supported, true) ? $method : 'post';
        $attributes = array_replace([
            'action' => $action,
            'method' => $formMethod,
        ], $attributes);

        $attr = self::attributes($attributes);
        return '<form' . ($attr === '' ? '' : ' ' . $attr) . '>';
    }

    /**
     * Render a closing form tag.
     */
    public static function close(): string
    {
        return '</form>';
    }

    /**
     * Render a label element.
     */
    public static function label(string $for, string $text, array $attributes = []): string
    {
        $attributes = array_replace(['for' => $for], $attributes);
        $attr = self::attributes($attributes);
        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<label' . ($attr === '' ? '' : ' ' . $attr) . '>' . $escaped . '</label>';
    }

    /**
     * Render a generic input tag.
     */
    public static function input(string $name, string $value = '', string $type = 'text', array $attributes = []): string
    {
        $id = $attributes['id'] ?? $name;
        $attributes = array_replace([
            'type' => $type,
            'name' => $name,
            'id' => $id,
            'value' => $value,
        ], $attributes);

        $attr = self::attributes($attributes);
        return '<input' . ($attr === '' ? '' : ' ' . $attr) . ' />';
    }

    /**
     * Render a hidden input field.
     */
    public static function hidden(string $name, string $value, array $attributes = []): string
    {
        return self::input($name, $value, 'hidden', $attributes);
    }

    /**
     * Render a checkbox input.
     */
    public static function checkbox(string $name, string $value = '1', bool $checked = false, array $attributes = []): string
    {
        if ($checked) {
            $attributes['checked'] = true;
        }

        return self::input($name, $value, 'checkbox', $attributes);
    }

    /**
     * Render a radio input field.
     */
    public static function radio(string $name, string $value, bool $checked = false, array $attributes = []): string
    {
        if ($checked) {
            $attributes['checked'] = true;
        }

        return self::input($name, $value, 'radio', $attributes);
    }

    /**
     * Render a textarea element.
     */
    public static function textarea(string $name, string $value = '', array $attributes = []): string
    {
        $id = $attributes['id'] ?? $name;
        $attributes = array_replace([
            'name' => $name,
            'id' => $id,
        ], $attributes);

        $attr = self::attributes($attributes);
        $escaped = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<textarea' . ($attr === '' ? '' : ' ' . $attr) . '>' . $escaped . '</textarea>';
    }

    /**
     * Render a select element.
     *
     * @param iterable<string|int, string|\Stringable> $options
     * @param string|string[]|null $selected
     */
    public static function select(string $name, iterable $options, string|array|null $selected = null, array $attributes = []): string
    {
        $id = $attributes['id'] ?? $name;
        $attributes = array_replace([
            'name' => $name,
            'id' => $id,
        ], $attributes);

        $selectedValues = self::normaliseSelected($selected);

        $optionsHtml = '';
        foreach ($options as $value => $label) {
            $optionAttr = ['value' => (string) $value];
            if (in_array((string) $value, $selectedValues, true)) {
                $optionAttr['selected'] = true;
            }

            $attr = self::attributes($optionAttr);
            $escapedLabel = htmlspecialchars((string) $label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $optionsHtml .= '<option' . ($attr === '' ? '' : ' ' . $attr) . '>' . $escapedLabel . '</option>';
        }

        $attr = self::attributes($attributes);
        return '<select' . ($attr === '' ? '' : ' ' . $attr) . '>' . $optionsHtml . '</select>';
    }

    /**
     * Render a submit button.
     */
    public static function submit(string $value, array $attributes = []): string
    {
        return self::input($attributes['name'] ?? 'submit', $value, 'submit', $attributes);
    }

    /**
     * Render a button element.
     */
    public static function button(string $text, array $attributes = []): string
    {
        $attributes = array_replace(['type' => 'button'], $attributes);
        $attr = self::attributes($attributes);
        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<button' . ($attr === '' ? '' : ' ' . $attr) . '>' . $escaped . '</button>';
    }

    /**
     * Render a hidden field with the desired HTTP method, useful for method spoofing.
     */
    public static function methodField(string $method): string
    {
        return self::hidden('_method', strtoupper($method));
    }

    /**
     * Render a hidden CSRF token field.
     */
    public static function csrfField(string $token, string $name = '_token'): string
    {
        return self::hidden($name, $token);
    }

    /**
     * Convert an array of attributes to a HTML attribute string.
     *
     * @param array<int|string, mixed> $attributes
     */
    public static function attributes(array $attributes): string
    {
        $parts = [];
        foreach ($attributes as $key => $value) {
            if (is_int($key)) {
                $key = $value;
                $value = true;
            }

            if ($value === null || $value === false) {
                continue;
            }

            $key = htmlspecialchars((string) $key, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            if ($value === true) {
                $parts[] = $key;
                continue;
            }

            if (is_array($value)) {
                $value = $key === 'class'
                    ? implode(' ', array_filter(array_map('strval', $value), static fn ($v) => $v !== ''))
                    : implode(' ', array_map('strval', $value));
            }

            $value = htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $parts[] = $key . '="' . $value . '"';
        }

        return implode(' ', $parts);
    }

    /**
     * @param string|string[]|null $selected
     * @return string[]
     */
    private static function normaliseSelected(string|array|null $selected): array
    {
        if ($selected === null) {
            return [];
        }

        if (is_array($selected)) {
            $values = array_map('strval', $selected);
            return array_values(array_unique($values));
        }

        return [(string) $selected];
    }
}

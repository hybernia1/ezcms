<?php
declare(strict_types=1);

namespace Core\Utils;

/**
 * Utility helpers for working with dates and times across the project.
 * Provides consistent creation and formatting helpers that wrap the native
 * DateTime API while defaulting to immutable instances.
 */
final class DateTimeFormatter
{
    private function __construct() {}

    /**
     * Create an immutable date-time instance for the provided time expression.
     */
    public static function create(string $time = 'now', ?\DateTimeZone $timezone = null): \DateTimeImmutable
    {
        if ($timezone !== null) {
            return new \DateTimeImmutable($time, $timezone);
        }

        return new \DateTimeImmutable($time);
    }

    /**
     * Shortcut for an immutable "now" instance, respecting optional timezone.
     */
    public static function now(?\DateTimeZone $timezone = null): \DateTimeImmutable
    {
        return self::create('now', $timezone);
    }

    /**
     * Format the provided date-time instance with an optional timezone override.
     */
    public static function format(\DateTimeInterface $dateTime, string $format, ?\DateTimeZone $timezone = null): string
    {
        if ($timezone !== null) {
            $dateTime = self::immutableFrom($dateTime)->setTimezone($timezone);
        }

        return $dateTime->format($format);
    }

    /**
     * Format year portion (YYYY) of the provided date.
     */
    public static function formatYear(\DateTimeInterface $dateTime): string
    {
        return self::format($dateTime, 'Y');
    }

    /**
     * Format month portion (MM) of the provided date.
     */
    public static function formatMonth(\DateTimeInterface $dateTime): string
    {
        return self::format($dateTime, 'm');
    }

    /**
     * Build a directory-friendly year/month string (e.g. "2025/10").
     */
    public static function formatYearMonth(\DateTimeInterface $dateTime, string $separator = '/'): string
    {
        return self::formatYear($dateTime) . $separator . self::formatMonth($dateTime);
    }

    private static function immutableFrom(\DateTimeInterface $dateTime): \DateTimeImmutable
    {
        return $dateTime instanceof \DateTimeImmutable
            ? $dateTime
            : \DateTimeImmutable::createFromInterface($dateTime);
    }
}

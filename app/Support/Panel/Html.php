<?php

namespace App\Support\Panel;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Small HTML helpers shared by the standalone TailAdmin panel. Only trusted,
 * hard coded markup is produced here; every dynamic value is escaped.
 */
class Html
{
    public static function badge(?string $text, string $variant = 'muted'): string
    {
        if (blank($text)) {
            return '<span class="text-gray-400">—</span>';
        }

        return sprintf('<span class="ta-badge-%s">%s</span>', $variant, e($text));
    }

    public static function status(bool $active): string
    {
        return $active
            ? self::badge('Aktif', 'success')
            : self::badge('Nonaktif', 'danger');
    }

    /**
     * @param  iterable<int, string>  $items
     */
    public static function badges(iterable $items, string $variant = 'brand'): string
    {
        $html = '';

        foreach ($items as $item) {
            $html .= self::badge((string) $item, $variant);
        }

        return $html === ''
            ? '<span class="text-gray-400">—</span>'
            : '<span class="inline-flex flex-wrap items-center gap-1">'.$html.'</span>';
    }

    public static function count(?int $value): string
    {
        return self::badge((string) (int) $value, 'muted');
    }

    public static function boolean(bool $value, string $true = 'Ya', string $false = 'Tidak'): string
    {
        return $value ? self::badge($true, 'success') : self::badge($false, 'muted');
    }

    public static function text(?string $value): string
    {
        return filled($value) ? e($value) : '<span class="text-gray-400">—</span>';
    }

    public static function truncate(?string $value, int $limit = 60): string
    {
        return filled($value) ? e(Str::limit($value, $limit)) : '<span class="text-gray-400">—</span>';
    }

    public static function date(mixed $value, string $format = 'd/m/Y'): string
    {
        if (blank($value)) {
            return '<span class="text-gray-400">—</span>';
        }

        $date = $value instanceof DateTimeInterface ? $value : Carbon::parse($value);

        return e($date->format($format));
    }
}

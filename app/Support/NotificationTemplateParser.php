<?php

namespace App\Support;

class NotificationTemplateParser
{
    /**
     * @param  array<string, scalar|null>  $vars
     */
    public static function render(?string $content, array $vars): string
    {
        if ($content === null || $content === '') {
            return '';
        }

        return (string) preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/',
            function (array $m) use ($vars) {
                $key = $m[1];

                return array_key_exists($key, $vars) ? (string) ($vars[$key] ?? '') : $m[0];
            },
            $content
        );
    }

    /**
     * Build ZNS template_data từ JSON biến Zalo.
     *
     * Format khuyến nghị (giống nội dung Zalo / Email — không cần map trung gian):
     *   {"customer_name":"{{recipient_name}}","amount":"{{amount_formatted}}"}
     *
     * Key = tên tham số trên mẫu ZNS đã duyệt.
     * Value = chuỗi có {{bien_crm}} (hoặc tên biến CRM thuần để tương thích cũ).
     *
     * @param  array<string, string>|null  $mapping
     * @param  array<string, scalar|null>  $vars
     * @return array<string, string>
     */
    public static function mapZaloParams(?array $mapping, array $vars): array
    {
        if (! $mapping) {
            return [];
        }

        $out = [];
        foreach ($mapping as $zaloKey => $value) {
            $value = (string) $value;
            if ($value === '') {
                $out[(string) $zaloKey] = '';

                continue;
            }

            // Có {{...}} → render như mẫu Email/Zalo
            if (str_contains($value, '{{')) {
                $out[(string) $zaloKey] = self::render($value, $vars);

                continue;
            }

            // Tương thích cũ: value = tên biến CRM (không ngoặc)
            if (array_key_exists($value, $vars)) {
                $out[(string) $zaloKey] = (string) ($vars[$value] ?? '');

                continue;
            }

            $out[(string) $zaloKey] = self::render($value, $vars);
        }

        return $out;
    }
}

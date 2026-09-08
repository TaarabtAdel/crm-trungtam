<?php

namespace App\Support;

use Illuminate\Http\Request;

class TenantHostResolver
{
    /**
     * Lấy slug tenant từ Host (subdomain). Trả null nếu không xác định được.
     */
    public static function resolve(Request $request, string $baseDomain): ?string
    {
        $host = strtolower($request->getHost());
        $baseDomain = strtolower($baseDomain);

        if ($host === $baseDomain || $host === 'www.'.$baseDomain) {
            return null;
        }

        if (str_ends_with($host, '.'.$baseDomain)) {
            $prefix = substr($host, 0, -strlen('.'.$baseDomain));
            $slug = explode('.', $prefix)[0];

            if ($slug !== '' && $slug !== 'www') {
                return $slug;
            }

            return null;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return null;
        }

        // Host dạng a.b.c (local test / IP domain) — lấy label đầu
        $parts = explode('.', $host);
        if (count($parts) >= 3) {
            $slug = $parts[0] === 'www' ? ($parts[1] ?? null) : $parts[0];

            return ($slug !== null && $slug !== '') ? $slug : null;
        }

        return null;
    }
}

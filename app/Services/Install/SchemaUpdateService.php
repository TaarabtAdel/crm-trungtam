<?php

namespace App\Services\Install;

use App\Models\Setting;

class SchemaUpdateService
{
    /**
     * @var array<string, class-string>
     */
    protected array $versions = [
        '1.0' => \App\Models\Versions\Ver1::class,
    ];

    public function currentVersion(): string
    {
        try {
            return (string) Setting::get('app_schema_version', '0');
        } catch (\Throwable) {
            return '0';
        }
    }

    public function targetVersion(): string
    {
        return (string) config('app.schema_version', '1.0');
    }

    /**
     * @return array{success:bool,message:string,from:string,to:string}
     */
    public function run(?string $target = null): array
    {
        $target = $target ?? $this->targetVersion();
        $current = $this->currentVersion();
        $keys = array_keys($this->versions);
        usort($keys, 'version_compare');

        $applied = [];
        foreach ($keys as $version) {
            if (version_compare($version, $current, '<=')) {
                continue;
            }
            if (version_compare($version, $target, '>')) {
                break;
            }

            $class = $this->versions[$version];
            $result = $class::doUpdate();
            $ok = is_array($result) ? ($result['success'] ?? false) : (bool) $result;
            if (! $ok) {
                $detail = is_array($result) ? (string) ($result['message'] ?? '') : '';

                return [
                    'success' => false,
                    'message' => 'Cập nhật schema thất bại tại phiên bản '.$version
                        .($detail !== '' ? ': '.$detail : '.'),
                    'from' => $current,
                    'to' => $version,
                ];
            }
            $applied[] = $version;
            Setting::set('app_schema_version', $version);
        }

        return [
            'success' => true,
            'message' => $applied === []
                ? 'Schema đã ở phiên bản mới nhất ('.$this->currentVersion().').'
                : 'Đã cập nhật schema: '.implode(' → ', $applied),
            'from' => $current,
            'to' => $this->currentVersion(),
        ];
    }
}

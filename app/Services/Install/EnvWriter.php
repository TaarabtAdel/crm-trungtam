<?php

namespace App\Services\Install;

class EnvWriter
{
    public function path(): string
    {
        return base_path('.env');
    }

    public function ensureExists(): void
    {
        if (is_file($this->path())) {
            return;
        }

        $example = base_path('.env.example');
        if (is_file($example)) {
            copy($example, $this->path());

            return;
        }

        file_put_contents($this->path(), "APP_NAME=\"CRM Trung Tam\"\nAPP_ENV=production\nAPP_KEY=\nAPP_DEBUG=false\n\nDB_CONNECTION=mysql\n");
    }

    /**
     * @param  array<string, string|int|bool|null>  $values
     */
    public function setMany(array $values): void
    {
        $this->ensureExists();
        $content = file_get_contents($this->path()) ?: '';

        foreach ($values as $key => $value) {
            $content = $this->replaceKey($content, (string) $key, $this->stringify($value));
        }

        if (file_put_contents($this->path(), $content) === false) {
            throw new \RuntimeException('Không ghi được file .env. Kiểm tra quyền ghi thư mục gốc dự án.');
        }
    }

    protected function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $str = (string) $value;
        if ($str === '') {
            return '';
        }

        if (preg_match('/[\s#"\'\\\\]/', $str)) {
            return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $str).'"';
        }

        return $str;
    }

    protected function replaceKey(string $content, string $key, string $value): string
    {
        $line = $key.'='.$value;
        $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

        if (preg_match($pattern, $content)) {
            return preg_replace($pattern, $line, $content, 1) ?? $content;
        }

        return rtrim($content)."\n".$line."\n";
    }
}

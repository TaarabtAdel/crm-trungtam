<?php

namespace App\Services;

use App\Support\TenantContext;
use App\Support\TenantStorage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DatabaseBackupService
{
    /**
     * @return array{filename: string, path: string, size: int}
     */
    public function create(): array
    {
        $dir = TenantStorage::backupsPath();
        $dbName = (string) (TenantContext::databaseName() ?: config('database.connections.mysql.database'));
        $slug = TenantStorage::slug();
        $filename = sprintf(
            'backup_%s_%s_%s.sql',
            $slug,
            preg_replace('/[^a-zA-Z0-9_-]/', '_', $dbName) ?: 'db',
            now()->format('Ymd_His')
        );
        $path = $dir.DIRECTORY_SEPARATOR.$filename;

        $sql = $this->buildDump($dbName);
        File::put($path, $sql);

        return [
            'filename' => $filename,
            'path' => $path,
            'size' => (int) filesize($path),
        ];
    }

    /**
     * @return list<array{filename: string, size: int, modified_at: string}>
     */
    public function list(): array
    {
        $dir = TenantStorage::backupsPath();
        $files = File::glob($dir.DIRECTORY_SEPARATOR.'*.sql') ?: [];

        rsort($files);

        return array_values(array_map(function (string $file) {
            return [
                'filename' => basename($file),
                'size' => (int) filesize($file),
                'modified_at' => date('Y-m-d H:i:s', filemtime($file)),
            ];
        }, $files));
    }

    public function absolutePath(string $filename): ?string
    {
        $filename = basename($filename);
        if (! str_ends_with(strtolower($filename), '.sql')) {
            return null;
        }

        $path = TenantStorage::backupsPath().DIRECTORY_SEPARATOR.$filename;
        if (! is_file($path)) {
            return null;
        }

        return $path;
    }

    public function delete(string $filename): bool
    {
        $path = $this->absolutePath($filename);
        if (! $path) {
            return false;
        }

        return File::delete($path);
    }

    protected function buildDump(string $dbName): string
    {
        $out = [];
        $out[] = '-- CRM Trung tâm SQL backup';
        $out[] = '-- Tenant: '.TenantStorage::slug();
        $out[] = '-- Database: '.$dbName;
        $out[] = '-- Generated at: '.now()->toDateTimeString();
        $out[] = 'SET NAMES utf8mb4;';
        $out[] = 'SET FOREIGN_KEY_CHECKS=0;';
        $out[] = '';

        $tables = DB::select('SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\'');
        $key = 'Tables_in_'.$dbName;
        // MySQL may return different key casing
        $tableNames = [];
        foreach ($tables as $row) {
            $arr = (array) $row;
            $name = $arr[$key] ?? $arr[array_key_first($arr)] ?? null;
            if ($name) {
                $tableNames[] = $name;
            }
        }

        sort($tableNames);

        foreach ($tableNames as $table) {
            $create = DB::selectOne('SHOW CREATE TABLE `'.str_replace('`', '``', $table).'`');
            $createSql = ((array) $create)['Create Table'] ?? ((array) $create)['Create View'] ?? null;
            if (! $createSql) {
                continue;
            }

            $out[] = '-- --------------------------------------------------------';
            $out[] = '-- Table: '.$table;
            $out[] = '-- --------------------------------------------------------';
            $out[] = 'DROP TABLE IF EXISTS `'.str_replace('`', '``', $table).'`;';
            $out[] = $createSql.';';
            $out[] = '';

            $rows = DB::table($table)->get();
            if ($rows->isEmpty()) {
                continue;
            }

            foreach ($rows->chunk(100) as $chunk) {
                $values = [];
                foreach ($chunk as $row) {
                    $vals = [];
                    foreach ((array) $row as $value) {
                        $vals[] = $this->sqlValue($value);
                    }
                    $values[] = '('.implode(',', $vals).')';
                }

                $columns = array_map(
                    fn ($c) => '`'.str_replace('`', '``', $c).'`',
                    array_keys((array) $chunk->first())
                );

                $out[] = 'INSERT INTO `'.str_replace('`', '``', $table).'` ('.implode(',', $columns).') VALUES';
                $out[] = implode(",\n", $values).';';
                $out[] = '';
            }
        }

        $out[] = 'SET FOREIGN_KEY_CHECKS=1;';
        $out[] = '';

        return implode("\n", $out);
    }

    protected function sqlValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        $string = (string) $value;

        return "'".str_replace(
            ["\\", "\0", "\n", "\r", "'", '"', "\x1a"],
            ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'],
            $string
        )."'";
    }
}

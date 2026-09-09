<?php

namespace App\Services\Install;

use PDO;
use PDOException;

class DatabaseTester
{
    /**
     * @param  array{host:string,port:int|string,database:string,username:string,password?:string}  $config
     * @return array{ok:bool,message:string}
     */
    public function test(array $config): array
    {
        $host = $config['host'] ?? '127.0.0.1';
        $port = (int) ($config['port'] ?? 3306);
        $database = $config['database'] ?? '';
        $username = $config['username'] ?? '';
        $password = (string) ($config['password'] ?? '');

        if ($database === '' || $username === '') {
            return ['ok' => false, 'message' => 'Thiếu tên database hoặc username.'];
        }

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $database);

        try {
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 8,
            ]);
            $pdo->query('SELECT 1');

            return ['ok' => true, 'message' => 'Kết nối MySQL thành công.'];
        } catch (PDOException $e) {
            return [
                'ok' => false,
                'message' => 'Không kết nối được MySQL: '.$e->getMessage(),
            ];
        }
    }
}

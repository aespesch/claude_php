<?php
class Config {
    private static $apiKey = null;
    private static $dbConfig = null;

    public static function loadEnv($filePath = '.env') {
        if (!file_exists($filePath)) return false;

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, '"\'');
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
        return true;
    }

    public static function getApiKey() {
        if (self::$apiKey === null) {
            self::$apiKey = getenv('API_KEY') ?: ($_ENV['API_KEY'] ?? ($_SERVER['API_KEY'] ?? null));
        }
        return self::$apiKey;
    }

    public static function getDbConfig() {
        if (self::$dbConfig === null) {
            self::$dbConfig = [
                'host' => getenv('database') ?: ($_ENV['database'] ?? 'localhost'),
                'user' => getenv('user') ?: ($_ENV['user'] ?? 'root'),
                'password' => getenv('pwd') ?: ($_ENV['pwd'] ?? ''),
                'database' => getenv('dbname') ?: ($_ENV['dbname'] ?? 'claude_chat')
            ];
        }
        return self::$dbConfig;
    }
}
<?php
class DatabaseConfig {
    private static $instance = null;
    private $connection = null;

    private $host;
    private $username;
    private $password;
    private $database;
    private $port;
    private $dbType;

    private function __construct() {
        $this->loadConfiguration();
    }

    private function loadConfiguration() {
        if (file_exists(__DIR__ . '/../.env')) {
            $env = parse_ini_file(__DIR__ . '/../.env');
            foreach ($env as $key => $value) {
                if (!getenv($key)) {
                    putenv("$key=$value");
                }
            }
        }

        if (getenv('DATABASE_URL')) {
            $this->parseConnectionString(getenv('DATABASE_URL'));
        }
        elseif (getenv('DB_HOST')) {
            $this->dbType = getenv('DB_TYPE') ?: 'mysql';
            $this->host = getenv('DB_HOST');
            $this->username = getenv('DB_USER');
            $this->password = getenv('DB_PASSWORD') ?: '';
            $this->database = getenv('DB_NAME');
            $this->port = getenv('DB_PORT') ?: ($this->dbType === 'pgsql' ? 5432 : 3306);
        }
        elseif (file_exists(__DIR__ . '/../config_db.php')) {
            require_once __DIR__ . '/../config_db.php';

            if (defined('DB_HOST')) {
                $this->dbType = defined('DB_TYPE') ? DB_TYPE : 'mysql';
                $this->host = DB_HOST;
                $this->username = DB_USER;
                $this->password = DB_PASSWORD;
                $this->database = DB_NAME;
                $this->port = defined('DB_PORT') ? DB_PORT : ($this->dbType === 'pgsql' ? 5432 : 3306);
            } else {
                global $servername, $username, $password, $dbname;
                $this->dbType = 'mysql';
                $this->host = $servername ?? 'localhost';
                $this->username = $username ?? 'root';
                $this->password = $password ?? '';
                $this->database = $dbname ?? 'evolentra';
                $this->port = 3306;
            }
        }
        else {
            throw new Exception('Database configuration not found. Please set environment variables or create .env file');
        }
    }

    private function parseConnectionString($url) {
        $parsed = parse_url($url);

        $scheme = $parsed['scheme'] ?? 'mysql';
        $this->dbType = ($scheme === 'postgres' || $scheme === 'postgresql') ? 'pgsql' : 'mysql';

        $this->host = $parsed['host'] ?? 'localhost';
        $this->username = $parsed['user'] ?? 'root';
        $this->password = $parsed['pass'] ?? '';
        $this->database = ltrim($parsed['path'] ?? '', '/');
        $this->port = $parsed['port'] ?? ($this->dbType === 'pgsql' ? 5432 : 3306);
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        if ($this->connection === null) {
            if ($this->dbType === 'pgsql') {
                return $this->getPDOConnection();
            } else {
                return $this->getMySQLiConnection();
            }
        }

        return $this->connection;
    }

    private function getMySQLiConnection() {
        try {
            $this->connection = new mysqli(
                $this->host,
                $this->username,
                $this->password,
                $this->database,
                $this->port
            );

            if ($this->connection->connect_error) {
                throw new Exception('Connection failed: ' . $this->connection->connect_error);
            }

            $this->connection->set_charset('utf8mb4');

        } catch (Exception $e) {
            error_log('Database connection error: ' . $e->getMessage());
            throw $e;
        }

        return $this->connection;
    }

    public function getPDOConnection() {
        try {
            if ($this->dbType === 'pgsql') {
                $dsn = sprintf(
                    'pgsql:host=%s;port=%d;dbname=%s',
                    $this->host,
                    $this->port,
                    $this->database
                );
            } else {
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                    $this->host,
                    $this->port,
                    $this->database
                );
            }

            $pdo = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            return $pdo;

        } catch (PDOException $e) {
            error_log('PDO connection error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getHost() { return $this->host; }
    public function getUsername() { return $this->username; }
    public function getDatabase() { return $this->database; }
    public function getPort() { return $this->port; }
    public function getDbType() { return $this->dbType; }

    public function testConnection() {
        try {
            $conn = $this->getConnection();
            return [
                'success' => true,
                'message' => 'Database connection successful',
                'host' => $this->host,
                'database' => $this->database,
                'type' => $this->dbType
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'host' => $this->host,
                'database' => $this->database,
                'type' => $this->dbType
            ];
        }
    }

    public function close() {
        if ($this->connection !== null) {
            if ($this->connection instanceof mysqli) {
                $this->connection->close();
            }
            $this->connection = null;
        }
    }

    private function __clone() {}

    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

function getDbConnection() {
    return DatabaseConfig::getInstance()->getConnection();
}

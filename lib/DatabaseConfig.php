<?php
/**
 * Database Configuration Class
 * 
 * Handles database configuration for both local development and production environments.
 * In production, reads from environment variables set by DigitalOcean.
 * In development, falls back to local config_db.php file.
 */

class DatabaseConfig {
    private static $instance = null;
    private $connection = null;
    
    private $host;
    private $username;
    private $password;
    private $database;
    private $port;
    
    private function __construct() {
        $this->loadConfiguration();
    }
    
    /**
     * Load database configuration from environment or local file
     */
    private function loadConfiguration() {
        // Check if DATABASE_URL is set (DigitalOcean format)
        if (getenv('DATABASE_URL')) {
            $this->parseConnectionString(getenv('DATABASE_URL'));
        }
        // Check for individual environment variables
        elseif (getenv('DB_HOST')) {
            $this->host = getenv('DB_HOST');
            $this->username = getenv('DB_USER');
            $this->password = getenv('DB_PASSWORD');
            $this->database = getenv('DB_NAME');
            $this->port = getenv('DB_PORT') ?: 3306;
        }
        // Fallback to local config_db.php for development
        elseif (file_exists(__DIR__ . '/../config_db.php')) {
            require_once __DIR__ . '/../config_db.php';
            
            // Assuming config_db.php defines these variables
            if (defined('DB_HOST')) {
                $this->host = DB_HOST;
                $this->username = DB_USER;
                $this->password = DB_PASSWORD;
                $this->database = DB_NAME;
                $this->port = defined('DB_PORT') ? DB_PORT : 3306;
            } else {
                // Legacy variable names
                global $servername, $username, $password, $dbname;
                $this->host = $servername ?? 'localhost';
                $this->username = $username ?? 'root';
                $this->password = $password ?? '';
                $this->database = $dbname ?? 'evolentra';
                $this->port = 3306;
            }
        }
        else {
            throw new Exception('Database configuration not found. Please set environment variables or create config_db.php');
        }
    }
    
    /**
     * Parse DATABASE_URL connection string
     * Format: mysql://username:password@host:port/database
     */
    private function parseConnectionString($url) {
        $parsed = parse_url($url);
        
        $this->host = $parsed['host'] ?? 'localhost';
        $this->username = $parsed['user'] ?? 'root';
        $this->password = $parsed['pass'] ?? '';
        $this->database = ltrim($parsed['path'] ?? '', '/');
        $this->port = $parsed['port'] ?? 3306;
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get database connection
     */
    public function getConnection() {
        if ($this->connection === null) {
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
                
                // Set charset to utf8mb4 for emoji support
                $this->connection->set_charset('utf8mb4');
                
            } catch (Exception $e) {
                error_log('Database connection error: ' . $e->getMessage());
                throw $e;
            }
        }
        
        return $this->connection;
    }
    
    /**
     * Get PDO connection (for libraries that require PDO)
     */
    public function getPDOConnection() {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $this->host,
                $this->port,
                $this->database
            );
            
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
    
    /**
     * Get configuration values
     */
    public function getHost() { return $this->host; }
    public function getUsername() { return $this->username; }
    public function getDatabase() { return $this->database; }
    public function getPort() { return $this->port; }
    
    /**
     * Test database connection
     */
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            return [
                'success' => true,
                'message' => 'Database connection successful',
                'host' => $this->host,
                'database' => $this->database
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'host' => $this->host,
                'database' => $this->database
            ];
        }
    }
    
    /**
     * Close connection
     */
    public function close() {
        if ($this->connection !== null) {
            $this->connection->close();
            $this->connection = null;
        }
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Helper function for backward compatibility
function getDbConnection() {
    return DatabaseConfig::getInstance()->getConnection();
}

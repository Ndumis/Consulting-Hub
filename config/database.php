<?php
require_once(__DIR__ . '/../includes/functions.php');
require_once(__DIR__ . '/app.php');

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    public $conn;

    public function __construct() {
        $this->host     = DB_HOST;
        $this->db_name  = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASSWORD;
        $this->port     = DB_PORT;
    }

    public function getConnection() {
        $this->conn = null;
        
        try {
            // Build MySQL DSN with timeout options
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";
            
            // PDO connection options for better timeout handling
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 10,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                PDO::ATTR_PERSISTENT => false,
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            return $this->conn;
            
        } catch (Exception $exception) {
            // Log the detailed error for debugging
            error_log("MySQL database connection failed: " . $exception->getMessage());
            
            // Try alternative connection method without database name (in case DB doesn't exist)
            try {
                $alt_dsn = "mysql:host={$this->host};port={$this->port};charset=utf8mb4";
                $alt_conn = new PDO($alt_dsn, $this->username, $this->password, $options);
                
                // Create database if it doesn't exist
                $alt_conn->exec("CREATE DATABASE IF NOT EXISTS `{$this->db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                
                // Now connect to the specific database
                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
                error_log("MySQL connection successful after creating database");
                return $this->conn;
                
            } catch (Exception $alt_exception) {
                error_log("Alternative MySQL connection also failed: " . $alt_exception->getMessage());
                die("Database connection failed. Unable to connect to MySQL database at " . $this->host . ". Please check your network connection and database server status.");
            }
        }
    }

    public function createDatabase() {
        try {
            // Connect without specifying database to create it
            $dsn = "mysql:host={$this->host};port={$this->port};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 10
            ];
            
            $temp_connection = new PDO($dsn, $this->username, $this->password, $options);
            
            // Create database if it doesn't exist
            $sql = "CREATE DATABASE IF NOT EXISTS `{$this->db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            $temp_connection->exec($sql);
            
            return true;
            
        } catch (Exception $exception) {
            error_log("MySQL database creation failed: " . $exception->getMessage());
            return false;
        }
    }
    
    public function closeConnection() {
        if ($this->conn) {
            $this->conn = null;
        }
    }
}
?>
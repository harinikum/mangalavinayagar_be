<?php
// include("../config/config.php");
include("../config/config.php");

class Database {

    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    public $connection;

    public function getConnection() {
        $this->connection = null;
    
        try {
            $this->connection = new mysqli($this->host, $this->username, $this->password, $this->db_name);
    
            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }
        } catch (Exception $exception) {
            error_log("Database connection error: " . $exception->getMessage(), 3, "../logs/error.log");
        }
    
        return $this->connection;
    }
    
}

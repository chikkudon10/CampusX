<?php
/**
 * Database Class
 * CampusX - College Management System
 * Handles all database operations using mysqli
 */

class Database {
    private $connection;
    private $host;
    private $user;
    private $pass;
    private $dbname;
    
    /**
     * Constructor - Initialize database credentials
     */
    public function __construct() {
        $this->host = DB_HOST;
        $this->user = DB_USER;
        $this->pass = DB_PASS;
        $this->dbname = DB_NAME;
    }
    
    /**
     * Connect to database
     */
    public function connect() {
        if ($this->connection === null) {
            $this->connection = new mysqli($this->host, $this->user, $this->pass, $this->dbname);
            
            if ($this->connection->connect_error) {
                error_log("Database Connection Error: " . $this->connection->connect_error);
                die("Database connection failed. Please contact administrator.");
            }
            
            $this->connection->set_charset(DB_CHARSET);
        }
        
        return $this->connection;
    }
    
    /**
     * Get connection
     */
    public function getConnection() {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }
    
    /**
     * Execute SELECT query
     */
    public function select($query, $params = [], $types = "") {
        $conn = $this->getConnection();
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            error_log("Query Preparation Failed: " . $conn->error);
            return false;
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        $stmt->close();
        return $data;
    }
    
    /**
     * Execute SELECT query and return single row
     */
    public function selectOne($query, $params = [], $types = "") {
        $result = $this->select($query, $params, $types);
        return !empty($result) ? $result[0] : null;
    }
    
    /**
     * Execute INSERT query
     */
    public function insert($query, $params = [], $types = "") {
        $conn = $this->getConnection();
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            error_log("Query Preparation Failed: " . $conn->error);
            return false;
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $result = $stmt->execute();
        $insertId = $stmt->insert_id;
        
        $stmt->close();
        
        return $result ? $insertId : false;
    }
    
    /**
     * Execute UPDATE query
     */
    public function update($query, $params = [], $types = "") {
        $conn = $this->getConnection();
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            error_log("Query Preparation Failed: " . $conn->error);
            return false;
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $result = $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        
        $stmt->close();
        
        return $result ? $affectedRows : false;
    }
    
    /**
     * Execute DELETE query
     */
    public function delete($query, $params = [], $types = "") {
        return $this->update($query, $params, $types);
    }
    
    /**
     * Execute custom query
     */
    public function query($query) {
        $conn = $this->getConnection();
        $result = $conn->query($query);
        
        if (!$result) {
            error_log("Query Execution Failed: " . $conn->error);
            return false;
        }
        
        return $result;
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->getConnection()->insert_id;
    }
    
    /**
     * Count rows
     */
    public function count($table, $where = "", $params = [], $types = "") {
        $query = "SELECT COUNT(*) as total FROM $table";
        
        if (!empty($where)) {
            $query .= " WHERE $where";
        }
        
        $result = $this->selectOne($query, $params, $types);
        return $result ? (int)$result['total'] : 0;
    }
    
    /**
     * Check if record exists
     */
    public function exists($table, $where, $params = [], $types = "") {
        return $this->count($table, $where, $params, $types) > 0;
    }
    
    /**
     * Get all records from table
     */
    public function getAll($table, $where = "", $params = [], $types = "", $orderBy = "", $limit = "") {
        $query = "SELECT * FROM $table";
        
        if (!empty($where)) {
            $query .= " WHERE $where";
        }
        
        if (!empty($orderBy)) {
            $query .= " ORDER BY $orderBy";
        }
        
        if (!empty($limit)) {
            $query .= " LIMIT $limit";
        }
        
        return $this->select($query, $params, $types);
    }
    
    /**
     * Get single record by ID
     */
    public function getById($table, $id, $idColumn = 'id') {
        $query = "SELECT * FROM $table WHERE $idColumn = ? LIMIT 1";
        return $this->selectOne($query, [$id], 'i');
    }
    
    /**
     * Insert record into table
     */
    public function insertRecord($table, $data) {
        $columns = array_keys($data);
        $values = array_values($data);
        
        $columnList = implode(', ', $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        
        $query = "INSERT INTO $table ($columnList) VALUES ($placeholders)";
        
        // Determine types
        $types = '';
        foreach ($values as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        
        return $this->insert($query, $values, $types);
    }
    
    /**
     * Update record in table
     */
    public function updateRecord($table, $data, $where, $whereParams = []) {
        $setClause = [];
        $values = [];
        
        foreach ($data as $column => $value) {
            $setClause[] = "$column = ?";
            $values[] = $value;
        }
        
        $values = array_merge($values, $whereParams);
        
        $query = "UPDATE $table SET " . implode(', ', $setClause) . " WHERE $where";
        
        // Determine types
        $types = '';
        foreach ($values as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        
        return $this->update($query, $values, $types);
    }
    
    /**
     * Delete record from table
     */
    public function deleteRecord($table, $where, $params = []) {
        $query = "DELETE FROM $table WHERE $where";
        
        $types = '';
        foreach ($params as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } else {
                $types .= 's';
            }
        }
        
        return $this->delete($query, $params, $types);
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->getConnection()->begin_transaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->getConnection()->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->getConnection()->rollback();
    }
    
    /**
     * Escape string
     */
    public function escape($string) {
        return $this->getConnection()->real_escape_string($string);
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
     * Destructor - Close connection
     */
    public function __destruct() {
        $this->close();
    }
}
?>
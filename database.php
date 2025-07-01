<?php
// Database connection and operations for Form Builder

class FormBuilderDB {
    private $conn;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        $host = $_ENV['PGHOST'] ?? 'localhost';
        $port = $_ENV['PGPORT'] ?? '5432';
        $dbname = $_ENV['PGDATABASE'] ?? 'postgres';
        $user = $_ENV['PGUSER'] ?? 'postgres';
        $password = $_ENV['PGPASSWORD'] ?? '';
        
        try {
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
            $this->conn = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ]);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    public function getAllForms() {
        $stmt = $this->conn->prepare("SELECT * FROM local_formbuilder_forms ORDER BY timemodified DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getFormById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM local_formbuilder_forms WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function saveForm($data) {
        if (isset($data['id']) && $data['id'] > 0) {
            // Update existing form
            $stmt = $this->conn->prepare("UPDATE local_formbuilder_forms SET name = ?, description = ?, formdata = ?, settings = ?, timemodified = ? WHERE id = ?");
            $stmt->execute([
                $data['name'],
                $data['description'],
                $data['formdata'],
                $data['settings'],
                time(),
                $data['id']
            ]);
            return $data['id'];
        } else {
            // Create new form
            $stmt = $this->conn->prepare("INSERT INTO local_formbuilder_forms (name, description, formdata, settings, timecreated, timemodified) VALUES (?, ?, ?, ?, ?, ?) RETURNING id");
            $stmt->execute([
                $data['name'],
                $data['description'],
                $data['formdata'],
                $data['settings'],
                time(),
                time()
            ]);
            $result = $stmt->fetch();
            return $result->id;
        }
    }
    
    public function deleteForm($id) {
        $stmt = $this->conn->prepare("DELETE FROM local_formbuilder_forms WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    public function saveSubmission($formid, $data) {
        $stmt = $this->conn->prepare("INSERT INTO local_formbuilder_submissions (formid, submissiondata, timecreated) VALUES (?, ?, ?)");
        return $stmt->execute([
            $formid,
            json_encode($data),
            time()
        ]);
    }
    
    public function getFormSubmissions($formid) {
        $stmt = $this->conn->prepare("SELECT * FROM local_formbuilder_submissions WHERE formid = ? ORDER BY timecreated DESC");
        $stmt->execute([$formid]);
        return $stmt->fetchAll();
    }
}
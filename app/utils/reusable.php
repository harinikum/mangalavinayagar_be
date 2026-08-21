<?php
require_once '../utils/database.php';
// require_once '../models/inventory1_model.php';

class Reusable
{
    private $conn;
    private $db;
    // private $inventory1;
    public $img_url;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();


        //     $this->inventory1 = new Inventory1_model($this->db);
    }

    public function generateOtp() {
        return rand(100000, 999999); // Generate a 6-digit OTP
    } 

    public function image_url($file = '')
    {
        return 'http://localhost/veg_delivery_php_be/app/asset/' . $file;
    }

    function convertTo12Hour($time24) {
        $date = DateTime::createFromFormat('H:i:s', $time24);
        return $date->format('g:i:s A');
    }
    

    public function get_filename($file)
    {
        if (isset($file) && $file['error'] == UPLOAD_ERR_OK) {
            $fileTmpPath = $file['tmp_name'];
            $fileName = $file['name'];

            $fileHash = md5_file($fileTmpPath);
            $decode_fileName = $fileHash . '-' . basename($fileName);

            return ["fileName" => $decode_fileName, "filePath" => $fileTmpPath];
        } else {
            throw new Exception("error of file upload resulable function");
        }
    }

    public function get_previous_data($productId, $tablename, $fieldname)
    {
        $query = "SELECT $fieldname FROM $tablename WHERE id = ?";
        $stmt = $this->conn->prepare($query);

        if ($stmt === false) {
            throw new Exception("Error preparing the query.");
        }
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $row = $result->fetch_assoc()) {
            return $row[$fieldname];
        } else {
            return null;
        }
    }

    public function check_duplicate($tableName, $fieldName, $value)
    {
        $stmt = $this->conn->prepare("SELECT * FROM $tableName WHERE $fieldName = ? ");
        $stmt->bind_param('s', $value);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows;
    }

    public function remove_file($file)
    {
        if (file_exists($file)) {
            if (!unlink($file)) {
                throw new Exception("Error removing file.");
            }
        } else {
            throw new Exception("File does not exist.");
        }
    }

    public function Common_update_spare($id, $file, $tablename, $filedname, $filemove_path)
    {

        try {
            $get_file = $this->get_filename($file);
            $file_name = $get_file['fileName'];
            $file_path = $get_file['filePath'];

            $already_exist = false;

            $previous_image = $this->get_previous_data($id, $tablename, $filedname);
            $check_dup_result = $this->check_duplicate($tablename, $filedname, $previous_image);
            $check_params_image_result = $this->check_duplicate($tablename, $filedname, $file_name);

            $existingFilePath = $filemove_path . $previous_image;

            if ($check_dup_result == 1) {
                $this->remove_file($existingFilePath);
            }

            if ($check_params_image_result > 0) {
                $already_exist = true;
            }

            $uploadPath = $filemove_path . $file_name;

            if (!$already_exist) {
                move_uploaded_file($file_path, $uploadPath);
            }

            return ["message" => "success", "fileName" => $file_name];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage(), 'file' => $existingFilePath];
        }
    }

    public function create_where_condition($params, $default_conditions = [], $search_field = [],$tableName=NULL)
    {
        $search_term = $params['search_term'] ?? null;
        $from_date = $params['from_date'] ?? null;
        $to_date = $params['to_date'] ?? null;
        $where_condition = [];

        if (!empty($default_conditions)) {
            foreach ($default_conditions as $x) {
                $where_condition[] = $x;
            }
        }

        if (!empty($search_term) && !empty($search_field)) {
            $search_term = mysqli_real_escape_string($this->conn, $search_term);
            $search_clauses = [];
            foreach ($search_field as $x) {
                $search_clauses[] = "$x LIKE '%$search_term%'";
            }
            $where_condition[] = '(' . implode(' OR ', $search_clauses) . ')';
        }

        if (!empty($from_date)) {
            $from_date = mysqli_real_escape_string($this->conn, $from_date);
            if($tableName){
                $where_condition[] = "($tableName.date >= '$from_date')";
            }
            else{
                $where_condition[] = "(date >= '$from_date')";
            }
        }

        if (!empty($to_date)) {
            $to_date = mysqli_real_escape_string($this->conn, $to_date); // Escaping date
            if($tableName){
                $where_condition[] = "($tableName.date <= '$to_date')";
            }
            else{
                $where_condition[] = "(date <= '$to_date')";
            }
        }

        return !empty($where_condition) ? "WHERE " . implode(" AND ", $where_condition) : "";
    }

    public function sort_function($sort_by, $order_by, $query, $tableName=NULL)
    {
        $sorby = strtolower($sort_by);
        $modify_sortby = str_replace(' ', '_', $sorby);
        if($tableName){
            $modify_sortby = "$tableName.$modify_sortby";
        }
        $orderby = $order_by;
        $query .= " ORDER BY $modify_sortby $orderby";

        return $query;

    }


    public function insert($table, $data)
    {
        try {
            $columns = implode(", ", array_keys($data));
            $placeholders = implode(", ", array_fill(0, count($data), '?'));

            $query = "INSERT INTO $table ($columns) VALUES ($placeholders)";

            $stmt = $this->conn->prepare($query);

            $types = "";
            $values = [];
            foreach ($data as $key => $value) {
                $types .= $this->getParamType($value);
                $values[] = $value;
            }

            $stmt->bind_param($types, ...$values);

            if ($stmt->execute()) {
                return ["message" => "success", "previous_id" => $this->conn->insert_id];
            } else {
                throw new Exception("Error during insertion: " . $stmt->error);
            }

        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage(), "query" => $query];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }

    private function getParamType($value)
    {
        if (is_int($value)) {
            return 'i';
        } elseif (is_float($value)) {
            return 'd';
        } elseif (is_string($value)) {
            return 's';
        } else {
            return 'b';
        }
    }

    public function get($table, $where_condition, $get_one_data = false, $params = null)
    {
        try {

            if (!empty($where_condition)) {
                $query = "SELECT * FROM $table $where_condition";
            } else {
                $query = "SELECT * FROM $table";
            }
            if ($get_one_data) {
                $stmt = $this->conn->prepare($query);
                if ($stmt->execute()) {
                    $result_set = $stmt->get_result();
                    $rows = $result_set->fetch_assoc();
                    if (!empty($rows)) {
                        return ["message" => "success", "data" => $rows];
                    } else {
                        return ["message" => "no data found"];
                    }

                } else {
                    throw new Exception("error on executing");
                }

            } else {
                if (!empty($params['sort_by']) && !empty($params['order_by'])) {
                    $query = $this->sort_function($params['sort_by'], $params['order_by'], $query);
                } else {
                    $query .= " ORDER BY id DESC";
                }

                if (isset($params['limit']) && is_numeric($params['limit'])) {
                    $limit = intval($params['limit']);
                    $query .= " LIMIT $limit";

                    if (isset($params['offset']) && is_numeric($params['offset'])) {
                        $offset = intval($params['offset']);
                        $query .= " OFFSET $offset";
                    }
                }
                $stmt = $this->conn->prepare($query);

                if ($stmt->execute()) {
                    $result_set = $stmt->get_result();

                    $rows = $result_set->fetch_all(MYSQLI_ASSOC);
                    return ["message" => "success", "data" => $rows];
                } else {
                    throw new Exception("error on executing");
                }
            }
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage()];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }

    public function update($table = '', $data = '', $where_condition = [], $where_params = [])
    {
        try {
            if (empty($data)) {
                return ["message" => "No data to update"];
            }
    
            $columns = implode('=?, ', array_map(fn($col) => "`$col`", array_keys($data))) . '=?';
    
            if (empty($where_condition)) {
                return ["message" => "Where condition is required"];
            }
    
            $query = "UPDATE `$table` SET $columns WHERE $where_condition";
    
            $stmt = $this->conn->prepare($query);
    
            $types = "";
            $values = [];
            
            foreach ($data as $key => $value) {
                $types .= $this->getParamType($value);
                $values[] = $value;
            }
            
            foreach ($where_params as $param) {
                $types .= $this->getParamType($param);
                $values[] = $param;
            }
    
            $stmt->bind_param($types, ...$values);
    
            if ($stmt->execute()) {
                if ($stmt->affected_rows == 0) {
                    return ["message" => "No rows affected"];
                } else {
                    return ["message" => "success"];
                }
            } else {
                throw new Exception("Error executing query");
            }
    
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage(), "error" => $query];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }
    

    public function remove($table = '', $id = '')
    {
        try {
            $is_disable = 1;
            $query = "UPDATE $table SET is_disable = ? WHERE id =?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param('ii', $is_disable, $id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows == 0) {
                    return ["message" => "no data found or no change"];
                } else {
                    return ["message" => "success"];
                }
            } else {
                throw new Exception("error on executing");
            }
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage()];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }

    public function delete($table = '',$field_name='', $id = '')
    {
        try {
            $query = "DELETE FROM $table WHERE $field_name =?";
            $stmt = $this->conn->prepare($query);
    
            $stmt->bind_param('i', $id);
    
            if ($stmt->execute()) {
                if ($stmt->affected_rows == 0) {
                    return ["message" => "no data found or no change"];
                } else {
                    return ["message" => "success"];
                }
            } else {
                throw new Exception("error on executing");
            }
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage()];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }
    






}

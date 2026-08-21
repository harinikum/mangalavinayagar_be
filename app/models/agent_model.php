<?php
require '../utils/reusable.php';
class Agent_model
{
    private $conn;
    private $payments = "payments";
    private $emi = "emi";
    private $members = "members";

    private $Reusable;

    public function __construct($db)
    {
        $this->Reusable = new Reusable();
        $this->conn = $db;
    }

    public function verify_agent($params){
        try {
            if(!isset($params['email_id']) || !isset($params['password'])){
                return ["message"=>"Give ID"];
            }

            $username = $params['email_id'];
            $password = $params['password'];

            $query = "SELECT id, password, is_super_admin, name, is_blocked FROM agent WHERE email_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->bind_result($adminId,$hashedPasswordFromDb,$isSuperAdmin, $name,$isBlocked);
            $stmt->fetch();

            if($stmt->execute()){
                $stmt->close();
                if($isBlocked == 0){
                    $isSuper = $isSuperAdmin == 1 ? "true" : "false";
                    if (password_verify($password, $hashedPasswordFromDb)) {
                        $adminTokPayload = [
                            'gmail' => $username,
                            "superadmin" => $isSuper
                        ];
                        return ["message"=>"success","admin"=>$adminTokPayload,"superadmin" => $isSuper, 'id'=> $adminId, 'name'=>$name];
                    } else {
                        return ["message"=>"Invalid username or password."];
                    }
                }
                else{
                    return ['message'=>"Your Provided Email ID is Blocked."];
                }
            }
            else{
                $stmt->close();
                return ["message"=>"Try Again"];
            }
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage()];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }

    public function insert_agent($params){
        try {
            if(empty($params['name']) || empty($params['area']) || empty($params['aadhar_no']) || empty($params['contact_no']) || empty($params['address']) || empty($params['email_id']) || empty($params['password'])){
                return ["message"=>"Give All Values"];
            }

            $isEmailExist = "SELECT COUNT(email_id) as email_count FROM agent WHERE email_id = ?";
            $isEmailStmt = $this->conn->prepare($isEmailExist);
            $isEmailStmt->bind_param("s", $params['email_id']);
            if($isEmailStmt->execute()){
                $email_result_set = $isEmailStmt->get_result();
                $rows = $email_result_set->fetch_all(MYSQLI_ASSOC);
                $emailCount = 0;
                $isEmailStmt->close();
                foreach ($rows as $result) {
                    $emailCount = $result['email_count'];
                }
                if($emailCount > 0){
                    return ['message'=>'Email Already Exists',"count"=>$emailCount];
                }
            }
            else{
                $isEmailStmt->close();
                return ['message'=>"Try Again"];
            }

            $isBlocked = 0;
            $isSuperAdmin = 0;

            $password = $params['password'];
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $query = "INSERT INTO agent (is_super_admin, email_id, password, name, area, aadhar_no, contact_no, address, is_blocked) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("issssiisi", $isSuperAdmin,$params['email_id'], $hashedPassword, $params['name'], $params['area'], $params['aadhar_no'], $params['contact_no'], $params['address'], $isBlocked);
            if($stmt->execute()){
                $stmt->close();
                return ["message"=>"Successfully Added"];
            }
            else{
                $stmt->close();
                return ["message"=>"Not Inserted Try Again"];
            }
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage()];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }

    public function update_agent_password($params){
        try {
            if(empty($params['id']) || empty($params['password'])){
                return ["message"=>"Give All Values"];
            }

            $password = $params['password'];
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $query = "UPDATE agent SET password = ? WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("si", $hashedPassword, $params['id']);
            if($stmt->execute()){
                $stmt->close();
                return ["message"=>"Successfully Updated"];
            }
            else{
                $stmt->close();
                return ["message"=>"Not Updated Try Again"];
            }
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage()];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }

    public function get_agents($params){
        try {
            $isBlocked = 0;
            if(isset($params['is_blocked'])){
                $isBlocked = $params['is_blocked'];
            }
            $search_field = ["name","area", "aadhar_no","contact_no","address"];
            $default_conditions = ["(is_blocked = $isBlocked)"];
            $where_clause = $this->Reusable->create_where_condition($params, $default_conditions, $search_field);

            $query = "SELECT id, name, area, aadhar_no, contact_no, address FROM agent $where_clause";

            if (!empty($params['sort_by']) && !empty($params['order_by'])) {
                $query = $this->Reusable->sort_function($params['sort_by'], $params['order_by'], $query, 'agent');
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
                $emis = [];

                foreach ($rows as $result) {

                    $emi_data = [
                        "id" => $result['id'],
                        "Agent Name" =>$result['name'],
                        "Area" => $result['area'],
                        "Aadhar Number" => $result['aadhar_no'],
                        "Contact Number" => $result['contact_no'],
                        "Address" => $result['address']
                    ];

                    $emis[] = $emi_data;
                }
                $stmt->close();
                return ["message" => "success", "data" => $emis, "query" => $query];
            } else {
                $stmt->close();
                throw new Exception("Error executing query");
            }
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage()];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }

    public function get_particular_agent($params){
        try {
            if(!isset($params['email_id'])){
                return ['message'=>'Provide All Values'];
            }
            $query = "SELECT id, name FROM agent WHERE email_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $params['email_id']);

            if ($stmt->execute()) {
                $result_set = $stmt->get_result();
                $rows = $result_set->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
                $emis = [];
                foreach ($rows as $result) {
                    $emi_data = [
                        "id" => $result['id'],
                        "Agent Name" =>$result['name']
                    ];

                    $emis[] = $emi_data;
                }
                $stmt->close();
                return ["message" => "success", "data" => $emis, "query" => $query];
            } else {
                throw new Exception("Error executing query");
            }
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage()];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }

    public function update_agent($params){
        try {
            if(empty($params['id']) || empty($params['name']) || empty($params['area']) || empty($params['aadhar_no']) || empty($params['contact_no']) || empty($params['address'])){
                return ["message"=>"Give All Values"];
            }

            $query = "UPDATE agent SET name = ?, area = ?, aadhar_no = ?, contact_no = ?, address = ? WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ssiisi", $params['name'], $params['area'], $params['aadhar_no'], $params['contact_no'], $params['address'], $params['id']);
            if($stmt->execute()){
                $stmt->close();
                return ["message"=>"Successfully Updated"];
            }
            else{
                $stmt->close();
                return ["message"=>"Not Updated Try Again"];
            }
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage()];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }

    public function delete_agent($params){
        try {
            if(empty($params['id'])){
                return ["message"=>"Give All Values"];
            }
            $isBlocked = 1;
            $query = "UPDATE agent SET is_blocked = ? WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ii",$isBlocked,$params['id']);
            if($stmt->execute()){
                $stmt->close();
                return ["message"=>"Successfully Deleted"];
            }
            else{
                $stmt->close();
                return ["message"=>"Not Deleted Try Again"];
            }
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage()];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }

    public function get_areas($params){
        try {
            $isBlocked = 0;
            if(isset($params['is_blocked'])){
                $isBlocked = $params['is_blocked'];
            }
            $search_field = ["area"];
            $default_conditions = ["(is_blocked = $isBlocked)"];
            $where_clause = $this->Reusable->create_where_condition($params, $default_conditions, $search_field);

            $query = "SELECT DISTINCT(area) FROM agent $where_clause";

            if (!empty($params['sort_by']) && !empty($params['order_by'])) {
                $query = $this->Reusable->sort_function($params['sort_by'], $params['order_by'], $query, 'agent');
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
                $areas = [];

                foreach ($rows as $result) {

                    $areas[] = $result['area'];
                }
                $stmt->close();
                return ["message" => "success", "data" => $areas, "query" => $query];
            } else {
                $stmt->close();
                throw new Exception("Error executing query");
            }
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage()];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    } 

    public function get_agent_names($params){
        try {
            $isBlocked = 0;
            if(isset($params['is_blocked'])){
                $isBlocked = $params['is_blocked'];
            }
            $search_field = ["name","email_id"];
            $default_conditions = ["(is_blocked = $isBlocked)"];
            $where_clause = $this->Reusable->create_where_condition($params, $default_conditions, $search_field);

            $query = "SELECT id, name, email_id FROM agent $where_clause";

            if (!empty($params['sort_by']) && !empty($params['order_by'])) {
                $query = $this->Reusable->sort_function($params['sort_by'], $params['order_by'], $query, 'agent');
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
                $names = [];

                foreach ($rows as $result) {
                    $name_and_id = [
                        "id"=>$result['id'],
                        "Name"=>$result['name'],
                        "Email ID"=>$result['email_id'],
                    ];
                    $names[] = $name_and_id;
                }
                $stmt->close();
                return ["message" => "success", "data" => $names, "query" => $query];
            } else {
                $stmt->close();
                throw new Exception("Error executing query");
            }
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage()];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }

    public function get_agent_names_and_areas($params){
        try {
            $isBlocked = 0;
            if(isset($params['is_blocked'])){
                $isBlocked = $params['is_blocked'];
            }
            $search_field_param = "name";
            if(isset($params['search_field'])){
                $search_field_param = $params['search_field'];
            }
            $search_field = [$search_field_param];
            $default_conditions = ["(is_blocked = $isBlocked)"];
            $where_clause = $this->Reusable->create_where_condition($params, $default_conditions, $search_field);

            $query = "SELECT id,name,area FROM agent $where_clause";

            if (!empty($params['sort_by']) && !empty($params['order_by'])) {
                $query = $this->Reusable->sort_function($params['sort_by'], $params['order_by'], $query, 'agent');
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
                $names_with_areas = [];

                foreach ($rows as $result) {
                    $area_names = [
                        "id"=>$result['id'],
                        "name"=>$result['name'],
                        "area"=>$result['area'],
                    ];
                    $names_with_areas[] = $area_names;
                }
                $stmt->close();
                return ["message" => "success", "data" => $names_with_areas, "query" => $query];
            } else {
                $stmt->close();
                throw new Exception("Error executing query");
            }
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage()];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }
}
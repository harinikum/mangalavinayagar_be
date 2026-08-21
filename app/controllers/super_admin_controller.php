<?php
require_once '../utils/database.php';
require_once '../models/super_admin_model.php';

class Super_admin_controller
{
    private $db;
    private $super_admin;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->super_admin = new Super_admin_model($this->db);
    }

    public function insert_super_admin($params)
    {
        $res = $this->super_admin->insert_super_admin($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
    
    public function verify_super_admin($params)
    {
        $res = $this->super_admin->verify_super_admin($params);
        if ($res) {
            return $res;
        } else {
            return ["message" => "error on controllers"];
        }
    }
    
    public function sent_otp($params)
    {
        $res = $this->super_admin->sent_otp($params);
        if ($res) {
            return $res;
        } else {
            return ["message" => "error on controllers"];
        }
    }
    
    public function verify_otp($params)
    {
        $res = $this->super_admin->verify_otp($params);
        if ($res) {
            return $res;
        } else {
            return ["message" => "error on controllers"];
        }
    }
    
    public function change_password($params)
    {
        $res = $this->super_admin->change_password($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
}
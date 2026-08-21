<?php
require_once '../utils/database.php';
require_once '../models/member_model.php';

class Member_controller
{
    private $db;
    private $agent;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->agent = new Member_model($this->db);
    }

    public function insert_member($params)
    {
        $res = $this->agent->insert_member($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
    
    public function insert_emi($params)
    {
        $res = $this->agent->insert_emi($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
    
    public function get_members($params)
    {
        $res = $this->agent->get_members($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
    
    public function get_emi_finished_members($params)
    {
        $res = $this->agent->get_emi_finished_members($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
    
    public function get_one_members($params)
    {
        $res = $this->agent->get_one_members($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
    public function get_members_pdf($params)
    {
        $res = $this->agent->get_members_pdf($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
    
    public function get_deleted_members($params)
    {
        $res = $this->agent->get_deleted_members($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
    
    public function get_after_members($params)
    {
        $res = $this->agent->get_after_members($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
    
    public function restore_member($params)
    {
        $res = $this->agent->restore_member($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
    
    public function delete_member($params)
    {
        $res = $this->agent->delete_member($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
    
    public function update_member($params)
    {
        $res = $this->agent->update_member($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }

    public function insert_and_finish_emi($params)
    {
        $res = $this->agent->insert_and_finish_emi($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
    
    public function get_members_name_id($params)
    {
        $res = $this->agent->get_members_name_id($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
}
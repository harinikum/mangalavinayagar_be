<?php
require_once '../utils/database.php';
require_once '../models/agent_model.php';

class Agent_controller
{
    private $db;
    private $agent;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->agent = new Agent_model($this->db);
    }

    public function insert_agent($params)
    {
        $res = $this->agent->insert_agent($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
    
    public function update_agent($params)
    {
        $res = $this->agent->update_agent($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
    
    public function delete_agent($params)
    {
        $res = $this->agent->delete_agent($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
    
    public function get_agents($params)
    {
        $res = $this->agent->get_agents($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
    
    public function get_agent_names_and_areas($params)
    {
        $res = $this->agent->get_agent_names_and_areas($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
    
    public function get_areas($params)
    {
        $res = $this->agent->get_areas($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
    
    public function get_agent_names($params)
    {
        $res = $this->agent->get_agent_names($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
    
    public function update_agent_password($params)
    {
        $res = $this->agent->update_agent_password($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
    
    public function verify_agent($params)
    {
        $res = $this->agent->verify_agent($params);
        if ($res) {
            return $res;
        } else {
            return ["message" => "error on controllers"];
        }
    }
    
    public function get_particular_agent($params)
    {
        $res = $this->agent->get_particular_agent($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
}

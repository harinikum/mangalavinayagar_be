<?php
require_once '../utils/database.php';
require_once '../models/payment_model.php';

class Payment_controller
{
    private $db;
    private $payments;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->payments = new Payment_model($this->db);
    }

    public function get_payments($params)
    {
        $res = NULL;
        if(isset($params['agent_id']) && !empty($params['agent_id'])){
            $res = $this->payments->get_payments($params);
        }
        else{
            $res = $this->payments->get_payments_all($params);
        }
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
    
    public function get_payments_all($params)
    {
        $res = $this->payments->get_payments_all($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
    
    public function insert_not_gived($params=NULL){
        $res = $this->payments->insert_not_gived($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
    
    public function get_new_entry_payments($params)
    {
        $res = $this->payments->get_new_entry_payments($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
    
    public function insert_payments($params)
    {
        $res = $this->payments->insert_payments($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
    
    public function insert_new_entry_payments($params)
    {
        $res = $this->payments->insert_new_entry_payments($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
    
    public function insert_and_update_payments($params)
    {
        error_log('Controller insert_and_update_payments: count=' . (isset($params['payments']) ? count($params['payments']) : 0));
        $res = $this->payments->insert_and_update_payments($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
    
    public function get_collection_wise_reports($params)
    {
        $res = $this->payments->get_collection_wise_reports($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
    
    public function get_customer_wise_reports($params)
    {
        $res = $this->payments->get_customer_wise_reports($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
    
    public function get_new_loan_reports($params)
    {
        $res = $this->payments->get_new_loan_reports($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
    
    public function update_payments($params)
    {
        $res = $this->payments->update_payments($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
}
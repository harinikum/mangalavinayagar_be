<?php
require_once '../utils/database.php';
require_once '../models/otp_model.php';

class Otp_controller
{
    private $db;
    private $otp;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->otp = new Otp_model($this->db);
    }

    public function verify_otp_update_pass($params)
    {
        $res = $this->otp->verify_otp_update_pass($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }

    public function generate_otp($params)
    {
        $res = $this->otp->generate_otp($params);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(["message" => "error on controllers"]);
        }
    }
}
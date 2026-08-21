<?php
require_once '../../utils/database.php';
require_once '../../models/payment_model.php';

class Payment_controller
{
    private $db;
    private $payments;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->payments = new Payment_model($this->db);
        $res = $this->payments->insert_not_gived();
        file_put_contents(__DIR__ . '/cron_log.txt', date('Y-m-d H:i:s') . " - " . json_encode($res) . PHP_EOL, FILE_APPEND);
        echo json_encode($res);
    }
}

new Payment_controller();
<?php
require '../utils/reusable.php';
class Payment_model
{
    private $conn;

    private $Reusable;

    public function __construct($db)
    {
        $this->Reusable = new Reusable();
        $this->conn = $db;
    }

    public function get_payments($params) {
        try {
            // $agentId = $params['agent_id'];
            $currentYear = date('Y');
            $currentMonth = date('m');
            if(isset($params['month']) && isset($params['year']) && !empty($params['month']) && !empty($params['year'])){
                $currentMonth = $params['month'];
                $currentYear = $params['year'];
            }
            $firstDate = "$currentYear-$currentMonth-01";
            $lastDate = date('Y-m-t', strtotime($firstDate));

            // Fetch agent data
            $sqlAgents = "SELECT id AS agent_id, name AS agent_name, area FROM agent ";
            $agentId = 0;
            $collectorId = null;
if(isset($params['collector_id'])){
    $collectorId = $params['collector_id'];
}
            if(isset($params['agent_id'])){
                $sqlAgents.="WHERE id = ?";
                $agentId = $params['agent_id'];
            }
            else{
                $sqlAgents.="ORDER BY id DESC LIMIT 1";
            }
            $stmtAgents = $this->conn->prepare($sqlAgents);
            if(isset($params['agent_id'])){
                $stmtAgents->bind_param("i", $agentId);
            }
            $stmtAgents->execute();
            $resultAgents = $stmtAgents->get_result();
    
            if ($resultAgents->num_rows === 0) {
                // return ["message" => "No Agents Found"];
            }

            $datas = [];
            while ($agent = $resultAgents->fetch_assoc()) {
                $stmtAgents->close();
                // Fetch members linked to the agent
                $sqlMembers = "SELECT e1.member_id, e1.id as emiId, e1.note_id, e1.area as emi_area, members.name AS member_name, e1.date_of_loan, e1.loan_amount, e1.end_date,e1.balance_amount FROM emi e1 JOIN members ON members.id = e1.member_id WHERE (e1.agent_id = ?) AND (e1.id = (SELECT MAX(e2.id) FROM emi e2 WHERE e2.member_id = e1.member_id)) AND (members.is_blocked = 0)";
                if(isset($collectorId) && !empty($collectorId)){
    $sqlMembers .= "
    AND EXISTS (
        SELECT 1
        FROM payments p
        WHERE p.emi_id = e1.id
        AND p.collector_id = ?
        AND MONTH(p.date) = ?
        AND YEAR(p.date) = ?
    )";
}
                $getLastPayment = "SELECT payments.date FROM emi JOIN payments ON payments.emi_id = emi.id WHERE emi.agent_id = ? ORDER BY payments.date DESC LIMIT 1";
                $stmtLastPay = $this->conn->prepare($getLastPayment);
                $stmtLastPay->bind_param("i", $agent['agent_id']);
                $stmtLastPay->execute();
                $resultLastPay = $stmtLastPay->get_result();
                $lastPayDate = NULL;
                while ($lastPay = $resultLastPay->fetch_assoc()) {
                    $lastPayDate = $lastPay['date'];
                }
                $stmtLastPay->close();

                $stmtMembers = $this->conn->prepare($sqlMembers);
               if(isset($collectorId) && !empty($collectorId)){
    $stmtMembers->bind_param(
        "iiii",
        $agent['agent_id'],
        $collectorId,
        $currentMonth,
        $currentYear
    );
}
else{
    $stmtMembers->bind_param(
        "i",
        $agent['agent_id']
    );
}
                // $stmtMembers->bind_param("i", $agent['agent_id']);
                $stmtMembers->execute();
                $resultMembers = $stmtMembers->get_result();
                $lastRow = [
                    "AGENT" => '',
                    "AREA" => '',
                    "user_unique_id"=> '',
                    "note_id" =>'',
                    "உ.எண்" => '',
                    "CNAME" => '',
                    "LAMT" => '',
                    "DATE" => 'Total',
                    "END DATE" => '',
                    "hideLastPayDate" => '',
                    "hideAgentId" => '',
                    "hideMemberId" => '',
                ];
                $i = 0;
                if ($resultMembers->num_rows > 0) {
                    $totalBalance = 0;
                    $rowTotalLastRow = 0;
                    while ($member = $resultMembers->fetch_assoc()) {
                        error_log('MEMBER_DATA: ' . json_encode($member));
                        // Pre-build all dates for the month
                        $dates = [];
                        $start = strtotime($firstDate);
                        $end = strtotime($lastDate);
                        while ($start <= $end) {
                            $dates[] = date('Y-m-d', $start);
                            if($i==0){
                                $lastRow[date('Y-m-d', $start)] = ["date"=>0,"isNotPaid"=>2];
                            }
                            $start = strtotime("+1 day", $start);
                        }
                        $i++;
                        error_log("MEMBER_DATA => " . json_encode($member));

$rows = [
    "AGENT" => $agent['agent_name'],
    "AREA" => $member['emi_area'],
    "user_unique_id" => $member['note_id'],
    "note_id" => $member['note_id'],
    "உ.எண்" => $member['note_id'],
    "CNAME" => $member['member_name'],
    "LAMT" => $member['loan_amount'],
    "DATE" => $member['date_of_loan'],
    "END DATE" => $member['end_date'],
    "hideLastPayDate" => $lastPayDate,
    "hideAgentId" => $agent['agent_id'],
    "hideMemberId" => $member['member_id'],
    "hideEmiId" => $member['emiId'],
];

error_log("ROWS_DATA => " . json_encode($rows));
                        $rows = [
                            "AGENT" => $agent['agent_name'],
                            "AREA" => $member['emi_area'],
                            "user_unique_id" => $member['note_id'],
                            "note_id" => $member['note_id'],
                            "உ.எண்" => $member['note_id'],
                            "CNAME" => $member['member_name'],
                            "LAMT" => $member['loan_amount'],
                            "DATE" => $member['date_of_loan'],
                            "END DATE" => $member['end_date'],
                            "hideLastPayDate" => $lastPayDate,
                            "hideAgentId" => $agent['agent_id'],
                            "hideMemberId" => $member['member_id'],
                            "hideEmiId" => $member['emiId'],
        "note_id" => $member['note_id'],
                        ];
                        error_log(json_encode($rows));
                        
                        // Fetch payments for all dates in a single query
                        $datePlaceholders = implode(",", array_fill(0, count($dates), "?"));
                        // $sqlPayments = "SELECT date, amount, is_not_paid, payment_method
                        //                 FROM payments 
                        //                 WHERE emi_id = ? AND date IN ($datePlaceholders)";
                        $sqlPayments = "
SELECT date, amount, is_not_paid, payment_method
FROM payments
WHERE emi_id = ?";

if(isset($collectorId) && !empty($collectorId)){
    $sqlPayments .= " AND collector_id = ?";
}

$sqlPayments .= " AND date IN ($datePlaceholders)";
                        $stmtPayments = $this->conn->prepare($sqlPayments);
                        // $stmtPayments->bind_param(
                        //     str_repeat("i", 1) . str_repeat("s", count($dates)),
                        //     $member['emiId'],
                        //     ...$dates
                        // );
                       if(isset($collectorId) && !empty($collectorId)){
    $stmtPayments->bind_param(
        "ii" . str_repeat("s", count($dates)),
        $member['emiId'],
        $collectorId,
        ...$dates
    );
}else{
    $stmtPayments->bind_param(
        "i" . str_repeat("s", count($dates)),
        $member['emiId'],
        ...$dates
    );
}
                        $stmtPayments->execute();
                        $resultPayments = $stmtPayments->get_result();
    
                        // Map payments by date
                        $paymentMap = array_fill_keys($dates, ["date"=>0,"is_not_paid"=>2,"payment_method"=>null]); // Default to 0
                        $rowTotal = 0;
                        while ($payment = $resultPayments->fetch_assoc()) {
                            $paymentMap[$payment['date']] = ["date"=>$payment['amount'],"isNotPaid"=>$payment['is_not_paid'],"payment_method"=>$payment['payment_method']];
                            $rowTotal+=$payment['amount'];
                            $lastRow[$payment['date']]['date']+= $payment['amount'];
                        }
                        $stmtPayments->close();
error_log('PAYMENT_MAP: ' . json_encode($paymentMap));
                        $rows = array_merge($rows, $paymentMap);
error_log('PAYMENT_MAP: ' . json_encode($paymentMap));
                        $rows['Total Collection'] = $rowTotal;
                        $rowTotalLastRow+=$rowTotal;
                        $rows["Balance Amount"] = $member['balance_amount'];
                        $totalBalance+= $member['balance_amount'];
                        $datas[] = $rows;
                        $lastRow['Balance Amount'] = $totalBalance;
                        $lastRow['Total Collection'] = $rowTotalLastRow;
                    }
                }
                $stmtMembers->close();
                $datas[] = $lastRow;
error_log('FINAL_DATA: ' . json_encode($datas));
            }
    
            return ["message" => "success", "data" => $datas];
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage()];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }

    public function get_new_entry_payments($params) {
        try {
            $payDate = date('Y-m-d');
            if (isset($params['date'])) {
                $payDate = $params['date'];
            }
    
            $isNotPaid = 0;
            $collectorId = null;
            // Optional collector_id – only set if provided and not empty
            if (isset($params['collector_id']) && $params['collector_id'] !== '' && $params['collector_id'] !== null) {
                $collectorId = $params['collector_id'];
            }

            // Build dynamic WHERE conditions and bind parameters
            $conditions = [];
            $types = '';
            $bindValues = [];

            // date condition (always present)
            $conditions[] = "payments.date = ?";
            $types .= 's';
            $bindValues[] = $payDate;

            // is_not_paid condition (always present)
            $conditions[] = "payments.is_not_paid = ?";
            $types .= 'i';
            $bindValues[] = $isNotPaid;

            // optional collector_id condition
            if (!is_null($collectorId)) {
                $conditions[] = "payments.collector_id = ?";
                $types .= 'i';
                $bindValues[] = $collectorId;
            }

            $query = "
SELECT
    members.id,
    emi.note_id as 'உ.எண்',
    payments.amount as 'ரசீது தொகை',
     payments.payment_method as payment_method,
    members.id as 'hideMemberId',
    payments.id as hidePayId,
    payments.collector_id as hideCollectorId,
    payments.emi_id as hideEmiID,
    payments.balance_amt as hideBalanceAmt,
    payments.date AS hidePayDate,
    payments.time AS hidePayTime,
    emi.loan_amount as hideLoanAmount,
    emi.balance_amount as hideBalanceAmount,
    emi.is_finished as hideIsFinished,
    TRUE AS hideIsNotEdit
FROM payments
JOIN emi ON emi.id = payments.emi_id
JOIN members ON members.id = emi.member_id
WHERE " . implode(' AND ', $conditions) . "
";
            $stmt = $this->conn->prepare($query);
            // Dynamically bind parameters
            $stmt->bind_param($types, ...$bindValues);
            $stmt->execute();
    
            $result = $stmt->get_result();
            $data = [];
    
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            $data[] = [
                "id" => "",
                "உ.எண்" => "",
                "ரசீது தொகை" => ""
            ];
            $stmt->close();
            return ["message" => "success", "data" => $data];

        } catch (mysqli_sql_exception $e) {
            return ["success" => false, "message" => "Database Error: " . $e->getMessage()];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Error: " . $e->getMessage()];
        }
    } 

    public function get_payments_all($params) {
        try {
            $currentYear = date('Y');
            $currentMonth = date('m');
            if(isset($params['month']) && isset($params['year']) && !empty($params['month']) && !empty($params['year'])){
                $currentMonth = $params['month'];
                $currentYear = $params['year'];
            }
            $firstDate = "$currentYear-$currentMonth-01";
            $lastDate = date('Y-m-t', strtotime($firstDate));

            $sqlAgents = "SELECT id AS agent_id, name AS agent_name, area FROM agent ";
            $agentId = 0;
            $collectorId = null;

if(isset($params['collector_id']) && !empty($params['collector_id'])){
    $collectorId = $params['collector_id'];
}
            if(isset($params['agent_id']) && !empty($params['agent_id'])){
                $sqlAgents.="WHERE id = ?";
                $agentId = $params['agent_id'];
            }
            // else{
            //     $sqlAgents.="ORDER BY id DESC LIMIT 1";
            // }
            $stmtAgents = $this->conn->prepare($sqlAgents);
            if(isset($params['agent_id']) && !empty($params['agent_id'])){
                $stmtAgents->bind_param("i", $agentId);
            }
            $stmtAgents->execute();
            $resultAgents = $stmtAgents->get_result();
    
            if ($resultAgents->num_rows === 0) {
                // return ["message" => "No Agents Found"];
            }

            $datas = [];
            $lastRow = [
                "AGENT" => '',
                "AREA" => '',
                "CNAME" => '',
                "LAMT" => '',
                "DATE" => 'Total',
                "hideEndDate" => '',
                "hideLastPayDate" => '',
                "hideAgentId" => '',
                "hideMemberId" => '',
            ];
            $i = 0;
            $totalBalance = 0;
            $rowTotalLastRow = 0;
            while ($agent = $resultAgents->fetch_assoc()) {
                $sqlMembers = "SELECT e1.member_id, e1.id as emiId, e1.note_id, members.name AS member_name, e1.date_of_loan, e1.loan_amount, e1.end_date,e1.balance_amount FROM emi e1 JOIN members ON members.id = e1.member_id WHERE (e1.agent_id = ?) AND (e1.id = (SELECT MAX(e2.id) FROM emi e2 WHERE e2.member_id = e1.member_id)) AND (members.is_blocked = 0)";
                if(isset($collectorId) && !empty($collectorId)){
    $sqlMembers .= "
    AND EXISTS (
        SELECT 1
        FROM payments p
        WHERE p.emi_id = e1.id
        AND p.collector_id = ?
        AND MONTH(p.date) = ?
        AND YEAR(p.date) = ?
    )";
}

                $getLastPayment = "SELECT payments.date FROM emi JOIN payments ON payments.emi_id = emi.id WHERE emi.agent_id = ? ORDER BY payments.id DESC LIMIT 1";
                $stmtLastPay = $this->conn->prepare($getLastPayment);
                $stmtLastPay->bind_param("i", $agent['agent_id']);
                $stmtLastPay->execute();
                $resultLastPay = $stmtLastPay->get_result();
                $lastPayDate = NULL;
                while ($lastPay = $resultLastPay->fetch_assoc()) {
                    $lastPayDate = $lastPay['date'];
                }
                $stmtLastPay->close();

                $stmtMembers = $this->conn->prepare($sqlMembers);
                if(isset($collectorId) && !empty($collectorId)){
                    $stmtMembers->bind_param(
                        "iiii",
                        $agent['agent_id'],
                        $collectorId,
                        $currentMonth,
                        $currentYear
                    );
                } else {
                    $stmtMembers->bind_param("i", $agent['agent_id']);
                }
                $stmtMembers->execute();
                $resultMembers = $stmtMembers->get_result();
                if ($resultMembers->num_rows > 0) {
                    
                    while ($member = $resultMembers->fetch_assoc()) {
                        // Pre-build all dates for the month
                        $dates = [];
                        $start = strtotime($firstDate);
                        $end = strtotime($lastDate);
                        while ($start <= $end) {
                            $dates[] = date('Y-m-d', $start);
                            if($i==0){
                                $lastRow[date('Y-m-d', $start)] = ["date"=>0,"isNotPaid"=>2];
                            }
                            $start = strtotime("+1 day", $start);
                        }
                        $i++;
                        
                        $rows = [
                            "AGENT" => $agent['agent_name'],
                            "AREA" => $agent['area'],
                            "user_unique_id" => $member['note_id'],
                            "note_id" => $member['note_id'],
                            "உ.எண்" => $member['note_id'],
                            "CNAME" => $member['member_name'],
                            "LAMT" => $member['loan_amount'],
                            "DATE" => $member['date_of_loan'],
                            "hideEndDate" => $member['end_date'],
                            "hideLastPayDate" => $lastPayDate,
                            "hideAgentId" => $agent['agent_id'],
                            "hideMemberId" => $member['member_id'],
                            "hideEmiId" => $member['emiId'],
                        ];
                        
                        $datePlaceholders = implode(",", array_fill(0, count($dates), "?"));
                                                                $sqlPayments = "SELECT date, amount, is_not_paid, payment_method
                                        FROM payments 
                                        WHERE emi_id = ? AND date IN ($datePlaceholders)";
                        $stmtPayments = $this->conn->prepare($sqlPayments);
                        $stmtPayments->bind_param(
                            str_repeat("i", 1) . str_repeat("s", count($dates)),
                            $member['emiId'],
                            ...$dates
                        );
                        $stmtPayments->execute();
                        $resultPayments = $stmtPayments->get_result();
    
                        // Map payments by date
                        $paymentMap = array_fill_keys($dates, ["date"=>0,"is_not_paid"=>2,"payment_method"=>null]); // Default to 0
                        $rowTotal = 0;
                        while ($payment = $resultPayments->fetch_assoc()) {
                            $paymentMap[$payment['date']] = ["date"=>$payment['amount'],"isNotPaid"=>$payment['is_not_paid'],"payment_method"=>$payment['payment_method']];
                            $rowTotal+=$payment['amount'];
                            $lastRow[$payment['date']]['date']+= $payment['amount'];
                        }
                        $stmtPayments->close();
                        $rows = array_merge($rows, $paymentMap);
                        $rows['Total Collection'] = $rowTotal;
                        $rowTotalLastRow+=$rowTotal;
                        $rows["Balance Amount"] = $member['balance_amount'];
                        $totalBalance+= $member['balance_amount'];
                        $datas[] = $rows;
                        $lastRow['Balance Amount'] = $totalBalance;
                        $lastRow['Total Collection'] = $rowTotalLastRow;
                    }
                }
                $stmtMembers->close();
            }
            $stmtAgents->close();
            $datas[] = $lastRow;    
            return ["message" => "success", "data" => $datas];
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage()];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }

    public function insert_payments($params){
        try {
            $currentTime = date('H:i:s');
            foreach($params as $payment){
                $query = "INSERT INTO payments (collector_id, emi_id, amount, is_not_paid, date,time) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("iiiiss", $payment['collector_id'],  $payment['emi_id'], $payment['amount'], $payment['is_not_paid'], $payment['date'],$currentTime);
                $stmt->execute();
                $lastId = $this->conn->insert_id;
                $stmt->close();
                if($payment['is_not_paid'] == 0){
                    $amt = $payment['amount'];
                    $memberId = $payment['member_id'];
                    $emiId = $payment['emi_id'];
                    $updtQuery = "UPDATE emi SET balance_amount = balance_amount-$amt WHERE id = $emiId";
                    $updtStmt = $this->conn->prepare($updtQuery);
                    $updtStmt->execute();
                    $updtStmt->close();


                    $selQuery = "SELECT balance_amount FROM emi WHERE id = $emiId";
                    $stmtSel = $this->conn->prepare($selQuery);
                    $stmtSel->execute();
                    $stmtSel->close();
                    $resultSel = $stmtSel->get_result();
                    $balanceAmt = NULL;
                    while ($member = $resultSel->fetch_assoc()) {
                        $balanceAmt = $member['balance_amount'];
                    }

                    $updtPayQuery = "UPDATE payments SET balance_amt = $balanceAmt WHERE id = $lastId";
                    $updtPayStmt = $this->conn->prepare($updtPayQuery);
                    $updtPayStmt->execute();
                    $updtPayStmt->close();

                    if($balanceAmt == 0){
                        $finishQuery = "UPDATE emi SET is_finished = 1 WHERE id = $memberId";
                        $finishStmt = $this->conn->prepare($finishQuery);
                        $finishStmt->execute();
                        $finishStmt->close();
                    }
                }
            }
            return ["message"=>"Successfully Registered"];
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage()];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }

    // public function update_payments($params){
    //     try {
    //         if(!isset($params['id']) || !isset($params['amount'])){
    //             return ['message'=>"Give ID to Update"];
    //         }
    //         $currentTime = date('H:i:s');
    //         $getQuery = "SELECT emi_id, amount, balance_amt, is_not_paid FROM payments WHERE id = ?";
    //         $getStmt = $this->conn->prepare($getQuery);
    //         $getStmt->bind_param("i", $params['id']);
    //         if($getStmt->execute()){
    //             $result_set = $getStmt->get_result();
    //             $numRows = $result_set->num_rows;
    //             if($numRows == 0){
    //                 return ['message'=>'No Payments in the Provided ID'];
    //             }
    //             $rows = $result_set->fetch_all(MYSQLI_ASSOC);
    //             $isNotPaid = NULL;
    //             $emi_id = NULL;
    //             $amount = NULL;
    //             $balance_amt = NULL;
    //             foreach($rows as $pays){
    //                 $isNotPaid = $pays['is_not_paid'];
    //                 $amount = $pays['amount'];
    //                 $balance_amt = $pays['balance_amt'];
    //                 $emi_id = $pays['emi_id'];
    //             }
    //             $checkEmiFinishedQuery = "SELECT is_finished FROM emi WHERE id = ?";
    //             $checkStmt = $this->conn->prepare($checkEmiFinishedQuery);
    //             $checkStmt->bind_param("i", $emi_id);
    //             $checkStmt->execute();
    //             $result = $checkStmt->get_result();
    //             $emiData = $result->fetch_assoc();
    //             if ($emiData && $emiData['is_finished'] == 1) {
    //                 return ['message' => 'This EMI is already marked as finished. You cannot edit its payments.'];
    //             }
    //             if(($isNotPaid == 1 && $params['amount'] > 0) || ($isNotPaid == 0 && $params['amount'] == 0)){
    //                 $paidBool = 0;
    //                 if($params['amount'] > 0){
    //                     $paidBool = 1;
    //                 }
    //                 $updtPayBoolQuery = "UPDATE payments SET is_not_paid = ? WHERE id = ?";
    //                 $updtPayBoolStmt = $this->conn->prepare($updtPayBoolQuery);
    //                 $updtPayBoolStmt->bind_param("ii", $paidBool, $params['id']);
    //                 if(!$updtPayBoolStmt->execute()){
    //                     return['message'=>'Try Again'];   
    //                 }
    //             }
    //             $isNotPay = 0;
    //             if($params['amount'] == 0){
    //                 $isNotPay = 1;
    //             }
    //             $updatePayAmount = "UPDATE payments SET amount = ?, is_not_paid = ? WHERE id = ?";
    //             $updtPayAmountStmt = $this->conn->prepare($updatePayAmount);
    //             $updtPayAmountStmt->bind_param("iii", $params['amount'], $isNotPay, $params['id']);
    //             if($updtPayAmountStmt->execute()){
    //                 $getAllBalQuery = "SELECT id, amount, balance_amt FROM payments WHERE (emi_id = ? AND id >= ?) ORDER BY id";
    //                 $getAllStmt = $this->conn->prepare($getAllBalQuery);
    //                 $getAllStmt->bind_param("ii", $emi_id, $params['id']);
    //                 if($getAllStmt->execute()){
    //                     $result_set_all = $getAllStmt->get_result();
    //                     $numRowsAll = $result_set_all->num_rows;

    //                     $rowsAll = $result_set_all->fetch_all(MYSQLI_ASSOC);
    //                     $correctBal = $balance_amt + ($amount - $params['amount']);
    //                     $i = 1;
    //                     foreach($rowsAll as $allBal){
    //                         if($i != 1){
    //                             $correctBal = $correctBal-$allBal['amount'];
    //                         }
    //                         $updateBal = "UPDATE payments SET balance_amt = ? WHERE id = ?";
    //                         $updateBalStmt = $this->conn->prepare($updateBal);
    //                         $updateBalStmt->bind_param("ii",$correctBal,$allBal['id']);
    //                         $updateBalStmt->execute();
    //                         // if($updateBalStmt->execute() && $i != $numRowsAll){
    //                         //     $correctBal = $correctBal-$allBal['amount'];
    //                         // }
    //                         $i++;
    //                     }
    //                     $getEmiQuery = "SELECT is_finished FROM emi WHERE id = ?";
    //                     $getEmiStmt = $this->conn->prepare($getEmiQuery);
    //                     $getEmiStmt->bind_param("i",$emi_id);
    //                     if($getEmiStmt->execute()){
    //                         $result_set_finsihed = $getEmiStmt->get_result();
    //                         $rowsFinished = $result_set_finsihed->fetch_all(MYSQLI_ASSOC);
    //                         $isFinished = 0;
    //                         foreach($rowsFinished as $fin){
    //                             $isFinished = $fin['is_finished'];
    //                         }
    //                         if($isFinished == 0){
    //                             $updtOverAllBal = "UPDATE emi SET balance_amount = ? WHERE id = ?";
    //                             $updtOverAllStmt = $this->conn->prepare($updtOverAllBal);
    //                             $updtOverAllStmt->bind_param("ii", $correctBal, $emi_id);
    //                             if($updtOverAllStmt->execute()){
    //                                 return ['message'=>'Successfully Completed'];
    //                             }
    //                             else{
    //                                 return ['message'=>"Payments Updated But not Overall Amount"];
    //                             }
    //                         }
    //                         else{
    //                             return ['message'=>'Successfully Completed'];

    //                         }
    //                     }
    //                     else{
    //                         return ['message'=>"Payments Updated.But not Overall Balance Amount"];
    //                     }
    //                 }
    //                 else{
    //                     return ['message'=>"Try Again"];
    //                 }
    //             }
    //             else{
    //                 return ['message'=>'Try Again'];
    //             }
    //         }
    //         else{
    //             return ["message"=>"Try Again"];
    //         }
            

    //     } catch (mysqli_sql_exception $e) {
    //         return ["message" => "Database Error: " . $e->getMessage()];
    //     } catch (Exception $e) {
    //         return ["message" => "Error: " . $e->getMessage()];
    //     }
    // }
    public function update_payments($params){
        try {
            if(!isset($params['id']) || !isset($params['amount'])){
                return ['message' => "Give ID to Update"];
            }
    
            // Step 1: Get current payment details
            $getQuery = "SELECT emi_id, amount, balance_amt, is_not_paid FROM payments WHERE id = ?";
            $getStmt = $this->conn->prepare($getQuery);
            $getStmt->bind_param("i", $params['id']);
            if(!$getStmt->execute()){
                return ["message" => "Try Again"];
            }
    
            $result_set = $getStmt->get_result();
            if($result_set->num_rows == 0){
                return ['message'=>'No Payments in the Provided ID'];
            }
    
            $payment = $result_set->fetch_assoc();
            $emi_id = $payment['emi_id'];
            $oldAmount = $payment['amount'];
            $oldBalance = $payment['balance_amt'];
            $isNotPaid = $payment['is_not_paid'];
            $newAmount = $params['amount'];
    
            // Step 2: Skip everything if amount is the same
            if ($oldAmount == $newAmount) {
                return ['message' => 'No change in payment amount'];
            }

            $getStmt->close();
            // Step 3: Check if EMI is already finished
            $checkStmt = $this->conn->prepare("SELECT is_finished FROM emi WHERE id = ?");
            $checkStmt->bind_param("i", $emi_id);
            $checkStmt->execute();
            $emiData = $checkStmt->get_result()->fetch_assoc();
            $checkStmt->close();
            if ($emiData && $emiData['is_finished'] == 1) {
                return ['message' => 'This EMI is already marked as finished. You cannot edit its payments.'];
            }
    
            // Step 4: Update is_not_paid flag based on new amount
            $newIsNotPaid = ($newAmount == 0) ? 1 : 0;
            if ($newIsNotPaid != $isNotPaid) {
                $updateBoolStmt = $this->conn->prepare("UPDATE payments SET is_not_paid = ? WHERE id = ?");
                $updateBoolStmt->bind_param("ii", $newIsNotPaid, $params['id']);
                if(!$updateBoolStmt->execute()){
                    $updateBoolStmt->close();
                    return ['message'=>'Try Again'];   
                }
                $updateBoolStmt->close();
            }
    
            // Step 5: Update amount and is_not_paid
            $updatePayStmt = $this->conn->prepare("UPDATE payments SET amount = ?, is_not_paid = ? WHERE id = ?");
            $updatePayStmt->bind_param("iii", $newAmount, $newIsNotPaid, $params['id']);
            if(!$updatePayStmt->execute()){
                $updatePayStmt->close();
                return ['message'=>'Try Again'];
            }
            $updatePayStmt->close();
    
            // Step 6: Get all payments from this one onward for balance update
            $getAllStmt = $this->conn->prepare("SELECT id, amount, balance_amt FROM payments WHERE emi_id = ? ORDER BY id ASC");
            $getAllStmt->bind_param("i", $emi_id);
            if(!$getAllStmt->execute()){
                $getAllStmt->close();
                return ['message'=>"Try Again"];
            }
    
            $payments = $getAllStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $getAllStmt->close();
            
            // Step 7: Calculate balance forward from the updated payment
            $correctBal = $oldBalance + ($oldAmount - $newAmount);
            $updateBalStmt = $this->conn->prepare("UPDATE payments SET balance_amt = ? WHERE id = ?");
    
            $found = false;
            foreach ($payments as $pay) {
                if (!$found) {
                    // Skip updating until we find the updated payment row
                    if ($pay['id'] == $params['id']) {
                        $found = true;
                        $updateBalStmt->bind_param("ii", $correctBal, $pay['id']);
                        $updateBalStmt->execute();
                    }
                    continue;
                }
                // For all following payments
                $correctBal -= $pay['amount'];
                $updateBalStmt->bind_param("ii", $correctBal, $pay['id']);
                $updateBalStmt->execute();
            }
            $updateBalStmt->close();
    
            // Step 8: Update EMI table balance if not finished
            if ($emiData['is_finished'] == 0) {
                $updtEmiStmt = $this->conn->prepare("UPDATE emi SET balance_amount = ? WHERE id = ?");
                $updtEmiStmt->bind_param("ii", $correctBal, $emi_id);
                if($updtEmiStmt->execute()){
                    $updtEmiStmt->close();
                    return ['message'=>'Successfully Completed'];
                } else {
                    $updtEmiStmt->close();
                    return ['message'=>"Payments Updated But Not Overall EMI Balance"];
                }
            } else {
                return ['message'=>'Successfully Completed'];
            }
    
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage()];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }
    


    

    public function insert_and_update_payments($params){
        try{
            $updates = [];
            $inserts = [];

            // foreach ($params['payments'] as $payment) {
            // if (isset($payment['hidePayId'])) {
            //     $updates[] = [
            //         'id' => $payment['hidePayId'],
            //         'amount' => (int)$payment['ரசீது தொகை']
            //     ];
            // } elseif (!isset($payment['hidePayId'])) {
            //     $inserts[] = $payment;
            // }
            // }
            foreach ($params['payments'] as $payment) {

    if (!empty($payment['hidePayId'])) {
        // Existing payment → UPDATE
        $updates[] = [
            'id' => $payment['hidePayId'],
            'amount' => (int)$payment['ரசீது தொகை']
        ];
    } else {
        // New payment → INSERT
        $inserts[] = $payment;
    }

}
            // Ensure payment_method is set for each insert
            foreach ($inserts as &$payment) {
                if (!isset($payment['payment_method'])) {
                    $payment['payment_method'] = isset($params['payment_method']) ? strtolower($params['payment_method']) : 'cash';
                } else {
                    $payment['payment_method'] = strtolower($payment['payment_method']);
                }
            }
            unset($payment);
            $updateResults = [];
            foreach ($updates as $updateItem) {
                $updateResults[] = $this->update_payments($updateItem);
            }

            $insertResult = [];
            if (count($inserts)) {
            $insertResult = $this->insert_new_entry_payments([
                'agent_id' => $params['agent_id'],
                'date' => $params['date'],
                'payments' => $inserts
            ]);
            }

            $response = [
                'updates' => $updateResults,
                'insert' => $insertResult
            ];
            return ['message'=>'success', 'response'=>$response];
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage()];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }

    public function insert_new_entry_payments($params) {
        try {
            $currentTime = date('H:i:s');
            if (!isset($params['payments']) || !isset($params['date']) || !isset($params['agent_id'])) {
                return ['message' => "Give All Datas"];
            }
    
            $agent_id = $params['agent_id'];
            $payments = $params['payments'];
            $date = $params['date'];
            $collectorId = $params['agent_id'];
    
            $not_inserted_datas = [];
    
            foreach ($payments as $payment) {
                // $getEmiQuery = "SELECT * FROM emi WHERE agent_id = ? AND member_id = ? ORDER BY id DESC LIMIT 1";
                // $getEmiStmt = $this->conn->prepare($getEmiQuery);
                // $getEmiStmt->bind_param('ii', $agent_id, $payment['hideMemberId']);
                if (
    isset($payment['hideEmiID']) &&
    is_numeric($payment['hideEmiID']) &&
    $payment['hideEmiID'] > 0
){

    $getEmiQuery = "SELECT * FROM emi WHERE id = ?";
    $getEmiStmt = $this->conn->prepare($getEmiQuery);
    $getEmiStmt->bind_param('i', $payment['hideEmiID']);

} else {

    $getEmiQuery = "
        SELECT *
        FROM emi
        WHERE member_id = ?
        ORDER BY id DESC
        LIMIT 1";

    $getEmiStmt = $this->conn->prepare($getEmiQuery);
    $getEmiStmt->bind_param('i', $payment['hideMemberId']);
}
                $getEmiStmt->execute();
                $result = $getEmiStmt->get_result();
    
                if ($result->num_rows == 0) {
                    $getEmiStmt->close();
                    $not_inserted_datas[] = $payment['hideMemberId'];
    continue;
                    // return ['message' => 'Try Again'];
                }
    
                $emiData = $result->fetch_assoc();
                $emiId = $emiData['id'];
                $memberId = $emiData['member_id'];
                $isFinishedEmi = $emiData['is_finished'];
                $getEmiStmt->close();
                if($isFinishedEmi == 1){
                    $not_inserted_datas[] = $memberId;
                    continue;// return ['message'=>'This EMI is Already Finished You Cannot Edit this EMI'];
                }
                // Check if payment exists for that date
                $getPay = "SELECT * FROM payments WHERE emi_id = ? AND date = ? ORDER BY id DESC LIMIT 1";
                $getPayStmt = $this->conn->prepare($getPay);
                $getPayStmt->bind_param("is", $emiId, $date);
                $getPayStmt->execute();
                $getPayResult = $getPayStmt->get_result();
    
                $isNewPayment = false;
                $lastId = null;
    
                if ($getPayResult->num_rows > 0) {
                    $row = $getPayResult->fetch_assoc();
                    $lastId = $row['id'];
                    $getPayStmt->close();
    
                    // If payment was previously marked not paid, update it
                    if ($row['is_not_paid'] == 1) {
                        $isNotPaid = 0;
                        $query = "UPDATE payments SET amount = ?, is_not_paid = ? WHERE id = ?";
                        $stmt = $this->conn->prepare($query);
                        $stmt->bind_param("iii", $payment['ரசீது தொகை'], $isNotPaid, $lastId);
                        $stmt->execute();
                        $stmt->close();
                        $isNewPayment = true; // treat it like a new payment
                    } else {
                        // Already paid correctly — skip balance update
                        $not_inserted_datas[] = $memberId;
                        continue;
                    }
                    
                } else {
                    // Insert new payment
                    $getPayStmt->close();
                    $isNotPaid = 0;
                      $paymentMethod = isset($payment['payment_method'])
        ? strtolower($payment['payment_method'])
        : 'cash';
                                        $query = "INSERT INTO payments (collector_id, emi_id, amount, is_not_paid, date, time, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bind_param("iiiisss", $collectorId, $emiId, $payment['ரசீது தொகை'], $isNotPaid, $date, $currentTime, $payment['payment_method']);
                    $stmt->execute();
                    $stmt->close();
                    $lastId = $this->conn->insert_id;
                    $isNewPayment = true;
                }
    
                // Only update EMI balance if it was a new payment
                if ($isNewPayment) {
                    $amt = $payment['ரசீது தொகை'];
    
                    // Update EMI balance
                    $updtQuery = "UPDATE emi SET balance_amount = balance_amount - ? WHERE id = ?";
                    $updtStmt = $this->conn->prepare($updtQuery);
                    $updtStmt->bind_param("ii", $amt, $emiId);
                    $updtStmt->execute();
                    $updtStmt->close();
                    // Get new balance amount
                    $selQuery = "SELECT balance_amount FROM emi WHERE id = ?";
                    $stmtSel = $this->conn->prepare($selQuery);
                    $stmtSel->bind_param("i", $emiId);
                    $stmtSel->execute();
                    $resultSel = $stmtSel->get_result();
                    $balanceAmt = $resultSel->fetch_assoc()['balance_amount'];
                    $stmtSel->close();
                    // Update payment with new balance
                    // $isNotPay = 0;
                    // if($balanceAmt == 0){
                    //     $isNotPay = 1;
                    // }
                    $updtPayQuery = "UPDATE payments SET balance_amt = ? WHERE id = ?";
                    $updtPayStmt = $this->conn->prepare($updtPayQuery);
                    $updtPayStmt->bind_param("ii", $balanceAmt, $lastId);
                    $updtPayStmt->execute();
                    $updtPayStmt->close();
                    // Mark EMI finished if balance is 0
                    if ($balanceAmt == 0) {
                        $finishQuery = "UPDATE emi SET is_finished = 1 WHERE id = ?";
                        $finishStmt = $this->conn->prepare($finishQuery);
                        $finishStmt->bind_param("i", $emiId);
                        $finishStmt->execute();
                        $finishStmt->close();
                    }
                }
            }
    
            $msg = count($not_inserted_datas) ? "Some Inserted Not All" : "success";
            return ["message" => $msg, "not_inserted" => $not_inserted_datas];
    
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage()];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }
    

    // public function insert_new_entry_payments($params){
    //     try {
    //         $currentTime = date('H:i:s');
    //         if(!isset($params['payments']) || !isset($params['date']) || !isset($params['agent_id'])){
    //             return ['message'=>"Give All Datas"];
    //         }
    //         $agent_id = $params['agent_id'];
    //         $payments = $params['payments'];
    //         $date = $params['date'];
    //         $collectorId = 54;

    //         foreach($payments as $payment){
    //             // if(!isset($payment['member_id']))
    //             $getEmiQuery = "SELECT * FROM emi WHERE agent_id = ? AND member_id = ? ORDER BY id DESC LIMIT 1";
    //             $getEmiStmt = $this->conn->prepare($getEmiQuery);
    //             $getEmiStmt->bind_param('ii', $agent_id, $payment['உ.எண்']);
    //             $getEmiStmt->execute();
    //             $result = $getEmiStmt->get_result();
    //             $row_count = mysqli_num_rows($result);
    //             $data = [];
    //             while ($row = $result->fetch_assoc()) {
    //                 $data['emi_id'] = $row['id'];
    //                 $data['member_id'] = $row['member_id'];
    //             }
    //             if($row_count == 0){
    //                 return ['message'=>'Try Again'];
    //             }
    //             $getPay = "SELECT * FROM payments WHERE emi_id = ? AND date = ? ORDER BY id DESC LIMIT 1";
    //             $getPayStmt = $this->conn->prepare($getPay);
    //             $getPayStmt->bind_param("is", $data['emi_id'], $date);
    //             $getPayStmt->execute();
    //             $getPayresult = $getPayStmt->get_result();
    //             $getPay_row_count = mysqli_num_rows($getPayresult);
    //             $not_inserted_datas = [];
    //             while ($row = $getPayresult->fetch_assoc()) {
    //                 $lastId = $row['id'];
    //                 if($row['is_not_paid'] == 1){
    //                     $isNotPaid = 0;
    //                     $query = "UPDATE payments SET amount = ?, is_not_paid = ? WHERE id = ?";
    //                     $stmt = $this->conn->prepare($query);
    //                     $stmt->bind_param("iii", $payment['ரசீது தொகை'], $isNotPaid, $lastId);
    //                     $stmt->execute();
    //                 }
    //                 else{
    //                     $not_inserted_datas[] = $data['member_id'];
    //                     continue;
    //                 }
    //             }
    //             if($getPay_row_count == 0){
    //                 $isNotPaid = 0;
    //                 $query = "INSERT INTO payments (collector_id, emi_id, amount, is_not_paid, date,time) VALUES (?, ?, ?, ?, ?, ?)";
    //                 $stmt = $this->conn->prepare($query);
    //                 $stmt->bind_param("iiiiss", $collectorId,  $data['emi_id'], $payment['ரசீது தொகை'], $isNotPaid, $date,$currentTime);
    //                 $stmt->execute();
    //                 $lastId = $this->conn->insert_id;
    //             }
    //             // if($payment['is_not_paid'] == 0){
    //                 $amt = $payment['ரசீது தொகை'];
    //                 $memberId = $data['member_id'];
    //                 $emiId = $data['emi_id'];
    //                 $updtQuery = "UPDATE emi SET balance_amount = balance_amount-$amt WHERE id = $emiId";
    //                 $updtStmt = $this->conn->prepare($updtQuery);
    //                 $updtStmt->execute();

    //                 $selQuery = "SELECT balance_amount FROM emi WHERE id = $emiId";
    //                 $stmtSel = $this->conn->prepare($selQuery);
    //                 $stmtSel->execute();
    //                 $resultSel = $stmtSel->get_result();
    //                 $balanceAmt = NULL;
    //                 while ($member = $resultSel->fetch_assoc()) {
    //                     $balanceAmt = $member['balance_amount'];
    //                 }

    //                 $updtPayQuery = "UPDATE payments SET balance_amt = $balanceAmt WHERE id = $lastId";
    //                 $updtPayStmt = $this->conn->prepare($updtPayQuery);
    //                 $updtPayStmt->execute();

    //                 if($balanceAmt == 0){
    //                     $finishQuery = "UPDATE emi SET is_finished = 1 WHERE id = $memberId";
    //                     $finishStmt = $this->conn->prepare($finishQuery);
    //                     $finishStmt->execute();
    //                 }
    //             // }
    //         }
    //         $msg = count($not_inserted_datas) ? "Some Inserted Not All" : "success";

    //         return ["message"=>$msg, "not_inserted"=>$not_inserted_datas];
    //     } catch (mysqli_sql_exception $e) {
    //         return ["message" => "Database Error: " . $e->getMessage()];
    //     } catch (Exception $e) {
    //         return ["message" => "Error: " . $e->getMessage()];
    //     }
    // }
    
    public function insert_not_gived($params = NULL) {
        try {
            $date = date('Y-m-d');
            $time = date('H:i:s');
            $not_inserted = [];
            if(isset($params['date'])){
                $date = $params['date'];
            }
            
            // Step 1: Get all ongoing EMIs (not finished)
            $query = "SELECT * FROM emi WHERE is_finished = 0";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->get_result();
    
            while ($emi = $result->fetch_assoc()) {
                $emiId = $emi['id'];
                $memberId = $emi['member_id'];
                $agentId = $emi['agent_id'];
                $balanceAmt = $emi['balance_amount'];
    
                // Step 2: Check if payment exists for this EMI today
                $checkPaymentQuery = "SELECT * FROM payments WHERE emi_id = ? AND date = ?";
                $checkStmt = $this->conn->prepare($checkPaymentQuery);
                $checkStmt->bind_param("is", $emiId, $date);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
    
                if ($checkResult->num_rows > 0) {
                    $payment = $checkResult->fetch_assoc();
                    if ($payment['is_not_paid'] == 0) {
                        // Payment already made, do nothing
                        continue;
                    } else {
                        // Already inserted as "not paid", skip
                        continue;
                    }
                }
                $checkStmt->close();
                // Step 3: Insert payment as not paid (amount = 0)
                $insertQuery = "INSERT INTO payments (collector_id, emi_id, amount, is_not_paid, date, time, balance_amt) 
                                VALUES (?, ?, 0, 1, ?, ?, ?)";
                $insertStmt = $this->conn->prepare($insertQuery);
                $insertStmt->bind_param("iisss", $agentId, $emiId, $date, $time, $balanceAmt);
                $inserted = $insertStmt->execute();
    
                if (!$inserted) {
                    $not_inserted[] = $memberId;
                }
                $insertStmt->close();
            }
            $stmt->close();
            $msg = count($not_inserted) > 0 ? "Some records failed" : "success";
            return ["message" => $msg, "not_inserted" => $not_inserted];
    
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage()];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }

    public function get_collection_wise_reports($params){
        try{
            if(!isset($params['agent_id']) || ((!isset($params['month']) || !isset($params['year'])) && (!isset($params['date'])))){
                return ['message'=>'Give All Values'];
            }
            $agentId = $params['agent_id'];
            
            $firstDate = NULL;
            $lastDate = NULL;

            if(isset($params['month']) || isset($params['year'])){
                $month = $params['month'];
                $year = $params['year'];

                $firstDate = "$year-$month-01";
                $lastDate = date('Y-m-t', strtotime($firstDate));
            }
            else{
                $firstDate = $params['date'];
                $lastDate = $params['date'];
            }
            

            $search_field = ["members.name","agent.name", "members.aadhar_no","members.pan_no","emi.area"];
            // $default_conditions = ["(payments.collector_id = $agentId)","(payments.date >= '$firstDate' && payments.date <= '$lastDate')"];
            $default_conditions = [
    "(payments.date >= '$firstDate' && payments.date <= '$lastDate')"
];

if($agentId != "all"){
    $default_conditions[] = "(payments.collector_id = $agentId)";
}
            $where_clause = $this->Reusable->create_where_condition($params, $default_conditions, $search_field);

            // $query = "SELECT payments.amount, payments.date, payments.balance_amt, a.name AS agent_name, c.name AS collector_name, a.area AS agent_area, members.name AS member_name, emi.area as member_area, emi.loan_amount, emi.date_of_loan FROM payments JOIN emi ON payments.emi_id=  emi.id JOIN agent a ON a.id = emi.agent_id JOIN agent c ON c.id = payments.collector_id JOIN members ON members.id = emi.member_id $where_clause";
            $query = "SELECT payments.amount,
                 payments.date,
                 payments.balance_amt,
                 payments.payment_method,
                 a.name AS agent_name,
                 c.name AS collector_name,
                 a.area AS agent_area,
                 members.name AS member_name,
                 emi.area as member_area,
                 emi.loan_amount,
                 emi.date_of_loan
          FROM payments
          JOIN emi ON payments.emi_id = emi.id
          JOIN agent a ON a.id = emi.agent_id
          JOIN agent c ON c.id = payments.collector_id
          JOIN members ON members.id = emi.member_id
          $where_clause";
            if (!empty($params['sort_by']) && !empty($params['order_by'])) {
                $query = $this->Reusable->sort_function($params['sort_by'], $params['order_by'], $query, 'agent');
            } else {
                $query .= " ORDER BY payments.id";
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
                $payements = [];
                $totAmt = 0;
                $cashTotal = 0;
$gpayTotal = 0;
                foreach ($rows as $result) {
                    if($result['amount']){
                        if(
            isset($result['payment_method']) &&
            strtolower($result['payment_method']) == 'cash'
        ){
            $cashTotal += $result['amount'];
        }

        if(
            isset($result['payment_method']) &&
            strtolower($result['payment_method']) == 'gpay'
        ){
            $gpayTotal += $result['amount'];
        }
                        $payment = [
                            "Agent Name" =>$result['agent_name'],
                            "Area" =>$result['member_area'],
                            "Cust_Name" =>$result['member_name'],
                            "Loan Amount" =>$result['loan_amount'],
                            "Due Date" =>$result['date_of_loan'],
                            "Amount" =>$result['amount'],
                            "Balance" =>$result['balance_amt'],
                            "Entry Date" => $result['date'],
                            "Collected By" => $result['collector_name']
                        ];
                        $totAmt+=$result['amount'];
                        $payements[] = $payment;
                    }
                }
                $stmt->close();
                // return ["message"=>"success","data"=>$payements,"total"=>$totAmt,"query"=>$query];
                return [
    "message" => "success",
    "data" => $payements,
    "cash_total" => $cashTotal,
    "gpay_total" => $gpayTotal,
    "total" => $totAmt,
    "query" => $query
];
            }
            else{
                $stmt->close();
                return ["message"=>"Try Again"];
            }
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage(),"query"=>$query];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }


    public function get_customer_wise_reports($params){
        try{
            if(!isset($params['agent_id']) || empty($params['agent_id']) || !isset($params['customer_id']) || empty($params['customer_id'])){
                return ['message'=>'Give All Values'];
            }
            $agentId = $params['agent_id'];
            $customerId = $params['customer_id'];

            $isNext = true;

            $offset = 0;
            if(isset($params['offset']) && !empty($params['offset'])){
                $countQuery = "SELECT COUNT(id) as times FROM emi WHERE agent_id = ? AND member_id = ?";
                $countStmt = $this->conn->prepare($countQuery);
                $countStmt->bind_param("ii",$agentId,$customerId);
                if ($countStmt->execute()) {
                    $countResult = $countStmt->get_result();
                    $rows = $countResult->fetch_all(MYSQLI_ASSOC);
                    $counts = 0;
                    foreach($rows as $emis){
                        $counts = $emis['times'];
                    }
                    if($counts > $params['offset']){
                        $offset = $params['offset'];
                    }
                    else if($counts == $params['offset']){
                        $offset = $counts-1;
                        $isNext = false;
                    }
                    else{
                        $isNext = false;
                    }
                $countStmt->close();
                }
                $countStmt->close();
            }

            $getQuery = "SELECT * FROM emi WHERE agent_id = ? AND member_id = ? ORDER BY id DESC LIMIT 1 OFFSET $offset";
            $getStmt = $this->conn->prepare($getQuery);
            $getStmt->bind_param("ii",$agentId,$customerId);
            $emiId = NULL;
            if ($getStmt->execute()) {
                $result = $getStmt->get_result();
                $rowCount = $result->num_rows;
                if($rowCount <= 0){
                    return ["message"=>"No Datas Found"];
                }
                $rows = $result->fetch_all(MYSQLI_ASSOC);
                foreach($rows as $emis){
                    $emiId = $emis['id'];
                }
                $getStmt->close();
            }
            else{
                $getStmt->close();
                return ["message"=>"Try Agein"];
            }

            $search_field = ["payments.amount"];

            $default_conditions = ["(emi.id = $emiId)"];
            $where_clause = $this->Reusable->create_where_condition($params, $default_conditions, $search_field);

            $query = "SELECT emi.date_of_loan as emi_date, payments.amount, payments.date, payments.balance_amt, agent.name FROM emi JOIN payments ON payments.emi_id = emi.id JOIN agent ON agent.id = payments.collector_id $where_clause";

            if (!empty($params['sort_by']) && !empty($params['order_by'])) {
                $query = $this->Reusable->sort_function($params['sort_by'], $params['order_by'], $query, 'agent');
            } else {
                $query .= " ORDER BY payments.id";
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
                $payements = [];
                $totAmt = 0;
                foreach ($rows as $result) {
                    if($result['amount']){
                        $payment = [
                            "Due Date" =>$result['emi_date'],
                            "Amount" =>$result['amount'],
                            "Balance" =>$result['balance_amt'],
                            "Date" =>$result['date'],
                            "Collected By" => $result['name']
                        ];
                        $totAmt+=$result['amount'];
                        $payements[] = $payment;
                    }
                }
                $stmt->close();
                return ["message"=>"success","data"=>$payements,"total"=>$totAmt,"isNext" => $isNext];
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

    public function get_new_loan_reports($params){
        try{
            if(!isset($params['agent_id']) || ((!isset($params['month']) || !isset($params['year'])) && (!isset($params['date'])))){
                return ['message'=>'Give All Values'];
            }
            $agentId = $params['agent_id'];
            
            $firstDate = NULL;
            $lastDate = NULL;

            if(isset($params['month']) || isset($params['year'])){
                $month = $params['month'];
                $year = $params['year'];

                $firstDate = "$year-$month-01";
                $lastDate = date('Y-m-t', strtotime($firstDate));
            }
            else{
                $firstDate = $params['date'];
                $lastDate = $params['date'];
            }

            $search_field = ["members.agent_id","agent.name","emi.area"];
            $default_conditions = ["(emi.agent_id = $agentId)","(emi.date_of_loan >= '$firstDate' && emi.date_of_loan <= '$lastDate')"];
            $where_clause = $this->Reusable->create_where_condition($params, $default_conditions, $search_field);

            $query = "SELECT members.name as member_name,members.contact_no,agent.name AS agent_name,emi.loan_amount,emi.date_of_loan,emi.end_date,emi.time FROM emi JOIN members ON members.id = emi.member_id JOIN agent ON agent.id = emi.agent_id $where_clause";

            if (!empty($params['sort_by']) && !empty($params['order_by'])) {
                $query = $this->Reusable->sort_function($params['sort_by'], $params['order_by'], $query, 'emi');
            } else {
                $query .= " ORDER BY emi.id";
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
                $totAmt = 0;
                foreach ($rows as $result) {
                        $emi = [
                            "Agent Name" =>$result['agent_name'],
                            "Cust_Name" =>$result['member_name'],
                            "Loan Amount" =>$result['loan_amount'],
                            "Due Date" =>$result['date_of_loan'],
                        ];
                        $totAmt+=$result['loan_amount'];
                        $emis[] = $emi;
                }
                $stmt->close();
                return ["message"=>"success","data"=>$emis,"total"=>$totAmt,"query"=>$query];
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
    
}
<?php
require '../utils/reusable.php';
class Member_model
{
    private $conn;

    private $Reusable;

    public function __construct($db)
    {
        $this->Reusable = new Reusable();
        $this->conn = $db;
    }

    public function insert_member($params){
        try {
            if(!isset($params['agent_id']) || !isset($params['area']) || !isset($params['name']) || !isset($params['place']) || !isset($params['contact_no']) || !isset($params['balance_amount']) || !isset($params['loan_amount']) || !isset($params['date_of_loan']) || !isset($params['note_id']) || !isset($params['emi_amount'])){
                return ["message"=>"Give All Values"];
            }
            $isBlocked = 0;
            $orderNum = 1;
            // $balanceAmt = 0;
            // $numAdd = 1;
            // $getQuery = "SELECT * FROM emi WHERE agent_id = ?";
            // $getStmt = $this->conn->prepare($getQuery);
            // $getStmt->bind_param("i",$params['agent_id']);
            // if($getStmt->execute()){
            //     $getStmt->store_result();
            //     $numRows = $getStmt->num_rows;
            //     if($numRows>0){
            //         if (!isset($params['order_num']) || $params['order_num'] === null || $params['order_num'] === '') {
            //             $lastQuery = "SELECT * FROM emi e1 WHERE e1.agent_id = ? AND e1.order_num = (SELECT MAX(e2.order_num) FROM emi e2 WHERE e2.member_id = e1.member_id) ORDER BY e1.order_num DESC LIMIT 1";
            //             $lastStmt = $this->conn->prepare($lastQuery);
            //             $lastStmt->bind_param("i", $params['agent_id']);
            //             if ($lastStmt->execute()) {
            //                 $lastResult = $lastStmt->get_result();
            //                 if ($lastRow = $lastResult->fetch_assoc()) {
            //                     $orderNum = $lastRow['order_num']+1;
            //                 }
            //             } else {
            //                 return ["message"=>"Try Again"];
            //             }
            //         }
            //         else{
            //             $orderNum = $params['order_num']+1;
            //             $getAfterOrderNum = "SELECT * FROM emi e1 WHERE agent_id = ? AND e1.order_num = (SELECT MAX(e2.order_num) FROM emi e2 WHERE e2.member_id = e1.member_id) AND order_num >= ?  ORDER BY order_num";
            //             $afterOrderNumStmt = $this->conn->prepare($getAfterOrderNum);
            //             $afterOrderNumStmt->bind_param("ii", $params['agent_id'],$orderNum);
            //             if ($afterOrderNumStmt->execute()) {
            //                 $result_set = $afterOrderNumStmt->get_result();
            //                 $rows = $result_set->fetch_all(MYSQLI_ASSOC);
            //                 $numAdd = 1;
            //                 foreach ($rows as $result) {
            //                     $updtRow = $orderNum + $numAdd;
            //                     $updtQuery = "UPDATE emi SET order_num = ? WHERE id = ?";
            //                     $updtStmt = $this->conn->prepare($updtQuery);
            //                     $updtStmt->bind_param("ii", $updtRow,$result['id']);
            //                     $updtStmt->execute();
            //                     $numAdd++;
            //                 }
            //             } else {
            //                 return ["message"=>"Try Again"];
            //             }
            //         }
            //     }
            // }
            // else{
            //     return ["message"=>"Try Again"];
            // }
            $end_date = new DateTime($params['date_of_loan']);
            $end_date->modify('+100 days');

            $formattedEndDate =  $end_date->format('Y-m-d');

            $is_finished = 0;
            $checkQuery = "SELECT * FROM emi WHERE note_id = ? AND is_finished = ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bind_param("ii", $params['note_id'], $is_finished);
            $checkStmt->execute();
            $result = $checkStmt->get_result();

            if ($result->num_rows > 0) {
                $checkStmt->close();
                return ['message'=>'இந்த உ.எண்-ல் முடிக்கப்படாத EMI கள் உள்ளன.'];
            }
            $checkStmt->close();

            $insQuery = "INSERT INTO members(name,contact_no,is_blocked) VALUES (?,?,?)";
            $inStmt = $this->conn->prepare($insQuery);
            $inStmt->bind_param("sii",$params['name'],$params['contact_no'],$isBlocked);
            if($inStmt->execute()){
                $lastInsId = $this->conn->insert_id;
                $inStmt->close();
                $currTime = date('H:i:s');

                $isFinished = 0;
                
                if($params['balance_amount'] == 0){
                    $isFinished = 1;
                }

                $emiInsQuery = "INSERT INTO emi(note_id,member_id,agent_id,area,place,loan_amount,balance_amount,emi_amount,date_of_loan,end_date,is_finished,time) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
                $emiInStmt = $this->conn->prepare($emiInsQuery);
                $emiInStmt->bind_param("iiissiiissis",$params['note_id'],$lastInsId,$params['agent_id'],$params['area'],$params['place'],$params['loan_amount'],$params['balance_amount'], $params['emi_amount'],$params['date_of_loan'],$formattedEndDate,$isFinished,$currTime);

                if($emiInStmt->execute()){
                    $emiInStmt->close();
                    return ["message"=>"Successfully Added"];
                }
                else{
                    $emiInStmt->close();
                    return ["message"=>"Member Added But Not EMI"];
                }
            }
            else{
                $inStmt->close();
                return ["message"=>"Not Added Try Again"];
            }
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage()];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }

    public function insert_emi($params){
        try {
            if(!isset($params['agent_id']) || !isset($params['balance_amount']) || !isset($params['loan_amount']) || !isset($params['date_of_loan']) || !isset($params['note_id']) || !isset($params['emi_amount']) || !isset($params['id']) || !isset($params['place']) || !isset($params['area'])){
                return ["message"=>"Give All Values"];
            }
            $is_finished = 0;
            $checkQuery1 = "SELECT * FROM emi WHERE member_id = ? AND is_finished = ?";
            $checkStmt1 = $this->conn->prepare($checkQuery1);
            $checkStmt1->bind_param("ii", $params['id'], $is_finished);
            $checkStmt1->execute();
            $result1 = $checkStmt1->get_result();
            
            if ($result1->num_rows > 0) {
                $checkStmt1->close();
                return ['message'=>'இந்த உறுப்பினருக்கு முடிக்கப்படாத EMIகள் உள்ளன.'];
            }
            $checkStmt1->close();
            $isBlocked = 0;

            $end_date = new DateTime($params['date_of_loan']);
            $end_date->modify('+100 days');

            $formattedEndDate =  $end_date->format('Y-m-d');

            $checkQuery = "SELECT * FROM emi WHERE note_id = ? AND is_finished = ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bind_param("ii", $params['note_id'], $is_finished);
            $checkStmt->execute();
            $result = $checkStmt->get_result();

            if ($result->num_rows > 0) {
                $checkStmt->close();
                return ['message'=>'இந்த உ.எண்-ல் முடிக்கப்படாத EMI கள் உள்ளன.'];
            }
            $checkStmt->close();

                $lastInsId = $params['id'];

                $currTime = date('H:i:s');

                $isFinished = 0;
                
                if($params['balance_amount'] == 0){
                    $isFinished = 1;
                }

                $emiInsQuery = "INSERT INTO emi(note_id,member_id,agent_id,area,place,loan_amount,balance_amount,emi_amount,date_of_loan,end_date,is_finished,time) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
                $emiInStmt = $this->conn->prepare($emiInsQuery);
                $emiInStmt->bind_param("iiissiiissis",$params['note_id'],$lastInsId,$params['agent_id'],$params['area'],$params['place'],$params['loan_amount'],$params['balance_amount'], $params['emi_amount'],$params['date_of_loan'],$formattedEndDate,$isFinished,$currTime);

                if($emiInStmt->execute()){
                    $emiInStmt->close();
                    return ["message"=>"Successfully Added"];
                }
                else{
                    $emiInStmt->close();
                    return ["message"=>"Member Added But Not EMI"];
                }
            
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage()];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }

    public function get_members($params){
        try {
            $isBlocked = 0;
            if(isset($params['is_blocked'])){
                $isBlocked = $params['is_blocked'];
            }
            $search_field = ["members.name","agent.name","emi.area", "emi.note_id"];
            $default_conditions = ["(members.is_blocked = $isBlocked)","(emi.id = ( SELECT MAX(id) FROM emi AS e WHERE e.member_id = emi.member_id ))"];
            if(isset($params['is_finished'])){
                $isFinishedEmi = $params['is_finished'];
                $default_conditions[] = "(is_finished = $isFinishedEmi)";
            }
            // echo json_encode($default_conditions);
            if(isset($params['agent_name']) && !empty($params['agent_name'])){
                $agentName = $params['agent_name'];
                $default_conditions[] = "(agent.name = '$agentName')";
            }
            if(isset($params['agent_email']) && !empty($params['agent_email'])){
                $agentEmail = $params['agent_email'];
                $default_conditions[] = "(agent.email_id = '$agentEmail')";
            }
            $where_clause = $this->Reusable->create_where_condition($params, $default_conditions, $search_field);

            $query = "SELECT members.id, emi.note_id, members.name,members.contact_no,emi.place,emi.id as emiID,emi.loan_amount,emi.balance_amount, emi.emi_amount,emi.date_of_loan,agent.id as agentId, agent.name as agent_name,emi.area FROM emi JOIN agent ON emi.agent_id = agent.id JOIN members ON members.id = emi.member_id $where_clause";

            if (!empty($params['sort_by']) && !empty($params['order_by'])) {
                $query = $this->Reusable->sort_function($params['sort_by'], $params['order_by'], $query, 'emi');
            } else {
                $query .= " ORDER BY emi.agent_id";
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
                $members = [];

                $prevIdsInAgent = [];
                foreach ($rows as $result) {
                    // $previousMember = [
                    //     "id" => 0,
                    //     "Customer Name" => "In First",
                    //     "orderNumber"=> 0
                    //     // "Address" => $result['address']
                    // ];;
                    // if(isset($prevIdsInAgent[$result['agent_name']])){
                    //     // $previousMember = $prevIdsInAgent[$result['agent_name']];
                    //     $previousMember = [
                    //         "id" => $result['id'],
                    //         "Customer Name" => $prevIdsInAgent[$result['agent_name']]
                    //         // "Address" => $result['address']
                    //     ];
                    // }
                    $member_data = [
                        // members.id, members.name,members.contact_no,members.place,members.loan_amount,members.balance_amount,members.date_of_loan, agent.name as agent_name, agent.area, members.aadhar_no, members.pan_no,
                        "id" => $result['id'],
                        "உ.எண்" => $result['note_id'],
                        "Customer Name" =>$result['name'],
                        "Contact Number" =>$result['contact_no'],
                        "Place" =>$result['place'],
                        "Loan Amount" =>$result['loan_amount'],
                        "Balance Amount" =>$result['balance_amount'],
                        "Date Of Loan" =>$result['date_of_loan'],
                        "Emi Amount" => $result['emi_amount'],
                        "Agent Name" =>$result['agent_name'],
                        "Area" => $result['area'],
                        "hideAgentId"=>$result['agentId'],
                        "hideEmiID"=>$result['emiID']
                        // "Address" => $result['address']
                    ];
                    // $prevIdsInAgent[$result['agent_name']] = $result['name'];
                    $members[] = $member_data;
                }
                $stmt->close();
                return ["message" => "success", "data" => $members, "query" => $query];
            } else {
                $stmt->close();
                throw new Exception("Error executing query");
            }
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage(),"query"=>$query];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }

    public function get_emi_finished_members($params){
        try {
            $isBlocked = 0;
            if(isset($params['is_blocked'])){
                $isBlocked = $params['is_blocked'];
            }
            $search_field = ["members.name","agent.name","emi.area", "emi.note_id"];
            $default_conditions = ["(members.is_blocked = $isBlocked)","(emi.id = ( SELECT MAX(id) FROM emi AS e WHERE e.member_id = emi.member_id ))"];
            if(isset($params['is_finished'])){
                $isFinishedEmi = $params['is_finished'];
                $default_conditions[] = "(is_finished = $isFinishedEmi)";
            }
            // echo json_encode($default_conditions);
            if(isset($params['agent_name']) && !empty($params['agent_name'])){
                $agentName = $params['agent_name'];
                $default_conditions[] = "(agent.name = '$agentName')";
            }
            if(isset($params['agent_email']) && !empty($params['agent_email'])){
                $agentEmail = $params['agent_email'];
                $default_conditions[] = "(agent.email_id = '$agentEmail')";
            }
            $where_clause = $this->Reusable->create_where_condition($params, $default_conditions, $search_field);

            $query = "SELECT members.id, emi.note_id, members.name,members.contact_no,emi.place,emi.id as emiID,emi.loan_amount,emi.balance_amount, emi.emi_amount,emi.date_of_loan,agent.id as agentId, agent.name as agent_name,emi.area FROM emi JOIN agent ON emi.agent_id = agent.id JOIN members ON members.id = emi.member_id $where_clause";

            if (!empty($params['sort_by']) && !empty($params['order_by'])) {
                $query = $this->Reusable->sort_function($params['sort_by'], $params['order_by'], $query, 'emi');
            } else {
                $query .= " ORDER BY emi.agent_id";
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
                $members = [];

                $prevIdsInAgent = [];
                foreach ($rows as $result) {
                    // $previousMember = [
                    //     "id" => 0,
                    //     "Customer Name" => "In First",
                    //     "orderNumber"=> 0
                    //     // "Address" => $result['address']
                    // ];;
                    // if(isset($prevIdsInAgent[$result['agent_name']])){
                    //     // $previousMember = $prevIdsInAgent[$result['agent_name']];
                    //     $previousMember = [
                    //         "id" => $result['id'],
                    //         "Customer Name" => $prevIdsInAgent[$result['agent_name']]
                    //         // "Address" => $result['address']
                    //     ];
                    // }
                    $member_data = [
                        // members.id, members.name,members.contact_no,members.place,members.loan_amount,members.balance_amount,members.date_of_loan, agent.name as agent_name, agent.area, members.aadhar_no, members.pan_no,
                        "id" => $result['id'],
                        "உ.எண்" => $result['note_id'],
                        "Customer Name" =>$result['name'],
                        "Contact Number" =>$result['contact_no'],
                        "Place" =>$result['place'],
                        "Agent Name" =>$result['agent_name'],
                        "Area" => $result['area'],
                        "hideAgentId"=>$result['agentId'],
                        "hideEmiID"=>$result['emiID']
                        // "Address" => $result['address']
                    ];
                    // $prevIdsInAgent[$result['agent_name']] = $result['name'];
                    $members[] = $member_data;
                }
                $stmt->close();
                return ["message" => "success", "data" => $members, "query" => $query];
            } else {
                $stmt->close();
                throw new Exception("Error executing query");
            }
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage(),"query"=>$query];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }

    // public function get_emi_available_members($params){
    //     try {
    //         $isBlocked = 0;
    //         if(isset($params['is_blocked'])){
    //             $isBlocked = $params['is_blocked'];
    //         }
    //         $search_field = ["members.name","agent.name", "members.aadhar_no","members.pan_no","emi.area, emi.note_id"];
    //         $default_conditions = ["(members.is_blocked = $isBlocked)","(emi.id = ( SELECT MAX(id) FROM emi AS e WHERE e.member_id = emi.member_id))"];
    //         if(isset($params['agent_name']) && !empty($params['agent_name'])){
    //             $agentName = $params['agent_name'];
    //             $default_conditions[] = "(agent.name = '$agentName')";
    //         }
    //         if(isset($params['agent_email']) && !empty($params['agent_email'])){
    //             $agentEmail = $params['agent_email'];
    //             $default_conditions[] = "(agent.email_id = '$agentEmail')";
    //         }
    //         $where_clause = $this->Reusable->create_where_condition($params, $default_conditions, $search_field);

    //         $query = "SELECT members.id, emi.note_id, members.name,members.contact_no,emi.place,emi.id as emiID,emi.loan_amount,emi.balance_amount, emi.emi_amount,emi.date_of_loan,agent.id as agentId, agent.name as agent_name,emi.area FROM emi JOIN agent ON emi.agent_id = agent.id JOIN members ON members.id = emi.member_id $where_clause";


    //         if (!empty($params['sort_by']) && !empty($params['order_by'])) {
    //             $query = $this->Reusable->sort_function($params['sort_by'], $params['order_by'], $query, 'emi');
    //         } else {
    //             $query .= " ORDER BY emi.agent_id";
    //         }

    //         if (isset($params['limit']) && is_numeric($params['limit'])) {
    //             $limit = intval($params['limit']);
    //             $query .= " LIMIT $limit";

    //             if (isset($params['offset']) && is_numeric($params['offset'])) {
    //                 $offset = intval($params['offset']);
    //                 $query .= " OFFSET $offset";
    //             }
    //         }
    //         $stmt = $this->conn->prepare($query);

    //         if ($stmt->execute()) {
    //             $result_set = $stmt->get_result();

    //             $rows = $result_set->fetch_all(MYSQLI_ASSOC);
    //             $members = [];

    //             $prevIdsInAgent = [];
    //             foreach ($rows as $result) {
    //                 // $previousMember = [
    //                 //     "id" => 0,
    //                 //     "Customer Name" => "In First",
    //                 //     "orderNumber"=> 0
    //                 //     // "Address" => $result['address']
    //                 // ];;
    //                 // if(isset($prevIdsInAgent[$result['agent_name']])){
    //                 //     // $previousMember = $prevIdsInAgent[$result['agent_name']];
    //                 //     $previousMember = [
    //                 //         "id" => $result['id'],
    //                 //         "Customer Name" => $prevIdsInAgent[$result['agent_name']]
    //                 //         // "Address" => $result['address']
    //                 //     ];
    //                 // }
    //                 $member_data = [
    //                     // members.id, members.name,members.contact_no,members.place,members.loan_amount,members.balance_amount,members.date_of_loan, agent.name as agent_name, agent.area, members.aadhar_no, members.pan_no,
    //                     "id" => $result['id'],
    //                     "உ.எண்" => $result['note_id'],
    //                     "Customer Name" =>$result['name'],
    //                     "Contact Number" =>$result['contact_no'],
    //                     "Place" =>$result['place'],
    //                     "Loan Amount" =>$result['loan_amount'],
    //                     "Balance Amount" =>$result['balance_amount'],
    //                     "Date Of Loan" =>$result['date_of_loan'],
    //                     "Emi Amount" => $result['emi_amount'],
    //                     "Agent Name" =>$result['agent_name'],
    //                     "Area" => $result['area'],
    //                     "hideAgentId"=>$result['agentId'],
    //                     "hideEmiID"=>$result['emiID']
    //                     // "Address" => $result['address']
    //                 ];
    //                 // $prevIdsInAgent[$result['agent_name']] = $result['name'];
    //                 $members[] = $member_data;
    //             }

    //             return ["message" => "success", "data" => $members, "query" => $query];
    //         } else {
    //             throw new Exception("Error executing query");
    //         }
    //     } catch (mysqli_sql_exception $e) {
    //         return ["message" => "Database Error: " . $e->getMessage(),"query"=>$query];
    //     } catch (Exception $e) {
    //         return ["message" => "Error: " . $e->getMessage()];
    //     }
    // }

    public function get_one_members($params){
        try {
            $isBlocked = 0;
            if(isset($params['is_blocked'])){
                $isBlocked = $params['is_blocked'];
            }
            $search_field = [];
            if(!isset($params['id']) && !isset($params['name'])){
                return ['message'=>'Give Id or Name'];
            }
            if(isset($params['id'])){
                $memId = $params['id'];
            }
            else{
                $memGet = "SELECT members.id FROM members JOIN emi ON emi.member_id = members.id WHERE members.name = ? ";
                if(isset($params['emi_amount'])){
                    $memGet .= " AND emi_amount = ?";
                }
                if(isset($params['emi_amount'])){
                    $memGet .=" ORDER BY emi.id DESC LIMIT 1";                
                }
                else{
                    $memGet .=" LIMIT 1";
                }
                $stmt = $this->conn->prepare($memGet);
                if(isset($params['emi_amount'])){
                    $stmt->bind_param("si",$params['name'], $params['emi_amount']);
                }
                else{
                    $stmt->bind_param("s", $params['name']);
                }
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $idM = $row['id'];
                    $is_finish = 0;
                    $stmt->close();
                    $getNoteId = "SELECT note_id FROM emi WHERE member_id = ? AND is_finished = ? ORDER BY id DESC LIMIT 1";
                    $getNoteStmt = $this->conn->prepare($getNoteId);
                    $getNoteStmt->bind_param("ii", $idM, $is_finish);
                    $getNoteStmt->execute();
                    $getNoteResult = $getNoteStmt->get_result();
                    if ($getNoteResult->num_rows > 0) {
                        $noteRow = $getNoteResult->fetch_assoc();
                        $memId = $noteRow['note_id'];
                        $getNoteStmt->close();
                    }
                    else{
                        $getNoteStmt->close();
                        return ['message'=>'There is No EMI'];
                    }
                } else {
                    $stmt->close();
                    return ['message'=>'There is No Members'];
                }
            }
            $default_conditions = ["(members.is_blocked = $isBlocked)","(emi.id = ( SELECT MAX(id) FROM emi AS e WHERE e.member_id = emi.member_id ))","(emi.note_id = $memId)","(emi.is_finished = 0)"];
            
            if(isset($params['agent_name']) && !empty($params['agent_name'])){
                $agentName = $params['agent_name'];
                $default_conditions[] = "(agent.name = '$agentName')";
            }
            if(isset($params['agent_email']) && !empty($params['agent_email'])){
                $agentEmail = $params['agent_email'];
                $default_conditions[] = "(agent.email_id = '$agentEmail')";
            }
            $where_clause = $this->Reusable->create_where_condition($params, $default_conditions, $search_field);

            $query = "SELECT members.id, members.name,emi.id as emi_id, emi.note_id, emi.place, emi.loan_amount,emi.balance_amount,emi.date_of_loan, emi.emi_amount,agent.id as agentId, agent.name as agent_name FROM emi JOIN agent ON emi.agent_id = agent.id JOIN members ON members.id = emi.member_id $where_clause ORDER BY emi.id DESC LIMIT 1";

            // if (!empty($params['sort_by']) && !empty($params['order_by'])) {
            //     $query = $this->Reusable->sort_function($params['sort_by'], $params['order_by'], $query, 'emi');
            // } else {
            //     $query .= " ORDER BY emi.agent_id,emi.order_num";
            // }

            // if (isset($params['limit']) && is_numeric($params['limit'])) {
            //     $limit = intval($params['limit']);
            //     $query .= " LIMIT $limit";

            //     if (isset($params['offset']) && is_numeric($params['offset'])) {
            //         $offset = intval($params['offset']);
            //         $query .= " OFFSET $offset";
            //     }
            // }
            $stmt = $this->conn->prepare($query);

            if ($stmt->execute()) {
                $result_set = $stmt->get_result();

                $rows = $result_set->fetch_all(MYSQLI_ASSOC);
                $members = [];
                $message = "No Data";
                foreach ($rows as $result) {
                    $members = [
                        "id" => $result['id'],
                        "உ.எண்"=>$result['note_id'],
                        "Customer Name" =>$result['name'],
                        "Loan Amount" =>$result['loan_amount'],
                        "Balance Amount" =>$result['balance_amount'],
                        "emi_amount" => $result['emi_amount'] < $result['balance_amount'] ? $result['emi_amount'] : $result['balance_amount'],
                        "emi_id" =>$result['emi_id'],
                        "place" =>$result['place'],
                        // "hideAgentId"=>$result['agentId'],
                        // "hideEmiID"=>$result['emiID']
                        // "Address" => $result['address']
                    ];
                    $message = "success";
                }
                $stmt->close();
                return ["message" => $message, "data" => $members, "query" => $query];
            } else {
                $stmt->close();
                throw new Exception("Error executing query");
            }
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage(),"query"=>$query];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }

    public function get_members_pdf($params){
        try {
            $isBlocked = 0;
            if(isset($params['is_blocked'])){
                $isBlocked = $params['is_blocked'];
            }
            $search_field = ["members.name","agent.name", "members.aadhar_no","members.pan_no","emi.area"];
            $default_conditions = ["(members.is_blocked = $isBlocked)","(emi.id = ( SELECT MAX(id) FROM emi AS e WHERE e.member_id = emi.member_id ))"];
            if(isset($params['agent_name']) && !empty($params['agent_name'])){
                $agentName = $params['agent_name'];
                $default_conditions[] = "(agent.name = '$agentName')";
            }
            if(isset($params['agent_email']) && !empty($params['agent_email'])){
                $agentEmail = $params['agent_email'];
                $default_conditions[] = "(agent.email_id = '$agentEmail')";
            }
            $where_clause = $this->Reusable->create_where_condition($params, $default_conditions, $search_field);

            $query = "SELECT members.id, members.name,members.contact_no,emi.place,emi.id as emiID,emi.loan_amount,emi.balance_amount,emi.date_of_loan,agent.id as agentId, agent.name as agent_name,emi.area FROM emi JOIN agent ON emi.agent_id = agent.id JOIN members ON members.id = emi.member_id $where_clause";

            if (!empty($params['sort_by']) && !empty($params['order_by'])) {
                $query = $this->Reusable->sort_function($params['sort_by'], $params['order_by'], $query, 'emi');
            } else {
                $query .= " ORDER BY emi.agent_id";
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
                $members = [];

                $prevIdsInAgent = [];
                foreach ($rows as $result) {
                    // $previousMember = [
                    //     "id" => 0,
                    //     "Customer Name" => "In First",
                    //     "orderNumber"=> 0
                    //     // "Address" => $result['address']
                    // ];;
                    // if(isset($prevIdsInAgent[$result['agent_name']])){
                    //     // $previousMember = $prevIdsInAgent[$result['agent_name']];
                    //     $previousMember = [
                    //         "id" => $result['id'],
                    //         "Customer Name" => $prevIdsInAgent[$result['agent_name']],
                    //         "orderNumber"=> $result['order_num']
                    //         // "Address" => $result['address']
                    //     ];
                    // }
                    $member_data = [
                        // members.id, members.name,members.contact_no,members.place,members.loan_amount,members.balance_amount,members.date_of_loan, agent.name as agent_name, agent.area, members.aadhar_no, members.pan_no,
                        "id" => $result['id'],
                        "Name" =>$result['name'],
                        "Contact" =>$result['contact_no'],
                        "Place" =>$result['place'],
                        "Loan" =>$result['loan_amount'],
                        "Balance" =>$result['balance_amount'],
                        "Date" =>$result['date_of_loan'],
                        "Agent Name" =>$result['agent_name'],
                        "Area" => $result['area']
                        // "Address" => $result['address']
                    ];
                    // $prevIdsInAgent[$result['agent_name']] = $result['name'];
                    $members[] = $member_data;
                }
                $stmt->close();
                return ["message" => "success", "data" => $members, "query" => $query];
            } else {
                $stmt->close();
                throw new Exception("Error executing query");
            }
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage(),"query"=>$query];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }
    
    public function get_deleted_members($params){
        try {
            $isBlocked = 1;
            if(isset($params['is_blocked'])){
                $isBlocked = $params['is_blocked'];
            }
            $search_field = ["members.name"];
            $default_conditions = ["(members.is_blocked = $isBlocked)"];

            $where_clause = $this->Reusable->create_where_condition($params, $default_conditions, $search_field);

            $query = "SELECT members.id, members.name,members.contact_no FROM members $where_clause";

            if (!empty($params['sort_by']) && !empty($params['order_by'])) {
                $query = $this->Reusable->sort_function($params['sort_by'], $params['order_by'], $query, 'members');
            } else {
                $query .= " ORDER BY members.id";
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
                $members = [];

                foreach ($rows as $result) {
                    $member_data = [
                        // members.id, members.name,members.contact_no,members.place,members.loan_amount,members.balance_amount,members.date_of_loan, agent.name as agent_name, agent.area, members.aadhar_no, members.pan_no,
                        "id" => $result['id'],
                        "Customer Name" =>$result['name'],
                        "Contact Number" =>$result['contact_no']
                        // "Address" => $result['address']
                    ];
                    $members[] = $member_data;
                }
                $stmt->close();
                return ["message" => "success", "data" => $members, "query" => $query];
            } else {
                $stmt->close();
                throw new Exception("Error executing query");
            }
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage(),"query"=>$query];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }

    public function restore_member($params){
        try {
            if(!isset($params['id']) || empty($params['id'])){
                return ['message'=>"Give Id"];
            }
            $isBlocked = 0;
            $blockQuery = "UPDATE members SET is_blocked = ? WHERE id = ?";
            $blockStmt = $this->conn->prepare($blockQuery);
            $blockStmt->bind_param("ii",$isBlocked,$params['id']);
            if($blockStmt->execute()){
                $blockStmt->close();
                return ["message"=>"Successfully Restored"];
            }
            else{
                $blockStmt->close();
                return ["message"=>"Try Again"];
            }
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage(),"query"=>$query];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }

    public function get_after_members($params){
        try{
            $isBlocked = 0;
            if(isset($params['is_blocked'])){
                $isBlocked = $params['is_blocked'];
            }
            if(empty($params['agent_id']) && !isset($params['agent_id'])){
                return ["message"=>"Give Agent Id"];
            }

            $agentid = $params['agent_id'];

            $search_field = ["members.name","agent.name", "members.aadhar_no","members.pan_no","agent.area"];
            $default_conditions = ["(members.is_blocked = $isBlocked)","(emi.agent_id = $agentid)","(emi.id = ( SELECT MAX(id) FROM emi AS e WHERE e.member_id = emi.member_id ))"];
            $where_clause = $this->Reusable->create_where_condition($params, $default_conditions, $search_field);

            $query = "SELECT emi.member_id, emi.agent_id, members.name, emi.order_num FROM emi JOIN members ON emi.member_id = members.id $where_clause";

            if (!empty($params['sort_by']) && !empty($params['order_by'])) {
                $query = $this->Reusable->sort_function($params['sort_by'], $params['order_by'], $query, 'emi');
            } else {
                $query .= " ORDER BY emi.agent_id DESC,emi.order_num ASC";
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
                $members = [];
                $default_data = [
                    "id" => 0,
                    "Customer Name" => "Add At First",
                    "orderNumber"=> 0
                    // "Address" => $result['address']
                ];
                $members[] = $default_data;

                $prevIdsInAgent = [];
                foreach ($rows as $result) {
                    $member_data = [
                        "id" => $result['member_id'],
                        "Customer Name" =>$result['name'],
                        "orderNumber"=>$result['order_num']
                        // "Address" => $result['address']
                    ];
                    $members[] = $member_data;
                }
                $stmt->close();
                return ["message" => "success", "data" => $members, "query" => $query];
            } else {
                $stmt->close();
                throw new Exception("Error executing query");
            }
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage(),"query"=>$query];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }

    public function delete_member($params){
        try{
            if(!isset($params['id']) || empty($params['id'])){
                return ['message'=>"Give Id"];
            }
            $isBlocked = 1;
            $blockQuery = "UPDATE members SET is_blocked = ? WHERE id = ?";
            $blockStmt = $this->conn->prepare($blockQuery);
            $blockStmt->bind_param("ii",$isBlocked,$params['id']);
            if($blockStmt->execute()){
                $blockStmt->close();
                return ["message"=>"Successfully Deleted"];
            }
            else{
                $blockStmt->close();
                return ["message"=>"Try Again"];
            }
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage()];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }

    public function update_member($params){
        try{
            $getAfterOrderNum;
            if(!isset($params['id']) || !isset($params['agent_id']) || !isset($params['area']) || !isset($params['name'])  || !isset($params['place']) || !isset($params['contact_no']) || !isset($params['loan_amount']) || !isset($params['balance_amount']) || !isset($params['date_of_loan']) || !isset($params['note_id']) || !isset($params['emi_amount'])){
                return ['message'=>"Give Proper Values"];
            }
            $is_finished = 0;
            $checkQuery = "SELECT * FROM emi WHERE note_id = ? AND is_finished = ? AND id != ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bind_param("iii", $params['note_id'], $is_finished, $params['id']);
            $checkStmt->execute();
            $result = $checkStmt->get_result();

            if ($result->num_rows > 0) {
                $checkStmt->close();
                return ['message'=>'இந்த உ.எண்-ல் முடிக்கப்படாத EMI கள் உள்ளன.'];
            }
            $checkStmt->close();

            $end_date = new DateTime($params['date_of_loan']);
            $end_date->modify('+100 days');

            $formattedEndDate =  $end_date->format('Y-m-d');

            $getQuery = "SELECT * FROM emi WHERE id = ?";
            $memberId = NULL;
            $agentId = NULL;
            $getStmt = $this->conn->prepare($getQuery);
            $getStmt->bind_param("i", $params['id']);
            if ($getStmt->execute()) {
                $result_set = $getStmt->get_result();
                $rows = $result_set->fetch_all(MYSQLI_ASSOC);
                $getStmt->close();
                $pendingAmt = 0;
                $prevOrderNum = 0;
                foreach ($rows as $result) {
                    $memberId = $result['member_id'];
                    $agentId = $result['agent_id'];
                    // $pendingAmt = $result['balance_amount'];
                    // $prevOrderNum = $result['order_num'];
                }
                $isFinished = 0;
                // $balAmount = $pendingAmt+$params['loan_amount'];
                $updtQuery = "UPDATE members SET name= ?, contact_no = ? WHERE id = ?";
                $updtStmt = $this->conn->prepare($updtQuery);
                $updtStmt->bind_param("sii",$params['name'],$params['contact_no'],$memberId);

                if($updtStmt->execute()){
                    $is_fin = 1;
                    $checkQuery = "SELECT * FROM emi WHERE is_finished = ? AND id = ?";
                    $checkStmt = $this->conn->prepare($checkQuery);
                    $checkStmt->bind_param("ii", $is_fin, $params['id']);
                    $checkStmt->execute();
                    $result = $checkStmt->get_result();

                    if ($result->num_rows > 0) {
                        $checkStmt->close();
                        return ['message'=>'You cannot Edit the Finished Emi, But Member Details Edited '];
                    }
                    $checkStmt->close();
                    if($params['balance_amount'] == 0){
                        $isFinished = 1;
                    }
                    $updtEmiQuery = "UPDATE emi SET note_id = ?,member_id= ?, agent_id = ?,area = ?, place = ?,loan_amount = ?, balance_amount = ?, emi_amount = ?,date_of_loan = ?,end_date = ?, is_finished = ? WHERE id = ?";
                    $updtEmiStmt = $this->conn->prepare($updtEmiQuery);
                    $updtEmiStmt->bind_param("iiissiiissii", $params['note_id'],$memberId,$agentId,$params['area'],$params['place'],$params['loan_amount'],$params['balance_amount'], $params['emi_amount'],$params['date_of_loan'],$formattedEndDate, $isFinished,$params['id']);
                    if($updtEmiStmt->execute()){
                        $updtEmiStmt->close();
                        return ["message"=>"Successfully Updated"];
                    }
                    else{
                        $updtEmiStmt->close();
                        return ["message"=>"Try Again"];
                    }
                }
                else{
                    $updtStmt->close();
                    return ["message"=>"Try Again"];
                }
            }
            else{
                $getStmt->close();
                return ["message"=>"Try Again"];
            }
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage()];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }

    public function insert_and_finish_emi($params){
        try{
            if(!isset($params['emi_id']) || !isset($params['loan_amount']) || !isset($params['date_of_loan'])){
                return ['message'=>"Give Proper Values"];
            }

            $isFinished = 1;
            $balAmount = 0;

            $updtQuery = "UPDATE emi SET is_finished = ?,balance_amount = ? WHERE id = ?";
            $updtStmt = $this->conn->prepare($updtQuery);
            $updtStmt->bind_param("iii",$isFinished, $balAmount, $params['emi_id']);
            if ($updtStmt->execute()) {
                $updtStmt->close();
                $getQuery = "SELECT * FROM emi WHERE id = ?";
                $getStmt = $this->conn->prepare($getQuery);
                $getStmt->bind_param("i", $params['emi_id']);
                if($getStmt->execute()){
                    $result_set = $getStmt->get_result();
                    if ($result_set->num_rows <= 0) {
                        return ['message'=>"There is No EMI's"];
                    }
                    $rows = $result_set->fetch_all(MYSQLI_ASSOC);
                    $getStmt->close();
                    $memberId = NULL;
                    $agentId = NULL;
                    $area = NULL;
                    $place = NULL;
                    // $order_num = NULL;
                    $description = NULL;
                    foreach ($rows as $result) {
                        $memberId = $result['member_id'];
                        $agentId = $result['agent_id'];
                        $area = $result['area'];
                        $place = $result['place'];
                        // $order_num = $result['order_num'];
                        $description = $result['description'];
                    }
                    $end_date = new DateTime($params['date_of_loan']);
                    $end_date->modify('+100 days');
                    $formattedEndDate =  $end_date->format('Y-m-d');

                    $isFinished = 0;
                    $currTime = date('H:i:s');

                    $insQuery = "INSERT INTO emi(member_id,agent_id,area,place,loan_amount,balance_amount,date_of_loan,end_date,is_finished,time,description) VALUES(?,?,?,?,?,?,?,?,?,?,?)";
                    $insStmt = $this->conn->prepare($insQuery);
                    $insStmt->bind_param("iissiississ", $memberId,$agentId,$area,$place,$params['loan_amount'],$params['loan_amount'],$params['date_of_loan'],$formattedEndDate,$isFinished,$currTime,$description);
                    if ($insStmt->execute()) {
                        $insStmt->close();
                        return ["message"=>"success"];
                    }
                    else{
                        $insStmt->close();
                        return ["message"=>"Try Again"];
                    }
                }
                else{
                    $getStmt->close();
                    return ["message"=>"Try Again"];
                }
            }
            else{
                $updtStmt->close();
                return ["message"=>"Try Again"];
            }
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage()];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }

    public function get_members_name_id($params){
        try {
            $isBlocked = 0;
            if(isset($params['is_blocked'])){
                $isBlocked = $params['is_blocked'];
            }
            $search_field = ["name"];
            $default_conditions = ["(is_blocked = $isBlocked)"];
            $where_clause = $this->Reusable->create_where_condition($params, $default_conditions, $search_field);

            $query = "SELECT m.id,m.name,e.place,CASE WHEN e.balance_amount > e.emi_amount THEN e.emi_amount ELSE e.balance_amount END AS emi_amount FROM members m JOIN (SELECT * FROM emi e1 WHERE e1.id = (SELECT MAX(e2.id) FROM emi e2 WHERE e2.member_id = e1.member_id)) e ON e.member_id = m.id $where_clause";

            // if (!empty($params['sort_by']) && !empty($params['order_by'])) {
            //     $query = $this->Reusable->sort_function($params['sort_by'], $params['order_by'], $query, 'emi');
            // } else {
            //     $query .= " ORDER BY id";
            // }

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
                $members = [];
                foreach ($rows as $result) {
                    $member_data = [
                        "id" => $result['id'],
                        "name" =>$result['name'],
                        "emi_amount" =>$result['emi_amount'],
                        "place" =>$result['place'],
                    ];
                    $members[] = $member_data;
                }
                $stmt->close();
                return ["message" => "success", "data" => $members, "query" => $query];
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
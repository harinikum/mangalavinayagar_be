<?php
require '../utils/reusable.php';
class Super_admin_model
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

    public function insert_super_admin($params){
        try {
            if(!isset($params['user_name']) || !isset($params['password'])){
                return ["message"=>"Give ID"];
            }

            $username = $params['user_name'];
            $password = $params['password'];
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Now, store $hashedPassword in your database
            $query = "INSERT INTO super_admin (email, password) VALUES (?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ss", $username, $hashedPassword);
            if($stmt->execute()){
                $stmt->close();
                return ["message"=>"success"];
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

    public function verify_super_admin($params){
        try {
            if(!isset($params['user_name']) || !isset($params['password'])){
                return ["message"=>"Give ID"];
            }

            $username = $params['user_name'];
            $password = $params['password'];

            $query = "SELECT password FROM super_admin WHERE email = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->bind_result($hashedPasswordFromDb);
            $stmt->fetch();

            if($stmt->execute()){
                $stmt->close();
                if (password_verify($password, $hashedPasswordFromDb)) {
                    $adminTokPayload = [
                        'gmail' => $username
                    ];
                    return ["message"=>"success","admin"=>$adminTokPayload];
                } else {
                    return ["message"=>"Invalid username or password."];
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

    public function sent_otp($params){
        try {
            if(!isset($params['user_name'])){
                return ["message"=>"Give ID"];
            }

            $username = $params['user_name'];

            $query = "SELECT email FROM super_admin WHERE email = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $otp = rand(1000, 9999);
                $expiry_time = date("Y-m-d H:i:s", strtotime('+5 minutes'));
                $stmt->close();
                $otpGetQuery = "SELECT * FROM otp WHERE email = ?";
                $otpGetStmt = $this->conn->prepare($otpGetQuery);
                $otpGetStmt->bind_param("s", $username);
                $otpGetStmt->execute();
                $otpGetStmt->store_result();
                if ($otpGetStmt->num_rows > 0) {
                    $otpGetStmt->close();
                    $updQuery = "UPDATE otp SET otp_code = ?,otp_expiry = ? WHERE email = ?";
                    $updStmt = $this->conn->prepare($updQuery);
                    $updStmt->bind_param("sss", $otp, $expiry_time, $username);
                    if ($updStmt->execute()) {
                        $updStmt->close();
                        $sented = mail($username, "Your OTP Code", "Your OTP is: $otp");
                        if($sented){
                            return ['message' => 'OTP sent successfully'];
                        }
                        else{
                            return ['message' => 'Failed to send OTP'];
                        }
                    } else {
                        $updStmt->close();
                        return ['message' => 'Failed to send OTP'];
                    }
                }
                else{
                    $otpGetStmt->close();
                    $insertQuery = "INSERT INTO otp(otp_code, otp_expiry, email) VALUES (?,?,?)";
                    $insertStmt = $this->conn->prepare($insertQuery);
                    $insertStmt->bind_param("sss", $otp, $expiry_time, $username);
                
                    if ($insertStmt->execute()) {
                        $insertStmt->close();
                        $sented = mail($username, "Your OTP Code", "Your OTP is: $otp");
                        if($sented){
                            return ['message' => 'OTP sent successfully'];
                        }
                        else{
                            return ['message' => 'Failed to send OTP'];
                        }
                    } else {
                        $insertStmt->close();
                        return ['message' => 'Failed to send OTP'];
                    }
                } 
            } else {
                $stmt->close();
                return ['message' => 'Email does not exist'];
            }
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage()];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }
    
    public function verify_otp($params){
        try {
            if(!isset($params['user_name'])){
                return ["message"=>"Give ID"];
            }

            $email = $params['user_name'];
            $otp = $params['otp'];

            if (!$email || !$otp) {
                return ['message' => 'Phone and OTP are required'];
            }

            // Check OTP and expiry
            $stmt = $this->conn->prepare("SELECT otp_code, otp_expiry FROM otp WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->bind_result($dbOtp, $expiry);
            $stmt->fetch();
            $stmt->close();
            if ($dbOtp && $otp === $dbOtp && new DateTime() < new DateTime($expiry)) {
                // OTP is valid
                $adminTokPayload = [
                    'gmail' => $email
                ];
                return ['message' => 'success',"admin"=>$adminTokPayload];
            } else {
                // OTP is invalid or expired
                return ['message' => 'Invalid or expired OTP'];
            }
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage()];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }
    
    public function change_password($params){
        try {
            if(!isset($params['password']) || !isset($params['confirm_password']) || !isset($params['user_name'])){
                return ["message"=>"Give Correct Values"];
            }
            $userName = $params["user_name"];
            $password = $params['password'];
            $confirmPassword = $params['confirm_password'];
            if($password == $confirmPassword){

                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                $query = "UPDATE super_admin SET password = ? WHERE email = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("ss", $hashedPassword,$userName);
                if($stmt->execute()){
                    $stmt->close();
                    return ["message"=>"success"];
                }
                else{
                    $stmt->close();
                    return ["message"=>"Try again"];
                }
            }
            else{
                return ["message"=>"Password and Confirm Password Not Equal"];
            }
        } catch (mysqli_sql_exception $e) {
            return ["message" => "Database Error: " . $e->getMessage()];
        } catch (Exception $e) {
            return ["message" => "Error: " . $e->getMessage()];
        }
    }
}

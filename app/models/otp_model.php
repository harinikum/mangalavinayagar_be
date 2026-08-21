<?php
require '../utils/reusable.php';
class Otp_model
{
    private $conn;

    private $Reusable;

    public function __construct($db)
    {
        $this->Reusable = new Reusable();
        $this->conn = $db;
    }

    public function verify_otp_update_pass($params = []){
        try {
            if(!isset($params['gmail']) || !isset($params['otp']) || !isset($params['newPassword']) || !isset($params['confirmNewPassword'])){
                return ['massage'=>'Give Proper Datas'];
            }
            $gmail = $params['gmail'];
            $otp = $params['otp'];
            $newPassword = $params['newPassword'];
            $confirmNewPassword = $params['confirmNewPassword'];
            if($confirmNewPassword == $newPassword){
                $query = "SELECT * FROM otp WHERE email = '$gmail' AND otp = '$otp' AND exp_time > NOW()";
                $result = mysqli_query($this->conn, $query);

                if ($result->num_rows > 0) {
                    // OTP is valid, update the user's password
                    $hashed_new_password = password_hash($newPassword, PASSWORD_BCRYPT);
                    $updatePass = "UPDATE agent set password='$hashed_new_password' WHERE email_id='$gmail'";
                    $updateRes = mysqli_query($this->conn, $updatePass);
                    return ["message"=>"success"];
                } else {
                    echo json_encode(["message" => "Invalid or expired OTP"]);
                }
            }
            else{
                return ["message"=>"Password and Confirm Password is Not Correct!!."];
            }
        } catch (\Throwable $th) {
            return ["message"=>"Some Error Occurred: " . $th->getMessage()];
        }
    }

    public function generate_otp($params){
        try {
            if(!isset($params['gmail'])){
                return ['message'=>'Give Email'];
            }
            $gmail = $params['gmail'];
            $query = "SELECT * FROM agent WHERE email_id = '$gmail'";
            $result = mysqli_query($this->conn, $query);

            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                $gmail = $row['email_id'];

                $otp = rand(1000, 9999);
                $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

                // Send OTP to user's email
                $sented = mail($gmail, "Your OTP Code", "Your OTP is: $otp");
                $deleteQuery = "DELETE from otp WHERE email='$gmail'";
                $deleteRes = mysqli_query($this->conn, $deleteQuery);
                if($sented){
                    $insQuery = "INSERT INTO otp (email, otp, exp_time) VALUES ('$gmail','$otp','$expires_at')";
                    $insRes = mysqli_query($this->conn, $insQuery);
                    return ["message" => "success"];
                }
                else{
                    return ["message" => "Try Again!!"];
                }
                // }
            } else {
                return ["message" => "Entered Wrong Email Id!!"];
            }
        } catch (\Throwable $th) {
            return ["message"=>"Some Error Occurred: " . $th->getMessage()];
        }
    }
}
?>
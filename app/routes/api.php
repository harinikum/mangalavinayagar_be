<?php
// require_once '../controllers/super_admin_contoller.php';
include('./path.php');
include_once '../utils/header.php';
require_once("../services/jwt_service.php");

class RouteHandler
{
    private $token;
    public function __construct() {
        date_default_timezone_set('Asia/Kolkata');
        $this->token = NULL;
        $currentFile = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if (
    $currentFile !== "verify_agent.php" &&
    $currentFile !== "sent_otp.php" &&
    $currentFile !== "verify_otp.php" &&
    $currentFile !== "generate_otp.php" &&
    $currentFile !== "verify_otp_update_pass.php"
) {
 
        // if ($_SERVER['REQUEST_URI'] !== agent_url . "verify_agent.php" && $_SERVER['REQUEST_URI'] !== super_admin_url . "verify_agent.php" && $_SERVER['REQUEST_URI'] !== super_admin_url . "sent_otp.php" && $_SERVER['REQUEST_URI'] !== super_admin_url . "verify_otp.php" && $_SERVER['REQUEST_URI'] !== super_admin_url."generate_otp.php" && $_SERVER['REQUEST_URI'] !== super_admin_url."verify_otp_update_pass.php") {
            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $authorizationHeader = $_SERVER['HTTP_AUTHORIZATION'];
            }
            if(isset($_SERVER['HTTP_USER'])){
                $admin_id = $_SERVER['HTTP_USER'];
            }
            if(isset($_SERVER['HTTP_ISSUPERADMIN'])){
                $is_super_admin = $_SERVER['HTTP_ISSUPERADMIN'];
            }
            // if(empty($is_super_admin)){
            //     echo json_encode(["message" => "Headers not found"]);
            //     // http_response_code(400);
            //     exit;
            // }
            if (!empty($authorizationHeader)) {
                if (preg_match('/Bearer\s+(.*)$/i', $authorizationHeader, $matches)) {
                    $this->token = new JWT($is_super_admin);
                    $jwt_token = $matches[1];
                    $data = json_decode(file_get_contents('php://input'), true);
                    $payload = [
                        'gmail' => $admin_id ?? null
                    ];
                    $decoded_Data = $this->token->validate($jwt_token, $payload);
                    if ($decoded_Data['message'] !== 'success') {
                        echo json_encode(["message" => $decoded_Data['message']]);
                        // http_response_code(401);
                        exit;
                    }
                } else {
                    echo json_encode(["message" => "Bearer token not found"]);
                    // http_response_code(400);
                    exit;
                }
            } else {
                echo json_encode(["message" => "Authorization header not found"]);
                // http_response_code(401);
                exit;
            } // close else block for empty auth header
        } // close if condition for file verification
    } // close constructor
    

    public function handleRequest()
    {
        $isMultipart = isset($_FILES) && !empty($_FILES);
        $data = null;
    
        if ($isMultipart) {
            $data = $_POST;
        } else {
            $data = json_decode(file_get_contents('php://input'), true);
        }
    
        $routeKey = $_SERVER['REQUEST_METHOD'] . ' ' . strtok($_SERVER['REQUEST_URI'], '?');
        if (array_key_exists($routeKey, routes)) {
            list($className, $methodName) = routes[$routeKey];
            $dynamic_class = strtolower($className); 
            require_once '../controllers/' . $dynamic_class . '.php';  

            if (class_exists($className)) {
                $controller = new $className();
    
                if (method_exists($controller, $methodName)) {
                    if ($isMultipart) {
                        call_user_func([$controller, $methodName], $data, $_FILES);
                    } else {
                        if($_SERVER['REQUEST_URI'] !== agent_url . "verify_agent.php" && $_SERVER['REQUEST_URI'] !== super_admin_url . "verify_agent.php" && $_SERVER['REQUEST_URI'] !== super_admin_url . "sent_otp.php"  && $_SERVER['REQUEST_URI'] !== super_admin_url . "verify_otp.php"){
                            call_user_func([$controller, $methodName], $data);
                        }
                        else{
                            $res = call_user_func([$controller, $methodName], $data);
                            if($res["message"] == "success"){
                                $this->token = new JWT($res['superadmin']);
                                $tokenRet = $this->token->generate($res["admin"]);
                                echo json_encode(["message" => "success","token"=>$tokenRet, "super_admin"=>$res['superadmin'], "id" => $res['id'], "name"=>$res['name']]);
                            }
                            else{
                                echo json_encode(["message" =>$res["message"]]);
                            }
                        }
                    }
                } else {
                    echo json_encode(["message" => "Method not found."]);
                    http_response_code(404);
                }
            } else {
                echo json_encode(["message" =>'Class not found']);
                http_response_code(404);
            }
        } else {
            echo json_encode(["message" => "Route not found."]);
            http_response_code(404);
        }
    }
}

$routeHandler = new RouteHandler();
$routeHandler->handleRequest();
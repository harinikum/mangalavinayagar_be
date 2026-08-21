<?php
class JWT
{
    private array $headers;
    private string $secret;
    private string $audience;

    /**
     * Constructor to initialize JWT with dynamic audience based on user type.
     *
     * @param bool $isSuperadmin Indicates if the user is a superadmin.
     */
    public function __construct($isSuperadmin)
    {
        $iss = "localhost";
        $iat = time();
        $nbf = $iat + 1;
        $exp = $iat + 86400;

        $this->audience = $isSuperadmin == "true" ? 'sUPeRadMin_sAi' : 'agEnTs';
        $this->secret = $isSuperadmin == "true" ? 'SUPeRaDMin^sAi/SeCRet' : 'AgEnTs_SaI_SecREt';

        $this->headers = [
            'alg' => 'HS256', // Algorithm
            'typ' => 'JWT',   // Token type
            'iss' => $iss,    // Issuer
            'aud' => $this->audience, // Dynamic audience
            "iat" => $iat,
            "nbf" => $nbf,
            "exp" => $exp
        ];
    }

    public function generate(array $payload): string
    {
        $headers = $this->encode(json_encode($this->headers)); // Encode headers
        $payload = $this->encode(json_encode($payload));       // Encode payload
        $signature = hash_hmac('SHA256', "$headers.$payload", $this->secret, true); // Create SHA256 signature
        $signature = $this->encode($signature); // Encode signature

        return "$headers.$payload.$signature";
    }

    public function validate(string $jwtToken, array $payload1): array
{
    if (empty($jwtToken)) {
        return ["message" => "JWT is empty"];
    }

    $tokenParts = explode('.', $jwtToken);
    if (count($tokenParts) !== 3) {
        return ["message" => "Invalid token format"];
    }

    list($encodedHeader, $encodedPayload, $encodedSignature) = $tokenParts;

    $decodedHeader = json_decode(base64_decode($encodedHeader));
    $decodedPayload = json_decode(base64_decode($encodedPayload));
    $clientSignature = $encodedSignature;

    if (!$decodedHeader || !$decodedPayload) {
        return ["message" => "Invalid header or payload"];
    }

    if (!isset($decodedHeader->exp) || (time() > $decodedHeader->exp)) {
        return ["message" => "Token has expired"];
    }

    if (isset($decodedHeader->iat) && (time() < $decodedHeader->iat)) {
        return ["message" => "Token was issued in the future"];
    }

    // if (!isset($decodedPayload->gmail) || $payload1['gmail'] !== $decodedPayload->gmail) {
    //     return ["message" => "Invalid email or payload data","decodedPayload"=>$decodedPayload->gmail,"payload1"=>$payload1->gmail];
    // }

     if (
    isset($payload1['gmail']) &&
    (
        !isset($decodedPayload->gmail) ||
        $payload1['gmail'] !== $decodedPayload->gmail
    )
) {
         return [
        "message" => "Invalid email or payload data",
        "decodedPayload" => $decodedPayload->gmail ?? '',
        "payload1" => $payload1['gmail'] ?? ''
    ];
    }

    if (!isset($decodedHeader->aud) || $decodedHeader->aud !== $this->audience) {
        return ["message" => "Invalid audience"];
    }

    $recreatedSignature = hash_hmac('SHA256', "$encodedHeader.$encodedPayload", $this->secret, true);
    $recreatedSignature = $this->encode($recreatedSignature);

    if ($clientSignature !== $recreatedSignature) {
        return ["message" => "Invalid signature"];
    }

    // Parse the current request URL
    $currentUrlPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    // Define allowed endpoint patterns for each audience
    if($this->audience == "agEnTs"){
        $currentUrlPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $restrictedEndpoints = [
            '/finance_desktop_be/super_admin_api/insert_agent.php',
            '/finance_desktop_be/super_admin_api/get_agents.php',
            '/finance_desktop_be/super_admin_api/update_agent.php',
            '/finance_desktop_be/super_admin_api/delete_agent.php',
            '/finance_desktop_be/super_admin_api/update_agent_password.php',
        ];

        foreach ($restrictedEndpoints as $restricted) {
            if (strpos($currentUrlPath, $restricted) === 0) {
                return ["message" => "Access denied: Endpoint restricted for agents"];
            }
        }
    }
    return ["message" => "success", "aud" => $this->audience, "tok_aud"=>$decodedHeader->aud];
}


    private function encode(string $str): string
    {
        return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
    }
}
?>
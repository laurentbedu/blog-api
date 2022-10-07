<?php 

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class AuthController {

public function __construct($params)
{
    $this->method = array_shift($params);

    $request_body = file_get_contents('php://input');
    $this->body = $request_body ? json_decode($request_body, true) : null;

    $this->action = $this->{$this->method}();
}

public function login(){
    $dbs = new DatabaseService("account");
    $email = filter_var($this->body['login'], FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ["result" => false];
    }
    $accounts = $dbs->selectWhere("login = ? AND is_deleted = ?", [$email, 0]);
    $prefix = $_ENV['config']->hash->prefix;
    if(count($accounts) == 1
       && password_verify($this->body['password'], $prefix . $accounts[0]->password)){
       $dbs = new DatabaseService("appUser");
       $appUser = $dbs->selectOne($accounts[0]->Id_appUser);
        
       $secretKey = $_ENV['config']->jwt->secret;
        $issuedAt = time();
        $expireAt = $issuedAt + 60 * 60 * 24;
        $serverName = "blog.api";
        $userRole = $appUser->Id_role;
        $userId =  $appUser->Id_appUser;
        $requestData = [
            'iat'  => $issuedAt,
            'iss'  => $serverName,
            'nbf'  => $issuedAt,
            'exp'  => $expireAt,
            'userRole' => $userRole,
            'userId' => $userId
        ];
        $token = JWT::encode($requestData, $secretKey, 'HS512');

       return ["result" => true, "role" => $appUser->Id_role, "token" => $token];
    }
    return ["result" => false];
}

public function check(){
    $headers = apache_request_headers();
    $token = $headers["Authorization"];
    $secretKey = $_ENV['config']->jwt->secret;
    if(!empty($token)){
        try{
            $payload = JWT::decode($token, new Key($secretKey, 'HS512'));
        }catch(Exception $e){
            $payload = null;
        }
        if (isset($payload) &&
            $payload->iss === "blog.api" &&
            $payload->nbf < time() &&
            $payload->exp > time())
        {
            return ["result" => true, "role" => $payload->userRole];
        }
    }
    return ["result" => false];
}

}?>
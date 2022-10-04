<?php class AuthController {

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
    if(count($accounts) == 1 && password_verify($this->body['password'], $prefix . $accounts[0]->password)){
       $dbs = new DatabaseService("appUser");
       $appUser = $dbs->selectOne($accounts[0]->Id_appUser);
       return ["result" => true, "role" => $appUser->Id_role];
    }
    return ["result" => false];
}



}?>
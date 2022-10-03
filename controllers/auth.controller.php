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
    if(count($accounts) == 1 && $accounts[0]->password == $this->body['password']){
       $dbs = new DatabaseService("appUser");
       $appUser = $dbs->selectOne($accounts[0]->Id_appUser);
       return ["result" => true, "role" => $appUser->Id_role];
    }
    return ["result" => false];
}

}?>
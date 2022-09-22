<?php class TestController {

    public function __construct($params)
    {
        $this->id = array_shift($params);
        $this->action = null;
        if(isset($id) && !ctype_digit($id)){
            return $this;
        }
        $request_body = file_get_contents('php://input');
        $this->body = $request_body ? json_decode($request_body, true) : null;

        $this->table = "article";

        if($_SERVER['REQUEST_METHOD'] == "POST" && isset($this->id)){//POST /test/:id
            //GetOne
            $this->action = $this->getOne($this->id);
        }
        if($_SERVER['REQUEST_METHOD'] == "POST" && !isset($this->id)){//POST /test
            //GetAll
            $this->action = $this->getAll();
        }
        
    }

    function addDataToRow(&$row, $k, $v, $table = null){
        if(!isset($table)){
            $table = $this->table;
        }
        $type = $v['type'];
        
        //1
        if($type==1){
            $dbs = new DatabaseService($k);
            $fk = "Id_".$k;
            if(is_array($row)){
                $ids = array_unique(array_column($row, $fk));
                $ids = '('.implode( ',', $ids ).')';
                $sRows = $dbs->selectWhere("is_deleted = 0 AND Id_$k IN $ids");
                foreach($row as &$r){
                    $items = array_filter(
                        $sRows,
                        function ($e) use ($fk, $r) {
                            return $e->$fk == $r->$fk;
                        }
                    );
                    $r->$k = count($items) == 1 ? array_pop($items) : null;
                }
                $bp = true;
            } 
            else{
                $sId = $row->$fk;
                $sRow = $dbs->selectOne($sId);
                if(count($v)>1){
                    foreach($v as $sk => $sv){
                        if($sk != "type"){
                            $this->addDataToRow($sRow,$sk,$sv,$k);
                        }
                    }
                    
                }
                $row->$k = $sRow;
            }
            
            
            
        }
        //[]
        if(is_array($type)){
            $dbs = new DatabaseService($k);
            $query_resp = $dbs->query("DESCRIBE $k",[]);
            $columns = $query_resp->statement->fetchAll(PDO::FETCH_COLUMN);
            $fk = "Id_".$table;
            
            if(is_array($row)){
                $bp = true;
            }
            $id = $row->$fk;

            if (in_array($fk, $columns)){
                $sRow = $dbs->selectWhere("is_deleted = 0 AND $fk = $id");
                if(count($v)>1){
                    foreach($v as $sk => $sv){
                        if($sk != "type"){
                            $this->addDataToRow($sRow,$sk,$sv,$k);
                        }
                    }
                    $bp = true;
                }
            }
            else{
                $query_resp = $dbs->query("SELECT table_name FROM information_schema.tables
                                    WHERE table_schema = ?", ['db_blog']);
                $tables = $query_resp->statement->fetchAll(PDO::FETCH_COLUMN);
                $rel_table = $table."_".$k;
                if(!in_array($rel_table,$tables)){
                    $rel_table = $k."_".$table;
                }
                $dbs = new DatabaseService($rel_table);
                $fk = "Id_".$table;

                if(is_array($row)){
                    $bp = true;
                }
                $id = $row->$fk;

                $rel_rows = $dbs->selectWhere("$fk = $id");
                $ids = array_column($rel_rows, "Id_".$k);
                $ids = '('.implode( ',', $ids ).')';
                $dbs = new DatabaseService($k);
                $sRow = $dbs->selectWhere("is_deleted = 0 AND Id_$k IN $ids");
                if(count($v)>1){
                    foreach($v as $sk => $sv){
                        if($sk != "type"){
                            $this->addDataToRow($sRow,$sk,$sv,$k);
                        }
                    }
                    $bp = true;
                }
                $bp = true;
            }
            $name = $k."_list";
            $row->$name = $sRow;
        }
    }


    public function getOne($id){
        $dbs = new DatabaseService($this->table);
        $row = $dbs->selectOne($id);
        foreach($this->body as $k=>$v){
            $this->addDataToRow($row, $k, $v);
            // $type = $v['type'];
            // //1
            // if($type==1){
            //     $dbs = new DatabaseService($k);
            //     $fk = "Id_".$k;
            //     $sId = $row->$fk;
            //     $sRow = $dbs->selectOne($sId);
            //     $row->$k = $sRow;
            // }
            // //[]
            // if(is_array($type)){
            //     $dbs = new DatabaseService($k);
            //     $query_resp = $dbs->query("DESCRIBE $k",[]);
            //     $columns = $query_resp->statement->fetchAll(PDO::FETCH_COLUMN);
            //     $fk = "Id_".$this->table;
            //     if (in_array($fk, $columns)){
            //         $sRow = $dbs->selectWhere("is_deleted = 0 AND $fk = $id");
            //     }
            //     else{
            //         $query_resp = $dbs->query("SELECT table_name FROM information_schema.tables
            //                          WHERE table_schema = ?", ['db_blog']);
            //         $tables = $query_resp->statement->fetchAll(PDO::FETCH_COLUMN);
            //         $rel_table = $this->table."_".$k;
            //         if(!in_array($rel_table,$tables)){
            //             $rel_table = $k."_".$this->table;
            //         }
            //         $dbs = new DatabaseService($rel_table);
            //         $fk = "Id_".$this->table;
            //         $rel_rows = $dbs->selectWhere("$fk = $id");
            //         $ids = array_column($rel_rows, "Id_".$k);
            //         $ids = '('.implode( ',', $ids ).')';
            //         $dbs = new DatabaseService($k);
            //         $sRow = $dbs->selectWhere("is_deleted = 0 AND Id_$k IN $ids");
            //         $bp = true;
            //     }
            //     $name = $k."_list";
            //     $row->$name = $sRow;
            // }
        }
        return $row;
    }
    public function getAll(){
        $dbs = new DatabaseService($this->table);

        return true;
    }

}?>
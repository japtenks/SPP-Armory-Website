
<?php

require_once(dirname(__FILE__, 2).'/dbsimple/Mysql.php');
require_once(dirname(__FILE__, 2).'/common.php');


function dbConnect($host,$port,$user,$pass,$name){
    try{
        $db = DbSimple_Generic::connect("mysql://$user:$pass@$host:$port/$name");
        $db->setErrorHandler('dbErrorHandler');
        return $db;
    }catch(Exception $e){
        echo "<div style='color:red'>DB connect failed: ".$e->getMessage()."</div>";
        return null;
    }
}

function dbErrorHandler($message,$info){
    if(!error_reporting()) return;
    if(isset($_GET['debug'])){
        echo "<div style='color:red'>SQL Error: ".htmlspecialchars($message)."</div>";
        if(!empty($info['query'])){
            echo "<pre>".htmlspecialchars($info['query'])."</pre>";
        }
    }
}

function q($conn,$sql,$mode=0){
    if(!$conn) return [];
    try{
        switch($mode){
            case 1: return $conn->selectRow($sql) ?: [];
            case 2: return $conn->selectCell($sql) ?: '';
            default: return $conn->select($sql) ?: [];
        }
    }catch(Exception $e){
        if(isset($_GET['debug'])){
            echo "<div>".htmlspecialchars($e->getMessage())."<br>".$sql."</div>";
        }
        return [];
    }
}


<?php
require 'SF_Tools.php';
include "config.php";

$SF = new SF_Tools($server_address, $server_port);
$SF->setAPIKey($api_key);

if(isset($_FILES['file'])){
    $SF->uploadSave($_FILES, $_POST['name']);
    if($SF->responce_code == 204){
        header("Location: SF.php");
    }else{
        echo "Error: ".$SF->responce_code;
        print_r($SF->responce);
    }
}
?>

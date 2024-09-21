<?php
require 'config.php';
include 'SF_Tools.php';
$sf_tools = new SF_Tools($server_address, $server_port, true);
$sf_tools->setAPIkey($api_key);

if($_POST['submit'] == "download"){
    $sf_tools->downloadSave($_POST['savename']);
}elseif($_POST['submit'] == "delete"){
    $sf_tools->deleteSave($_POST['savename']);
    if($sf_tools->responce_code == 204){
        header("Location: " . $_SERVER['HTTP_REFERER']);
    }
}else{
    echo "No POST data";
}
?>

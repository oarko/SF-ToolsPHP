<?php
if($_POST){
    require 'config.php';
    include 'SF_Tools.php';
    $sf_tools = new SF_Tools($server_address, $server_port, true);
    $sf_tools->setAPIkey("Bearer ". $api_key);
    $sf_tools->downloadSave($_POST['savename']);    
}else{
    echo "No POST data";
}
?>

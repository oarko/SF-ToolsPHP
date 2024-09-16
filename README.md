# SF-Tools
## SF_Tools.php
A PHP Class for returning the status of a Satifactory Dedicated Server

## How to run
Include SF_Tools.php in your PHP file and create an instance of SF_Tools(), then request the server status with `Get_server_status(stirng $server_address = "localhost", int $port = "7777").

```php
<?php
include 'SF_Tools.php';

// Example usage
$sfTools = new SF_Tools();
$sfTools->Get_server_status("192.168.5.20", 25525);
?>
<h1>Server Name: <?= $sfTools->name ?></h1>
<h2>Currently: <?= $sfTools->status ?></h2>
<h3>Running Version:<?= $sfTools->version ?></h3>
Modded: <input <?=($sfTools->modded)?"checked":""?> type="checkbox" id="modded" name="modded" value="modded">

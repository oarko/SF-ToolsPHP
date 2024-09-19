
# SF_Tools

`SF_Tools` is a PHP class designed to interact with Satisfactory dedicated servers, providing functionalities to retrieve and set server options, as well as advanced game settings.

## Requirements

- PHP 7.0 or higher

## Installation

Clone the repository to your local machine:

```sh
git clone https://github.com/oarko/SF_Tools.git
```

Include the [`SF_Tools.php`](command:_github.copilot.openRelativePath?%5B%7B%22scheme%22%3A%22file%22%2C%22authority%22%3A%22%22%2C%22path%22%3A%22%2Fmnt%2Fhtml%2FSF_Tools.php%22%2C%22query%22%3A%22%22%2C%22fragment%22%3A%22%22%7D%2C%2229d2738a-609e-4d82-a8bd-b6595b77ed40%22%5D "/mnt/html/SF_Tools.php") file in your project:

```php
require_once 'path/to/SF_Tools.php';
```

## Usage

### Initialization

Create an instance of the [`SF_Tools`](command:_github.copilot.openSymbolFromReferences?%5B%22%22%2C%5B%7B%22uri%22%3A%7B%22scheme%22%3A%22file%22%2C%22authority%22%3A%22%22%2C%22path%22%3A%22%2Fmnt%2Fhtml%2FSF_Tools.php%22%2C%22query%22%3A%22%22%2C%22fragment%22%3A%22%22%7D%2C%22pos%22%3A%7B%22line%22%3A432%2C%22character%22%3A15%7D%7D%5D%2C%2229d2738a-609e-4d82-a8bd-b6595b77ed40%22%5D "Go to definition") class by providing the server IP, port, and a boolean indicating whether to use a secure connection:
{server address} should be eiter an IP address, or just the base url for your server. expample: sf.mysite.com
```php
$sfTools = new SF_Tools("{server address}", {port default 7777}, {disable SSL varification default= true);
```

### Server connection functions

#### set_SSL_Varify()
enables SSL Varification. 
NOTE: enableing SSL varification without a proper Certificate Athority provided certificate for the server will cause a server varification error and the script will not function.
DEFAULT:FALSE
```php
$sfTools->set_SSL_varify(true)
```

#### chageServer()
change the server you are connecting to
```php
$sfTools->changeServer("{server address}", {port default:7777})
```



### Login Options

#### setAPIkey()
Set the API key for authentication:
Application tokens do not expire, and are granted by issuing the command `server.GenerateAPIToken` in the Dedicated Server console. The generated token can then be passed to the Authentication header with Bearer type to perform any Server API requests on the behalf of the server.

```php
$sfTools->setAPIkey("Bearer {your_api_key_here}");
```

#### passwordlessLogin()
login to the server as a client and sets the API Key to $sfTools->api_key

```php
$sfTools->passwordlesLogin();
```

#### passwordLogin()
login to the server as a client or administrator and sets the API Key to $sfTools->api_key
privilage levels should be either  `Client` or  `Administrator`

```php
$sfTools->passwordLogin({password}, {PrivilageLevel});
```

#### clameServer()
used to clame a server for the first time. Will generate and error if used on a clamed server

```php
$sfTools->clameServer({serverName}, {adminPassword});
```

#### setClientPassword()
sets the server client password

```php
$stTools->setClientPassword({password});
```

#### setAdminPassword()
sets the password for Administrator access

```php
$sfTools->setAdminPassword({password});
```


### Server Functions

#### renameServer()
renames the server
```php
$sfTools->renameServer("{serverName}");
```

#### setAutoloadSession()
sets the session that will load on next server boot
```php
$sfTools->setAutoloadSession("{SessionName}");
```

#### runConsoleCommand()
runs a command on the server console
```php
$sfTools->runConsoleCommand("{command}");
```

#### shutdown()
stops the server. IF the server is running as a service, the server will restart.
```php
$sfTools->shutdown();
```

#### getServerState()
Gets information about the server and sets it to the $[instance]->game_state array. this array includes:

    * [activeSessionName] => name of the current session
    * [numConnectedPlayers] => current number of connected players
    * [playerLimit] => max number of players
    * [techTier] => Maximum Tech Tier of all Schematics currently unlocked
    * [activeSchematic] => Schematic currently set as Active Milestone
    * [gamePhase] => string that indicates the current phase of the game
    * [isGameRunning] => true of false if game is currently running
    * [totalGameDuration] => time in seconds
    * [isGamePaused] =>  true or false to indicate weather the game is currently paused
    * [averageTickRate] => Average tick rate of the server, in ticks per second
    * [autoLoadSessionName] => name of the session that will be loaded when the server starts
```php
$sfTools->getServerState();
print_r($sfTools->game_state);
```

#### getServerOptions()
Retrieve the server options and sets the $[instance]->server_optioins array. this array includes:

     * [FG.DSAutoPause] => [true or false to indicate weather the game is set to pause when players are not connected]
     * [FG.DSAutoSaveOnDisconnect] => [True or false to indicate weather the game is set to save when players disconnect]
     * [FG.AutosaveInterval] => [time in seconds between autosaves]
     * [FG.ServerRestartTimeSlot] => [time in minutes between server restarts]
     * [FG.SendGameplayData] => [send usage data to the developers]
     * [FG.NetworkQuality] => [Network quality setting 0=> Low 1=> normal 2=> high 3=> ultra]

```php
$sfTools->getServerOptions();
print_r($sfTools-server_options);
```

#### setServerOptions()

Set the server options set in the $[instance]->server_options array

```php
$sfTools->setServerOptions();
```

#### getAdvancedGameSettings()

Retrieve the advanced game settings and sets the $[instance]->advanced_game_settings array. this array includes:

     * [FG.GameRules.NoPower] => False
     * [FG.GameRules.DisableArachnidCreatures] => False
     * [FG.GameRules.NoUnlockCost] => False
     * [FG.GameRules.SetGamePhase] => 1
     * [FG.GameRules.GiveAllTiers] => False
     * [FG.GameRules.UnlockAllResearchSchematics] => False
     * [FG.GameRules.UnlockInstantAltRecipes] => False
     * [FG.GameRules.UnlockAllResourceSinkSchematics] => False
     * [FG.GameRules.GiveItems] => Empty
     * [FG.PlayerRules.NoBuildCost] => False
     * [FG.PlayerRules.GodMode] => False
     * [FG.PlayerRules.FlightMode] => False

```php
$sfTools->getAdvancedGameSettings();
print_r($sfTools->advanced_game_settings);
```

#### setAdvancedGameSettings()
Set the Advanced Game Settings set in the $[instance]->advanced_game_settings array

```php
$sfTools->setAdvancedGameSettings();
```

### Save Functions

#### GetSessions
Gets all session on the server and save file information assoseated with them. sets the $[instance]->sessions array containing:

     [{sessionIndex}] =>(
                  [sessionName] => name of session
                  [saveHeaders] => (    array of saves
                                   [{saveIndex}] =>(
                                                [saveVersion] => 
                                                [buildVersion] =>
                                                [saveName] => 
                                                [saveLocationInfo] =>
                                                [mapName] =>
                                                [mapOptions] => 
                                                [sessionName] => 
                                                [playDurationSeconds] => 
                                                [saveDateTime] => 
                                                [isModdedSave] => 
                                                [isEditedSave] => 
                                                [isCreativeModeEnabled] => 

```php
$sfTools->getSessions();
print_r($sfTools->sessions);
```

#### downloadSave()
download a save file
NOTE: this will set the header for the document so it MUST be the only output call on the page.
```php
<?php
include 'SF_Tools.php';
$sfTools = new SF_Tools("localhost");
$sfTools->setAPIkey($myKey);
$sfTools->downloadSave("myserver save file");
?>
```


#### uplaodSave
***This function is NOT working at this time.***
Upload a save file to the server
```php
<?php
if(isset($_POST["submit"])) {
  include 'SF_Tools.php';
  $sfTools = new SF_Tools("localhost");
  $sfTools->setAPIkey($myKey);
  $file = $_FILES["file"]['tmp_name'];
  $sfTools->upload_save($file, "1.0 is newwww test", $_FILES['file']['size']);
  print_r($_FILES);
}else{
?>
<!DOCTYPE html>
<html>
<body>

<form action="upload.php" method="post" enctype="multipart/form-data">
  Select image to upload:
  <input type="file" name="file" id="file">
  <input type="submit" value="Upload file" name="submit">
</form>

</body>
</html>
<?php } ?>
```


### Error Handling

### error messages:
Check if there was an error during the last operation and print the error message

```php
$sfTools->error ? print_r($sfTools->errormsg) : "";
```

### status code
get the status code for the last request
```php
if($sfTools->responce_code == 200){
  echo "sucsess"
}
```

### Example

Here is a complete example of how to use the [`SF_Tools`](command:_github.copilot.openSymbolFromReferences?%5B%22%22%2C%5B%7B%22uri%22%3A%7B%22scheme%22%3A%22file%22%2C%22authority%22%3A%22%22%2C%22path%22%3A%22%2Fmnt%2Fhtml%2FSF_Tools.php%22%2C%22query%22%3A%22%22%2C%22fragment%22%3A%22%22%7D%2C%22pos%22%3A%7B%22line%22%3A432%2C%22character%22%3A15%7D%7D%5D%2C%2229d2738a-609e-4d82-a8bd-b6595b77ed40%22%5D "Go to definition") class:

```php
require_once 'SF_Tools.php';

echo "<pre>";

$sfTools = new SF_Tools("10.12.1.10", 7777, true);
if(!$sfTools->error){
  $sfTools->set_api_key("Bearer {your_api_key_here}");
  $sfTools->get_server_options();
  $sfTools->set_server_options();
  $sfTools->get_advanced_game_settings();
}

print_r($sfTools->advanced_game_settings);
?>
```

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Contributing

1. Fork the repository.
2. Create your feature branch (`git checkout -b feature/fooBar`).
3. Commit your changes (`git commit -am 'Add some fooBar'`).
4. Push to the branch (`git push origin feature/fooBar`).
5. Create a new Pull Request.

## Authors

- oarko - *Initial work* - [dopeghoti](https://github.com/dopeghoti)

## Acknowledgments

- Hat tip to anyone whose code was used
- Inspiration
- etc
```

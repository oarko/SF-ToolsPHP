<?php


class SF_Tools {
    const CURRENT_REST_API_VERSION = 1;
    const PROTOCOL_VERSION = 1;

    public $server = "localhost";
    public $port = 7777;


    public $latency = null;
    public $server_state = null;
    public $name = null;
    public $status = null;
    public $modded = null;
    public $version = null;
    public $api_token = null;
    private $valid_api = false;

    public $game_state = null;
    public $server_options = null;
    public $advanced_game_settings = null;
    public $sessions = null;
    public $PrivilageLevel = 0;

    private $server_link;
    private $secure = false;
    private $url = null;
    private $curl_options = null;
   
    
    public $error = false;
    public $errormsg = null;
    

    public $responce = null;
    public $responce_code = null;
    public $responce_message = null;
    private $rawResponse = null;

    private $responce_list = [
        0 => 'No Responce',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        204 => 'No Content',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        409 => 'Conflict',
        415 => 'Unsupported Media Type',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        503 => 'Service Unavailable'
    ];

    private $PrivilageLevels = [
        0 => 'NotAuthenticated',
        1 => 'Client',
        2 => 'Administator',
        3 => 'InitialAdmin',
        4 => 'APIToken'
    ];

    private $error_responce = [
        'error' => 'Server is offline',
        'status' => 'offline'
    ];

    private $messageTypes = [
        'PollServerState' => 0,
        'ServerStateResponse' => 1
    ];

    private $serverStates = [
        0 => 'Offline',
        1 => 'Idle',
        2 => 'Preparing world',
        3 => 'Live'
    ];


    function __construct($server = "localhost", $port = 7777, $secure = false) {
        $this->secure = false;
        $this->changeServer($server, $port);
        $this->server_link = curl_init();
        $this->Get_LW_server_status();
    }
    
    
    public function __destruct() {
        curl_close($this->server_link);
    }


    /**************************************************************************************************************************************
     * Server Connect Functions
     * 
     * 
     * set_SSL_varify() => set curl to varify the server ssl certificate
     * change_server() => change the server and port
     * set_curl_options() => sets the curl options for the server connection (internal function)
     * validate_url() => validates the url (internal function)
     * fetch_from_api() => fetch data from the api (internal function)
     * 
     **************************************************************************************************************************************/

    

    // set curl to varify the server ssl certificate
    public function set_SSL_varify($secure = false){
        $this->secure = $secure;
        $this->set_curl_options();
    }

    // change the server and port
    public function changeServer($server, $port = 7777){
        if($this->validate_url("https://$server:$port/api/v". self::CURRENT_REST_API_VERSION)){
            $this->url = "https://$server:$port/api/v" . self::CURRENT_REST_API_VERSION;
            $this->server = $server;
            $this->port = $port;
            $this->set_curl_options();
            return true;
        }else{
            $this->error = true;
            $this->errormsg = "Invalid URL: https://$server:$port/api/v" . self::CURRENT_REST_API_VERSION ." please check the server and port";
            return false;
        }
    }

    private function set_curl_options(){
        $this->curl_options = [
            CURLOPT_URL => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_VERBOSE => true,
            CURLOPT_POST => true
        ];
        if(!$this->secure){
            $this->curl_options[CURLOPT_SSL_VERIFYPEER] = false;
            $this->curl_options[CURLOPT_SSL_VERIFYHOST] = false;
        }
    }
        

    // validate the url (internal function)
    private function validate_url($url){
        if(!$url || !is_string($url) || ! preg_match('/^http(s)?:\/\/[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(\/.*)?$/i', $url)){
            return false;
        }else{
            return true;
        }
    }
    
    
    // fetch data from the api (internal function)
    private function fetch_from_api($data, $mustBeAdmin = false){
        $this->error = false;
        $this->errormsg = null;
        curl_reset($this->server_link);
        If($mustBeAdmin && $this->valid_api && $this->PrivilageLevel < 2){
            $this->error = true;
            $this->errormsg = "You must be an admin to perform this action";
            return false;
        }
        
        
        $this->curl_options[CURLOPT_HTTPHEADER] = ($this->valid_api)?
        ["Authorization: Bearer " . $this->api_token, "Content-Type: application/json"]
        :
        ["Content-Type: application/json"];
        $this->curl_options[CURLOPT_POSTFIELDS] =  $data;

        if(!curl_setopt_array($this->server_link, $this->curl_options)){
            $this->error = true;
            $this->errormsg = "Failed to set curl options";
            return false;
        }
        
        $this->responce = curl_exec($this->server_link);
        $this->responce_code = curl_getinfo($this->server_link, CURLINFO_HTTP_CODE);
        if (curl_errno($this->server_link)) {
            $this->error = true;
            $this->errormsg = curl_error($this->server_link);
            return false;
        } else {
            $this->responce_message = $this->responce_list[$this->responce_code];
            return true;
        }
    }


    /**************************************************************************************************************************************
     * Authentication Functions
     * 
     * set_api_key($api_key) => set the api key used for authentication
     * verify_api_key() => verify the api key (internal function) 
     * passwordlessLogin() => login with out a password as a client
     * passwordLogin($password) => login with a password as admin or client
     * clameServer($serverName, $password) => clame the server as admin (only works on unclamed servers)
     * setClientPassword($password) => set the client password
     * setAdminPassword($password) => set the admin password 
     * 
     * *************************************************************************************************************************************/


     // set the api key used for authentication
     public function setAPIkey($api_key){
        $this->api_token = $api_key;
        $this->valid_api = $this->verify_api_key();
        $this->PrivilageLevel = 4;
    }

    private function verify_api_key(){
        if(!$this->api_token){
            $this->error = true;
            $this->errormsg = "No API key set";
            return false;
        }else{
            $data = json_encode(["function" => "VerifyAuthenticationToken"]);
            $this->valid_api = true;
            $this->fetch_from_api($data);
            if($this->responce_code == 204){
                return true;
            }else{
                $this->error = true;
                $this->errormsg = "Invalid API key";
                return false;
            }
        }
    }

    public function clientLogin($password = null){
        if($password){
            $sucsess = $this->passwordLogin($password);
        }else{
            $sucsess = $this->passwordlessLogin();
        }
        ($sucsess)?$this->PrivilageLevel = 1:$this->PrivilageLevel = 0;
    }

    public function adminLogin($password){
        ($this->passwordLogin($password, 2))?$this->PrivilageLevel = 2:$this->PrivilageLevel = 0;
    }

    public function passwordlessLogin(){
        if(isset($this->api_token)){
            $this->api_token = null;
            $this->valid_api = false;
        }
        $data = json_encode([
            "function" => "PasswordlessLogin",
            "data" => [
                "minimumPrivilegeLevel" => 1
                ]
            ]);
        $this->fetch_from_api($data);
        if($this->responce_code == 200){
            $this->api_token = json_decode($this->responce, true)["data"]["authenticationToken"];
            $this->valid_api = $this->verify_api_key();
            return true;
        }else{
            $this->error = true;
            $this->errormsg = json_decode($this->responce, true)['errorMessage'];
            return false;
        }
    }

    public function passwordLogin($password, $privilage = 1){
        if(isset($this->api_token)){
            $this->api_token = null;
            $this->valid_api = false;
        }
        $data = json_encode([
            "function" => "PasswordlessLogin",
            "data" => [
                "minimumPrivilegeLevel" => $privilage,
                "Password" => $password
                ]
            ]);
            $this->fetch_from_api($data);
        if($this->responce_code == 200){
            $this->api_token = json_decode($this->responce, true)["data"]["authenticationToken"];
            $this->valid_api = $this->verify_api_key();
            return true;
        }else{
            $this->error = true;
            $this->errormsg = json_decode($this->responce, true)['errorMessage'];
            return false;
        }
    }

    public function claimServer($serverName, $adminPassword){
        if(isset($this->api_token)){
            $this->api_token = null;
            $this->valid_api = false;
        }
        $data = json_encode([
            "function" => "ClaimServer",
            "data" => [
                "ServerName" => $serverName,
                "AdminPassword" => $adminPassword
                ]
            ]);
        $this->fetch_from_api($data, true);
        if($this->responce_code == 200){
            $this->api_token = json_decode($this->responce, true)["data"]["authenticationToken"];
            $this->valid_api = true; //$this->verify_api_key();
            $this->PrivilageLevel = 3;
        }else{
            $this->error = true;
            $this->errormsg = json_decode($this->responce, true)['errorMessage'];
            return false;
        }
    }

    public function setClientPassword($password){
        $data = json_encode([
            "function" => "SetClientPassword",
            "data" => [
                "Password" => $password
                ]
            ]);
        $this->fetch_from_api($data, true);
        if($this->responce_code == 200){
            return true;
        }else{
            $this->error = true;
            $this->errormsg = json_decode($this->responce, true)['errorMessage'];
            return false;
        }
    }

    public function setAdminPassword($password){
        $data = json_encode([
            "function" => "SetAdminPassword",
            "data" => [
                "Password" => $password
                ]
            ]);
        $this->fetch_from_api($data, true);
        if($this->responce_code == 200){
            return true;
        }else{
            $this->error = true;
            $this->errormsg = json_decode($this->responce, true)['errorMessage'];
            return false;
        }
    }


    /**************************************************************************************************************************************
     * Server Status Functions
     * 
     * 
     * renameServer($serverName) => rename the server ro $serverName
     * setAutoloadSession($sessionName) => set the session that autoloads on server start
     * runConsoleCommand($command) => run a console command
     * shutdown() => shutdown the server. If the server is running as a service it will restart
     * getServerState() => gets information about the server. (see below for details)
     * getServerOptions() => gets the server options and sets the $SF-Tools->server_options. (see below for details)
     * setServerOptions() => sets the server options from the $SF-Tools->server_options array
     * getAdvancedGameSettings() => gets the advanced game settings and sets the $SF_Tools->advanced_game_settings array. (see below for details)
     * setAdvancedGameSettings() => sets the advanced game settings from the $SF-Tools->advanced_game_settings array
     * 
     **************************************************************************************************************************************/

    public function healthCheck($clientData){
        $data = json_encode([
            "function" => "HealthCheck",
            "data" => [
                "ClientCustomData" => $clientData
                ]
            ]);
        $this->fetch_from_api($data);
        if($this->responce_code == 200){
            return true;
        }else{
            $this->error = true;
            $this->errormsg = json_decode($this->responce, true)['errorMessage'];
            return false;
        }
    }

    public function CreateNewGame($sessionName, $MapName, $StartingLocation, $SkipOnboarding, $AdvancedGameSettings, $custom_options = null){
        $data = json_encode([
            "function" => "CreateNewGame",
            "data" => [
                "SessionName" => $sessionName,
                "MapName" => $MapName,
                "StartingLocation" => $StartingLocation,
                "SkipOnboarding" => $SkipOnboarding,
                "AdvancedGameSettings" => $AdvancedGameSettings,
                "CustomOptions" => $custom_options
                ]
            ]);
        $this->fetch_from_api($data, true);
        if($this->responce_code == 200){
            return true;
        }else{
            $this->error = true;
            $this->errormsg = json_decode($this->responce, true)['errorMessage'];
            return false;
        }
    }

    public function renameServer($serverName){
        $data = json_encode([
            "function" => "RenameServer",
            "data" => [
                "ServerName" => $serverName
                ]
            ]);
            $this->fetch_from_api($data, true);
        if($this->responce_code == 200){
            return true;
        }else{
            $this->error = true;
            $this->errormsg = json_decode($this->responce, true)['errorMessage'];
            return false;
        }
    }

    public function setAutoloadSession($sessionName){
        $data = json_encode([
            "function" => "SetAutoLoadSession",
            "data" => [
                "SessionName" => $sessionName
                ]
            ]);
        $this->fetch_from_api($data, true);
        if($this->responce_code == 200){
            return true;
        }else{
            $this->error = true;
            $this->errormsg = json_decode($this->responce, true)['errorMessage'];
            return false;
        }
    }

    public function runConsoleCommand($command){
        $data = json_encode([
            "function" => "RunCommand",
            "data" => [
                "Command" => $command
                ]
            ]);
        $this->fetch_from_api($data, true);
        if($this->responce_code == 200){
            return true;
        }else{
            $this->error = true;
            $this->errormsg = json_decode($this->responce, true)['errorMessage'];
            return false;
        }
    }

    public function shutdown(){
        $data = json_encode([
            "function" => "ShutdownServer"
            ]);
        $this->fetch_from_api($data, true);
        if($this->responce_code == 200){
            return true;
        }else{
            $this->error = true;
            $this->errormsg = json_decode($this->responce, true)['errorMessage'];
            return false;
        }
    }


    

    /************************************************************************************************************************************** 
    * generates an array of the server state containing the following
    * 
    * [activeSessionName] => [name of the current session]
    * [numConnectedPlayers] => [current number of connected players]
    * [playerLimit] => [max number of players]
    * [techTier] => [Maximum Tech Tier of all Schematics currently unlocked]
    * [activeSchematic] => [Schematic currently set as Active Milestone]
    * [gamePhase] => [string that indicates the current phase of the game]
    * [isGameRunning] => [true of false if game is currently running]
    * [totalGameDuration] => [time in seconds]
    * [isGamePaused] =>  [true or false to indicate weather the game is currently paused]
    * [averageTickRate] => [Average tick rate of the server, in ticks per second]
    * [autoLoadSessionName] => [name of the session that will be loaded when the server starts]
    **************************************************************************************************************************************/

    public function getServerState(){
        $this->error = 0;
        if($this->server_state < 1){
            $this->error = true;
            $this->errormsg = "Server is offline";
            return -1;
        } 
        $data = json_encode(["function" => "QueryServerState"]);
        $this->fetch_from_api($data);
        if($this->responce_code == 200){
            $this->game_state = json_decode($this->responce, true)["data"]["serverGameState"];
        }elseif (!$this->errormsg){
            $this->error = true;
            $this->errormsg = json_decode($this->responce, true)['errorMessage'];
            return false;
        }else{
            $this->error = true;
            return false;
        }
    }


    /**************************************************************************************************************************************
     * Generats an array of the server optisons containing the following
     * 
     * [FG.DSAutoPause] => [true or false to indicate weather the game is set to pause when players are not connected]
     * [FG.DSAutoSaveOnDisconnect] => [True or false to indicate weather the game is set to save when players disconnect]
     * [FG.AutosaveInterval] => [time in seconds between autosaves]
     * [FG.ServerRestartTimeSlot] => [time in minutes between server restarts]
     * [FG.SendGameplayData] => [send usage data to the developers]
     * [FG.NetworkQuality] => [Network quality setting 0=> Low 1=> normal 2=> high 3=> ultra]
     ***************************************************************************************************************************************/
    

    public function getServerOptions(){
        $this->error = 0;
        if($this->server_state < 1){
            $this->error = true;
            $this->errormsg = "Server is offline";
            return -1;
        } 
        $data = json_encode(["function" => "GetServerOptions"]);
        $this->fetch_from_api($data);
        if($this->responce_code == 200){
            $this->server_options = json_decode($this->responce, true)["data"]['serverOptions'];
        }else{
            $this->error = true;
            $this->errormsg = json_decode($this->responce, true)['errorMessage'];
            return false;
        }
    }

    public function setServerOptions(){
        $this->error = 0;
        $jsonarray = [
            'function' => 'ApplyServerOptions',
            'data'  => [
                "UpdatedServerOptions" => [
                    "FG.DSAutoPause" => $this->server_options["FG.DSAutoPause"],
                    "FG.DSAutoSaveOnDisconnect" => $this->server_options["FG.DSAutoSaveOnDisconnect"],
                    "FG.AutosaveInterval" => $this->server_options["FG.AutosaveInterval"],
                    "FG.ServerRestartTimeSlot" => $this->server_options["FG.ServerRestartTimeSlot"],
                    "FG.SendGameplayData" => $this->server_options["FG.SendGameplayData"],
                    "FG.NetworkQuality" => $this->server_options["FG.NetworkQuality"]
                ]
            ]
            
        ];
        $data = json_encode($jsonarray);
        $this->fetch_from_api($data);
        if($this->responce_code == 200){
            return true;
        }else{
            $this->error = true;
            $this->errormsg = json_decode($this->responce, true)['errorMessage'];
        }
    }


    /**************************************************************************************************************************************
     * Generats an array of the server optisons containing the following
     * 
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
     ***************************************************************************************************************************************/


    public function getAdvancedGameSettings(){
        $this->error = 0;
        if($this->server_state < 1){
            $this->error = true;
            $this->errormsg = "Server is offline";
            return -1;
        } 
        $data = json_encode(["function" => "GetAdvancedGameSettings"]);
        $this->fetch_from_api($data);
        if($this->responce_code == 200){
            $this->advanced_game_settings = json_decode($this->responce, true)["data"]['advancedGameSettings'];
        }else{
            $this->error = true;
            $this->errormsg = json_decode($this->responce, true)['errorMessage'];
            return false;
        }
    }

    public function setAdvancedGameSettings(){
        $this->error = 0;
        if($this->server_state < 1){
            $this->error = true;
            $this->errormsg = "Server is offline";
            return -1;
        } 
        $jsonarray = [
            'function' => 'ApplyAdvancedGameSettings',
            'data'  => [
                "UpdatedAdvancedGameSettings" => [
                    "FG.GameRules.NoPower" => $this->advanced_game_settings["FG.GameRules.NoPower"],
                    "FG.GameRules.DisableArachnidCreatures" => $this->advanced_game_settings["FG.GameRules.DisableArachnidCreatures"],
                    "FG.GameRules.NoUnlockCost" => $this->advanced_game_settings["FG.GameRules.NoUnlockCost"],
                    "FG.GameRules.SetGamePhase" => $this->advanced_game_settings["FG.GameRules.SetGamePhase"],
                    "FG.GameRules.GiveAllTiers" => $this->advanced_game_settings["FG.GameRules.GiveAllTiers"],
                    "FG.GameRules.UnlockAllResearchSchematics" => $this->advanced_game_settings["FG.GameRules.UnlockAllResearchSchematics"],
                    "FG.GameRules.UnlockInstantAltRecipes" => $this->advanced_game_settings["FG.GameRules.UnlockInstantAltRecipes"],
                    "FG.GameRules.UnlockAllResourceSinkSchematics" => $this->advanced_game_settings["FG.GameRules.UnlockAllResourceSinkSchematics"],
                    "FG.GameRules.GiveItems" => $this->advanced_game_settings["FG.GameRules.GiveItems"],
                    "FG.PlayerRules.NoBuildCost" => $this->advanced_game_settings["FG.PlayerRules.NoBuildCost"],
                    "FG.PlayerRules.GodMode" => $this->advanced_game_settings["FG.PlayerRules.GodMode"],
                    "FG.PlayerRules.FlightMode" => $this->advanced_game_settings["FG.PlayerRules.FlightMode"]
                ]
            ]
            
        ];
        $data = json_encode($jsonarray);
        $this->fetch_from_api($data);
        if($this->responce_code == 200){
            return true;
        }else{
            $this->error = true;
            $this->errormsg = json_decode($this->responce, true)['errorMessage'];
            return false;
        }
    }



    /**************************************************************************************************************************************
     * Session Functions
     * 
     * getSessions() => get a list of all the sessions on the server
     * downloadSave($save_name) => download the save file $save_name
     * uploadSave($save_file, $save_name, $filesize, $LoadSaveGame = false, $EnableAdvancedGameSettings = false) => upload the save file $save_file with the name $save_name
     * deleteSave($save_name) => delete the save file $save_name
     * 
     **************************************************************************************************************************************/



    public function getSessions(){
        $this->error = 0;
        if($this->server_state < 1){
            $this->error = true;
            $this->errormsg = "Server is offline";
            return -1;
        } 
        $data = json_encode(["function" => "EnumerateSessions"]);
        $this->fetch_from_api($data, true);
        if($this->responce_code == 200){
            $this->sessions = json_decode($this->responce, true)['data']['sessions'];
            return true;
        }else{
            $this->error = true;
            $this->errormsg = json_decode($this->responce, true)['errorMessage'];
            return false;
        }
    }

    public function downloadSave($save_name){
        $this->error = 0;
        if($this->server_state < 1){
            $this->error = true;
            $this->errormsg = "Server is offline";
            return -1;
        } 
        $jsonarray = [
            'function' => 'DownloadSaveGame',
            'data'  => [
                "SaveName" => $save_name
            ]
            
        ];
        $data = json_encode($jsonarray);
        $this->fetch_from_api($data, true);
        if($this->fetch_from_api($data)){
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $save_name . '.sav"');
            return true;
        }else{
            $this->error = true;
            $this->errormsg = json_decode($this->responce, true)['errorMessage'];
            return false;
        }
    }

    public function deleteSession($sessionName){
        $this->error = 0;
        if($this->server_state < 1){
            $this->error = true;
            $this->errormsg = "Server is offline";
            return -1;
        } 
        $jsonarray = [
            'function' => 'DeleteSession',
            'data'  => [
                "SessionName" => $sessionName
            ]
            
        ];
        $data = json_encode($jsonarray);
        $this->fetch_from_api($data, true);
        if($this->responce_code == 204){
            return true;
        }else{
            $this->error = true;
            $this->errormsg = json_decode($this->responce, true)['errorMessage'];
            return false;
        }
    }

    public function uploadSave($save_file, $save_name, $LoadSaveGame = false, $EnableAdvancedGameSettings = false){
        $this->error = 0;
        if($this->server_state < 1){
            $this->error = true;
            $this->errormsg = "Server is offline";
            return -1;
        }else if(!ini_get('file_uploads')){
            $this->error = true;
            $this->errormsg = "File uploads are disabled";
            return -1;
        }

        curl_reset($this->server_link);

        $jsonarray = [
            'function' => 'UploadSaveGame',
            'data'  => [
                "SaveName" => $save_name,
                "LoadSaveGame" => $LoadSaveGame,
                "EnableAdvancedGameSettings" => $EnableAdvancedGameSettings,
            ]
        ];

        if (function_exists('curl_file_create')) { // php 5.5+
            $post['saveGameFile'] = curl_file_create($save_file['file']['tmp_name'], "application/octet-stream", $save_file['file']['name']);
        } else { 
            $post['saveGameFile'] = '@' . realpath($save_file['file']['tmp_name']);
        }
        $post['json'] = new CURLFile('data://text/plain,' . json_encode($jsonarray), 'application/json', 'data.json');
        $this->curl_options[CURLOPT_HTTPHEADER] = [
            "Content-Type: multipart/form-data",
            "Authorization: Bearer " . $this->api_token
        ];
        $this->curl_options[CURLOPT_POSTFIELDS] = $post;
        curl_setopt_array($this->server_link, $this->curl_options);
        $this->responce = curl_exec($this->server_link);
        if( curl_getinfo($this->server_link, CURLINFO_HTTP_CODE) == 204 ){
            return true;
        }else{
            $this->error = true;
            $this->errormsg = json_decode($this->responce, true);
            return false;
        }
    }

    public function deleteSave($save_name){
        $this->error = 0;
        if($this->server_state < 1){
            $this->error = true;
            $this->errormsg = "Server is offline";
            return -1;
        } 
        $jsonarray = [
            'function' => 'DeleteSaveFile',
            'data'  => [
                "SaveName" => $save_name
            ]
            
        ];
        $data = json_encode($jsonarray);
        $this->fetch_from_api($data, true);
        if($this->responce_code == 204){
            return true;
        }else{
            $this->error = true;
            $this->errormsg = json_decode($this->responce, true);
            return false;
        }
    }

    





    //Not used at this time
    private $serverSubStates = [
        0 => 'ServerGameState',
        1 => 'ServerOptions',
        2 => 'AdvancedGameSettings',
        3 => 'SaveCollection',
        4 => 'Custom1',
        5 => 'Custom2',
        6 => 'Custom3',
        7 => 'Custom4'
    ];

    //Not used at this time
    private $serverFlags = [
        0 => 'Modded',
        1 => 'Custom1',
        2 => 'Custom2',
        3 => 'Custom3',
        4 => 'Custom4'
    ];


    private function Get_LW_server_status() {
        $this->responce = $this->Get_LW_status($this->server, $this->port);
        if(isset($this->responce['ServerStateRaw'])){
            $this->server_state = $this->responce['ServerStateRaw'];     
        }   
    }

    private function Get_LW_status($address, $port) {
        $msgID = hex2bin('D5F6'); // Protocol Magic identifying the UDP Protocol
        $msgType = chr($this->messageTypes['PollServerState']); // Identifier for 'Poll Server State' message
        $msgProtocol = chr(self::PROTOCOL_VERSION); // Identifier for protocol version identification
        $msgData = pack('P', microtime(true)); // "Cookie" payload for server state query. Can be anything.
        $msgEnds = chr(1); // End of Message marker

        $srvAddress = $address;
        $srvPort = (int) $port;
        $bufferSize = 1024;
        $msgToServer = $msgID . $msgType . $msgProtocol . $msgData . $msgEnds;
        $msgFromServer = null;

        $timeSent = microtime(true);
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_sendto($socket, $msgToServer, strlen($msgToServer), 0, $srvAddress, $srvPort);
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 2, 'usec' => 0));

        try {
            $msgFromServer = @socket_recvfrom($socket, $buffer, $bufferSize, 0, $srvAddress, $srvPort);
            if ($msgFromServer === false) {
                throw new Exception('Connection timed out.');
            }
            $timeRecv = microtime(true);
            $this->rawResponse = $buffer;
            $this->latency = $timeRecv - $timeSent;
            return $this->parse_LW_Response($buffer);
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
            exit(1);
        } finally {
            socket_close($socket);
        }
    }

    private function parse_LW_Response($data) {
        if (empty($data)) {
            throw new Exception('parseLightAPIResponse() called with empty response.');
        }

        // Validate the envelope
        $validFingerprint = pack('H*', 'd5f6') . chr($this->messageTypes['ServerStateResponse']) . chr(self::PROTOCOL_VERSION);
        $packetFingerprint = substr($data, 0, 4);
        if ($packetFingerprint !== $validFingerprint) {
            throw new Exception('Unknown packet type received.');
        }

        $packetTerminator = ord(substr($data, -1));
        if ($packetTerminator !== 1) {
            throw new Exception('Unknown packet terminator.');
        }

        $payload = substr($data, 4, -1); // strip the envelope from the datagram
        $response = [];
        $response['Cookie'] = unpack('P', substr($payload, 0, 8))[1];
        $response['ServerStateRaw'] = ord(substr($payload, 8, 1));
        $response['ServerStateMsg'] = $this->serverStates[$response['ServerStateRaw']];
        $this->status = $response['ServerStateMsg'];
        $response['ServerNetCL'] = unpack('V', substr($payload, 9, 4))[1];
        $this->version = $response['ServerNetCL'];
        $response['ServerFlags'] = str_split(sprintf('%064b', unpack('P', substr($payload, 13, 8))[1]));
        $responce['modded'] = $response['ServerFlags'][0];
        $this->modded = $response['ServerFlags'][0];
        $response['NumSubStates'] = ord(substr($payload, 21, 1));
        $response['SubStates'] = [];

        $offsetCursor = 22;
        for ($i = 0; $i < $response['NumSubStates']; $i++) {
            $subState = [];
            $subState['SubStateId'] = ord(substr($payload, $offsetCursor, 1));
            $offsetCursor += 1;
            $subState['SubStateVersion'] = unpack('v', substr($payload, $offsetCursor, 2))[1];
            $offsetCursor += 2;
            $response['SubStates'][] = $subState;
        }

        $serverNameLengthOffset = $offsetCursor;
        $serverNameOffset = $serverNameLengthOffset + 2;
        $response['ServerNameLength'] = unpack('v', substr($payload, $serverNameLengthOffset, 2))[1];
        $rawName = substr($payload, $serverNameOffset, $response['ServerNameLength']);
        $response['ServerName'] = $rawName;
        $this->name = $rawName;

        return $response;
    }
}

?>

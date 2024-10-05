<?php


class SF_Tools {
    const CURRENT_REST_API_VERSION = 1;
    const PROTOCOL_VERSION = 1;
    const BUFFER_SIZE = 1024;
    const COOKIE = 0;
    const SERVER_STATE = 8;
    const SERVER_NET_CL = 9;
    const SERVER_FLAGS = 13;
    const NUM_SUB_STATES = 21;
    const SUB_STATES = 22;
    const SERVER_NAME_LENGTH = 22;
    const SERVER_NAME = 23;

    public $server = "localhost";
    public $port = 7777;

    public $checkstatus = "file";

    public $db_host = "localhost";
    public $db_user = "";
    public $db_pass = "";
    public $db_name = "";

    public $fileName = "status.json";

    public $latency = null;
    public $serverStateCode = 0;
    public $serverState = "offline";
    public $serverName = null;
    public $status = null;
    public $modded = null;
    public $version = null;
    public $api_token = null;
    public $subStates = null;
    public $game_state = null;
    public $server_options = null;
    public $advanced_game_settings = null;
    public $sessions = null;
    public $PrivilageLevel = 0;

    private $server_link;
    private $secure = false;
    private $url = null;
    private $curl_options = null;
    private $cookie = null;
    private $valid_api = false;
    
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

    public $mapNames = [
        "Persistent_Level",
    ];


    public $startLocations = [
        "Grass Fields",
        "DuneDesert",
        "Rocky Desert",
        "Northern Forest",        
    ];

    public $PrivilageLevels = [
        0 => 'NotAuthenticated',
        1 => 'Client',
        2 => 'Administator',
        3 => 'InitialAdmin',
        4 => 'APIToken'
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

    private $substateIDs = [
        0 => 'ServerGameState',
        1 => 'ServerOptions',
        2 => 'AdvancedGameSettings',
        3 => 'SaveCollection',
        4 => 'Custom1',
        5 => 'Custom2',
        6 => 'Custom3',
        7 => 'Custom4'
    ];


    function __construct($server = "localhost", $port = 7777, $secure = false) {
        $this->secure = false;
        $this->changeServer($server, $port);
        $this->server_link = curl_init();
        $this->pollServerStatus();
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
        if(!$this->getState() == 3){
            throw new Exception("Server is Ready");
        }
        try{
            if(!isset($this->server_link)){
                throw new Exception("cURL failed to initialize");
            }
            curl_reset($this->server_link);
            If($mustBeAdmin && $this->valid_api && $this->PrivilageLevel < 2){
                throw new Exception("You must be an admin to perform this action");
            }
                        
            $this->curl_options[CURLOPT_HTTPHEADER] = ($this->valid_api)?
            ["Authorization: Bearer " . $this->api_token, "Content-Type: application/json"]
            :
            ["Content-Type: application/json"];
            $this->curl_options[CURLOPT_POSTFIELDS] =  $data;

            if(!curl_setopt_array($this->server_link, $this->curl_options)){
                throw new Exception("cURL failed to set options");  
            }
            
            $this->responce = curl_exec($this->server_link);
            $this->responce_code = curl_getinfo($this->server_link, CURLINFO_HTTP_CODE);
            if (curl_errno($this->server_link)) {
                throw new Exception(curl_error($this->server_link));
            } else {
                $this->responce_message = $this->responce_list[$this->responce_code];
                return true;
            }
        }catch(Exception $e){
            $this->error = true;
            $this->errormsg = $e->getMessage();
            return false;
        }
    }

    public function getState(){
        $this->pollServerStatus();
        return $this->serverState;
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
        try{
            if(!$this->api_token){
               throw new Exception("API key not set");
            }else{
                $data = json_encode(["function" => "VerifyAuthenticationToken"]);
                $this->valid_api = true;
                $this->fetch_from_api($data);
                if($this->responce_code == 204){
                    return true;
                }else{
                    throw new Exception("API key is invalid");
                }
            }
        }catch(Exception $e){
            $this->error = true;
            $this->errormsg = $e->getMessage();
            return false;
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
        ($this->passwordLogin($password, "Administrator"))?$this->PrivilageLevel = 2:$this->PrivilageLevel = 0;
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
            $this->errormsg = $this->errorDecode(__FUNCTION__);
            return false;
        }
    }

    public function passwordLogin($password, $privilage = 1){
        if(!isset($password)){
            $this->error = true;
            $this->errormsg = "Password not set";
            return false;
        }
        if(isset($this->api_token)){
            $this->api_token = null;
            $this->valid_api = false;
        }
        $data = json_encode([
            "function" => "PasswordLogin",
            "data" => [
                "minimumPrivilegeLevel" => $privilage,
                "Password" => $password
                ]
            ]);
            $this->fetch_from_api($data);           
        if(!$this->error){
            $this->api_token = json_decode($this->responce, true)["data"]["authenticationToken"];
            if($this->valid_api = $this->verify_api_key())
                return true;
        }else{
            $this->error = true;
            $this->errormsg = $this->errorDecode(__FUNCTION__);
            return false;
        }
        return false;
    }

    public function clameServer($serverName, $adminPassword){
        try{
            if(!isset($serverName)){
                throw new Exception("Server name not set");
            }
            if(!isset($adminPassword)){
                throw new Exception("Admin password not set");
            }
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
                throw new Exception($this->errorDecode(__FUNCTION__));
            }
        }catch(Exception $e){
            $this->error = true;
            $this->errormsg = $e->getMessage();
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
        if($this->responce_code == 204){
            return true;
        }else{
            $this->error = true;
            $this->errormsg = $this->errorDecode(__FUNCTION__);
            return false;
        }
    }

    public function setAdminPassword($password){
        if(!isset($password)){
            $this->error = true;
            $this->errormsg = "Password not set";
            return false;
        }
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
            $this->errormsg = $this->errorDecode(__FUNCTION__);
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
        if($this->responce_code == 204){
            return true;
        }else{
            $this->error = true;
            $this->errormsg = $this->errorDecode(__FUNCTION__);
            return false;
        }
    }

    public function CreateNewGame($sessionName, $MapName, $StartingLocation, $SkipOnboarding, $AdvancedGameSettings = false, $custom_options = false){
        try{
            if(strlen($sessionName) < 3){
                throw new Exception("Session Name needs to be at least 3 characters");
            }
            if(!in_array($MapName, $this->mapNames)){
                throw new Exception("Invalid Map Name");
            }
            if(!in_array($StartingLocation, $this->startLocations)){
                throw new Exception("Invalid Starting Location");
            }
            if(!is_bool($SkipOnboarding)){
                throw new Exception("Skip Onboarding must be a boolean");
            }
            $data = [
                "function" => "CreateNewGame",
                "data" => [
                    "NewGameData" => [
                        "SessionName" => $sessionName,
                        "MapName" => $MapName,
                        "StartingLocation" => $StartingLocation,
                        "bSkipOnboarding" => $SkipOnboarding
                        ]
                    ]
                ];

            if(is_array($AdvancedGameSettings)){
                $data["data"]["AdvancedGameSettings"] = $AdvancedGameSettings;
            }elseif(is_bool($AdvancedGameSettings) && $AdvancedGameSettings){
                $data["data"]["AdvancedGameSettings"] = $this->advanced_game_settings;
            }else{
                $data['data']['AdvancedGameSettings'] = null;
            }
            if($custom_options){
                $data["data"]["CustomOptions"] = $custom_options;
            }else{
                $data["data"]["CustomOptions"] = null;
            }   
            $this->fetch_from_api(json_encode($data), true);
            if($this->responce_code == 202){
                return true;
            }else{
                throw new Exception($this->responce);
                //throw new Exception($this->errorDecode(__FUNCTION__));
            }
        }
        catch(Exception $e){
            $this->error = true;
            $this->errormsg = $e->getMessage();
            return false;
        }
    }

    public function renameServer($serverName){
        if(!isset($serverName)){
            $this->error = true;
            $this->errormsg = "Server Name not set";
            return false;
        }
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
            $this->errormsg = $this->errorDecode(__FUNCTION__);
            return false;
        }
    }

    public function setAutoloadSession($sessionName){
        if(!isset($sessionName)){
            $this->error = true;
            $this->errormsg = "Session Name not set";
            return false;
        }
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
            $this->errormsg = $this->errorDecode(__FUNCTION__);
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
            $this->errormsg = $this->errorDecode(__FUNCTION__);
            return false;
        }
    }

    public function shutdown(){
        $data = json_encode([
            "function" => "Shutdown"
            ]);
        $this->fetch_from_api($data, true);
        if($this->responce_code == 200){
            return true;
        }else{
            $this->error = true;
            $this->errormsg = $this->responce;
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
        if($this->serverState < 1){
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
            $this->errormsg = $this->errorDecode(__FUNCTION__);
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
        if($this->serverState < 1){
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
            $this->errormsg = $this->errorDecode(__FUNCTION__);
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
            $this->errormsg = $this->errorDecode(__FUNCTION__);
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
        if($this->serverState < 1){
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
            $this->errormsg = $this->errorDecode(__FUNCTION__);
            return false;
        }
    }

    public function setAdvancedGameSettings(){
        $this->error = 0;
        if($this->serverState < 1){
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
            $this->errormsg = $this->errorDecode(__FUNCTION__);
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
        try{
            if($this->serverState < 1){
                throw new Exception("Server is offline");
            } 
            $data = json_encode(["function" => "EnumerateSessions"]);
            $this->fetch_from_api($data, true);
            if($this->responce_code == 200){
                $this->sessions = json_decode($this->responce, true)['data']['sessions'];
                return true;
            }else{
                throw new Exception($this->errorDecode(__FUNCTION__));
            }
        }catch(Exception $e){
            $this->error = true;
            $this->errormsg = $e->getMessage();
            return false;
        }
    }

    public function downloadSave($save_name){
        $this->error = 0;
        try{
            if($this->serverState < 1){
                throw new Exception("Server is offline");
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
                throw new Exception($this->errorDecode(__FUNCTION__));
            }
        }catch(Exception $e){
            $this->error = true;
            $this->errormsg = $e->getMessage();
            return false;
        }
    }

    public function deleteSession($sessionName){
        $this->error = 0;
        try{
            if($this->serverState < 1){
                throw new Exception("Server is offline");
            } 
            $jsonarray = [
                'function' => 'DeleteSaveSession',
                'data'  => [
                    "SessionName" => $sessionName
                ]
                
            ];
            $data = json_encode($jsonarray);
            $this->fetch_from_api($data, true);
            if($this->responce_code == 204){
                return true;
            }else{
                throw new Exception($this->errorDecode(__FUNCTION__));
            }
        }catch(Exception $e){
            $this->error = true;
            $this->errormsg = $e->getMessage();
            return false;
        }
    }

    public function uploadSave($save_file, $save_name, $LoadSaveGame = false, $EnableAdvancedGameSettings = false){
        $this->error = 0;
        try{
            if($this->serverState < 1){
                $this->error = true;
                throw new Exception("Server is offline");
            }else if(!ini_get('file_uploads')){
                $this->error = true;
                throw new Exception("File uploads are disabled");
            }
            if(!isset($this->server_link)){
                throw new Exception("cURL failed to initialize");
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
            if(!file_exists($save_file['file']['tmp_name'])){
                throw new Exception("File " . $save_file['file']['tmp_name'] ." does not exist, make sure file uploading is enabled");
            }

            if (function_exists('curl_file_create')) { // php 5.5+
                $post['saveGameFile'] = curl_file_create($save_file['file']['tmp_name'], "application/octet-stream", $save_file['file']['name']);
            } else { 
                //this may not function correctly
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
                throw new Exception($this->errorDecode(__FUNCTION__));
            }
        }catch(Exception $e){   
            $this->error = true;
            $this->errormsg = $e->getMessage();
            return false;
        }
    }

    public function deleteSave($save_name){
        $this->error = 0;
        try{
            if($this->serverState < 1){
                $this->error = true;
                throw new Exception("Server is offline");
                return -1;
            }
            if(!$save_name){
                $this->error = true;
                throw new Exception("No save name provided");
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
                throw new Exception($this->errorDecode(__FUNCTION__));
                return false;
            }
        }catch(Exception $e){  
            $this->error = true;
            $this->errormsg = $e->getMessage();
            return false;
        }
    }

    private function errorDecode($function){
        print_r($this->responce);
        $errorJson = json_decode($this->responce, true)['errorMessage'];
        $errormsg = $function . " failed with the following error: ";
        $errormsg .= $errorJson;
        return $errormsg;        
    }




    /**************************************************************************************************************************************
     * Light Weight Server Status Functions
     * 
     * pollServerStatus($address, $port) => get the server status (internal function)
     * parse_LW_Response($data) => parse the server status responce (internal function)
     * 
     **************************************************************************************************************************************/
    
    
     public function checkStatus(){
        $this->error = false;
        try{
            if(!$this->pollServerStatus()){
                throw new Exception("pollServerStatus failed :" . $this->errormsg);
            }
            $status = $this->responce;
            switch($this->checkstatus){
                case "file":
                    $this->Read_Status_from_file($status);
                    break;
                case "db":
                    $this->Read_Status_from_DB($status);
                    break;
                default:
                    throw new Exception("Invalid checkstatus");
            }
            if(empty($oldStatus) || !is_array($oldStatus)){
                $this->Write_Status_to_file($status);
                throw new Exception("no old status file");
                return true;
            }
            if(is_array($oldStatus)){
                $result = $this->arrayDiff($oldStatus, $status);
                if(!is_array($result)){
                    throw new Exception("No difference between old and new status");
                    return false;
                }
                if(!$this->Write_Status_to_file($status)){
                    throw new Exception("Write_Status_to_file failed :" . $this->errormsg);
                }else{
                    return true;
                }
            }
        }catch(Exception $e){
            $this->error = true;
            $this->errormsg = $e->getMessage();
            return false;
        }
    }

    public function pollServerStatus() {
        $msgID = hex2bin('D5F6'); // Protocol Magic identifying the UDP Protocol
        $msgType = chr($this->messageTypes['PollServerState']); // Identifier for 'Poll Server State' message
        $msgProtocol = chr(self::PROTOCOL_VERSION); // Identifier for protocol version identification
        $this->cookie = $msgData = pack('P', microtime(true)); // "Cookie" payload for server state query. Can be anything.
        $msgEnds = chr(1); // End of Message marker
        $msgToServer = $msgID . $msgType . $msgProtocol . $msgData . $msgEnds;
        $msgFromServer = null;

        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        $timeSent = microtime(true);
        socket_sendto($socket, $msgToServer, strlen($msgToServer), 0, $this->server, $this->port);
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 2, 'usec' => 0));

        try {
            $msgFromServer = @socket_recvfrom($socket, $buffer, self::BUFFER_SIZE, 0, $this->server, $this->port);
            if ($msgFromServer === false) {
                throw new Exception('Connection timed out.');
            }
            $timeRecv = microtime(true);
            $this->rawResponse = $buffer;
            $this->latency = $timeRecv - $timeSent;
            if($this->parse_LW_Response($buffer)){
                return true;
            }else{
                return false;
            }
        } catch (Exception $e) {
            $this->error = true;
            $this->errormsg = $e->getMessage();
            return false;
        } finally {
            socket_close($socket);
        }
    }

    

    private function parse_LW_Response($data) {
        try{
            if (empty($data)) {
                throw new Exception('parse_LW_Respon() called with empty response.');
            }

            // Validate the envelope
            $validFingerprint = pack('H*', 'd5f6') . chr($this->messageTypes['ServerStateResponse']) . chr(self::PROTOCOL_VERSION);
            $packetFingerprint = substr($data, 0, 4);
            if ($packetFingerprint !== $validFingerprint && substr($data, 4, 8) == $this->cookie) {
                throw new Exception('Unknown packet received.');
            }

            $packetTerminator = ord(substr($data, -1));
            if ($packetTerminator !== 1) {
                throw new Exception('Unknown packet terminator.');
            }


            $payload = substr($data, 4, -1); // strip the envelope from the datagram
            $this->responce = [];
            $this->responce['ServerStateValue'] = $this->serverState = ord(substr($payload, self::SERVER_STATE, 1));
            $this->responce['ServerStateMsg'] = $this->serverStates[$this->responce['ServerStateValue']];
            $this->status = $this->responce['ServerStateMsg'];
            $this->responce['ServerNetCL'] = unpack('V', substr($payload, self::SERVER_NET_CL, 4))[1];
            $this->version = $this->responce['ServerNetCL'];
            //$this->responce['ServerFlagsBinary'] = unpack('J', substr($payload, self::SERVER_FLAGS, 8))[1];
            $this->responce['ServerFlags'] = str_split(sprintf('%064b', unpack('P', substr($payload, self::SERVER_FLAGS, 8))[1]));
            $this->responce['modded'] = $this->responce['ServerFlags'][0];
            $this->modded = $this->responce['ServerFlags'][0];
            $this->responce['NumSubStates'] = ord(substr($payload, self::NUM_SUB_STATES, 1));
            $this->responce['SubStates'] = [];

            $offsetCursor = self::SUB_STATES;
            for ($i = 0; $i < $this->responce['NumSubStates']; $i++) {
                $SubStateId = $this->substateIDs[ord(substr($payload, $offsetCursor, 1))];
                $offsetCursor += 1;
                $SubStateVersion = unpack('v', substr($payload, $offsetCursor, 2))[1];
                $offsetCursor += 2;
                $this->responce['SubStates'][$SubStateId] = $SubStateVersion;
            }

            $serverNameLengthOffset = $offsetCursor;
            $serverNameOffset = $serverNameLengthOffset + 2;
            $this->responce['ServerNameLength'] = unpack('v', substr($payload, $serverNameLengthOffset, 2))[1];
            $rawName = substr($payload, $serverNameOffset, $this->responce['ServerNameLength']);
            $this->responce['ServerName'] = $rawName;
            $this->serverName = $rawName;

            return true;
        }catch(Exception $e) {
            $this->error = true;
            $this->errormsg = $e->getMessage();
            return false;
        }
    }


    public function Write_Status_to_file($status)
    {
        try{
            if($this->fileName == null){
                throw new Exception("fileName is not set");
            }elseif(is_writable(dirname($this->fileName))){
                throw new Exception("Directory is not writable");
            }elseif(is_writable($this->fileName)){
                throw new Exception("File is not writable");
            }
            $file = fopen($this->fileName, "w");
            fwrite($file, json_encode($status));
            fclose($file);
            return true;
        }catch(Exception $e){
            $this->errormsg = $e->getMessage();
            return false;
        }
    }

    public function Read_Status_from_file()
    {
        try{
            $status = null;
            if($this->fileName == null){
                throw new Exception("fileName is not set");
            }
            if (!file_exists($this->fileName)) {
                throw new Exception("File does not exist");
            }
            if(!$file = fopen($this->fileName, "r")){
                throw new Exception("File is not readable");
            };
            if(filesize($this->fileName)){
                $status = json_decode(fread($file, filesize($this->fileName)), true);
            }else{
                throw new Exception("File is empty");
            }
            fclose($file);
            return $status;
        }catch(Exception $e){
            $this->errormsg = $e->getMessage();
            return false;
        }
        
    }

    public function Read_Status_from_DB()
    {
        try{
            $query = "SELECT `status` FROM `SF_Tools` WHERE `server_address` = ?";
            if(in_array("PDO", get_loaded_extensions())){
                $db = new PDO("mysql:host=" . $this->db_host . ";dbname=" . $this->db_name, $this->db_user, $this->db_pass);
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $stmt = $db->prepare($query);
                $stmt->execute([$this->server]);
                $status = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt->closeCursor();
                $db = null;

            }elseif(in_array("mysqli", get_loaded_extensions())){
                $db = new mysqli($this->db_host, $this->db_user, $this->db_pass, $this->db_name);
                if($db->connect_error){
                    throw new Exception("DB connection failed: " . $db->connect_error);
                }
                $stmt = $db->prepare($query);
                $stmt->bind_param("s", $this->server);
                $stmt->execute();
                $stmt->bind_result($status);
                $stmt->fetch();
                $stmt->close();
                $db->close();
            }else{
                throw new Exception("No database connection available");
            }
            return json_decode($status);
        }catch(Exception $e){
            $this->error = true;
            $this->errormsg = $e->getMessage();
            return false;
        }catch(PDOException $e){
            $this->error = true;
            $this->errormsg = $e->getMessage();
            return false;
        }
    }

    private function Write_Status_to_DB($status){
        try{
            $query = "INSERT INTO `SF_Tools` (`server_address`, `status`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `status` = ?";
            if(in_array("PDO", get_loaded_extensions())){
                $db = new PDO("mysql:host=" . $this->db_host . ";dbname=" . $this->db_name, $this->db_user, $this->db_pass);
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $stmt = $db->prepare($query);
                $stmt->execute([$this->server, json_encode($status), json_encode($status)]);
                $stmt->closeCursor();
                $db = null;

            }elseif(in_array("mysqli", get_loaded_extensions())){
                $db = new mysqli($this->db_host, $this->db_user, $this->db_pass, $this->db_name);
                if($db->connect_error){
                    throw new Exception("DB connection failed: " . $db->connect_error);
                }
                $stmt = $db->prepare($query);
                $stmt->bind_param("sss", $this->server, json_encode($status), json_encode($status));
                $stmt->execute();
                $stmt->close();
                $db->close();
            }else{
                throw new Exception("No database connection available");
            }
            return true;
        }catch(Exception $e){
            $this->error = true;
            $this->errormsg = $e->getMessage();
            return false;
        }catch(PDOException $e){
            $this->error = true;
            $this->errormsg = $e->getMessage();
            return false;
        }
    }

    private function arrayDiff($oldStatus, $newStatus)
    {
        try{
            $result = null;
            if(!is_array($oldStatus) || !is_array($newStatus)){
                throw new Exception("oldStatus or newStatus is not an array");
            }
            foreach ($oldStatus as $key => $value) {
                if (is_array($value)) {
                    $this->arrayDiff($oldStatus[$key], $newStatus[$key]);
                } else {
                    if ($oldStatus[$key] != $newStatus[$key]) {
                        $result[$key] = $newStatus[$key];
                    }
                }
            }
            return $result;
        }catch(Exception $e){
            $this->errormsg = $e->getMessage();
            return false;
        }
    }

    
}

?>

<?php

$api_token = "ewoJInBsIjogIkFQSVRva2VuIgp9.8FF70A287A10207E0498B895AFF9487AE09615C3E4BA3CA580334F8D642B2A61348EE7D5A2168D591F70C4B580359476226F3194EC56FF7E9C2C43FD93F7F9E2";



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

    public $game_state = null;
    public $server_options = null;
    public $advanced_game_settings = null;

    private $server_link;
    private $insecure = false;
    private $url = null;
   
    
    public $error = false;
    public $errormsg = null;
    

    public $responce = [];
    public $rawResponse = null;


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


    function __construct($server = "localhost", $port = 7777, $insecure = true) {
        if($this->validate_url("https://$server:$port/api/v1")){
            $this->url = "https://$server:$port/api/v1";
            $this->server = $server;
            $this->port = $port;
            $this->insecure = $insecure;
            $this->server_link = curl_init();
            $this->set_curl_options();
            $this->Get_LW_server_status();
        }else{
            $this->error = true;
            $this->errormsg = "Invalid URL: https://$server:$port/api/v1 please check the server and port";
        }
    }
    
    private function set_curl_options(){
        if($this->insecure){
            curl_setopt($this->server_link, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($this->server_link, CURLOPT_SSL_VERIFYHOST, false);
        }else{
            curl_setopt($this->server_link, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($this->server_link, CURLOPT_SSL_VERIFYHOST, true);
        }
        curl_setopt($this->server_link, CURLOPT_URL, $this->url);
        curl_setopt($this->server_link, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->server_link, CURLOPT_VERBOSE, true);
        curl_setopt($this->server_link, CURLOPT_POST, true);
    }
    
    public function __destruct() {
        curl_close($this->server_link);
    }

    // set the api key used for authentication
    public function set_api_key($api_key){
        $this->api_token = $api_key;
    }

    // set curl to varify the server ssl certificate
    public function set_SSL_varify($varify = false){
        $this->insecure = $varify;
        $this->set_curl_options();
    }

    // change the server and port
    public function change_server($server, $port = 7777){
        $this->server = $server;
        $this->port = $port;
        $this->url = "https://$server:$port/aget_server_game_statepi/v1";
        $this->set_curl_options();
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
    private function fetch_from_api($data){
        curl_setopt($this->server_link, CURLOPT_HTTPHEADER, [
            "Authorization: $this->api_token",
            "Content-Type: application/json"
            ]);
            curl_setopt($this->server_link, CURLOPT_POSTFIELDS, $data);
            $response = curl_exec($this->server_link);

            if (curl_errno($this->server_link)) {
                $this->error = true;
                $this ->errormsg = curl_error($this->server_link);
                return false;
            } else {
                return $response;
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

    public function get_game_state(){
        $this->error = 0;
        if($this->server_state < 1){
            $this->error = true;
            $this->errormsg = "Server is offline";
            return -1;
        } 
        $data = json_encode(["function" => "QueryServerState"]);
        $response = $this->fetch_from_api($data);
        if($response){
            $this->game_state = json_decode($response, true)["data"]["serverGameState"];
        }else{
            $this->error = true;
            $this->errormsg = "No Responce from server";
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
    

    public function get_server_options(){
        $this->error = 0;
        if($this->server_state < 1){
            $this->error = true;
            $this->errormsg = "Server is offline";
            return -1;
        } 
        $data = json_encode(["function" => "GetServerOptions"]);
        $response = $this->fetch_from_api($data);
        if($response){
            $this->server_options = json_decode($response, true)["data"]['serverOptions'];
        }else{
            $this->error = true;
            $this->errormsg = "No Responce from server";
            return false;
        }
    }

    public function set_server_options(){
        $this->error = 0;
        if($this->server_state < 1){
            $this->error = true;
            $this->errormsg = "Server is offline";
            return -1;
        } 
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
        $response = $this->fetch_from_api($data);
        if(!$response){
            return true
        }else{
            $this->error = true;
            $this->errormsg = json_decode($response, true);
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


    public function get_advanced_game_settings(){
        $this->error = 0;
        if($this->server_state < 1){
            $this->error = true;
            $this->errormsg = "Server is offline";
            return -1;
        } 
        $data = json_encode(["function" => "GetAdvancedGameSettings"]);
        $response = $this->fetch_from_api($data);
        if($response){
            $this->advanced_game_settings = json_decode($response, true)["data"]['advancedGameSettings'];
        }else{
            $this->error = true;
            $this->errormsg = "No Responce from server";
            return false;
        }
    }

    public function set_advanced_game_settings(){
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
        $response = $this->fetch_from_api($data);
        if(!$response){
            return true
        }else{
            $this->error = true;
            $this->errormsg = json_decode($response, true);
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
        $this->server_state = $this->responce['ServerStateRaw'];
        
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

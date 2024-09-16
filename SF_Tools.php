<?php

class SF_Tools {
    const CURRENT_REST_API_VERSION = 1;
    const PROTOCOL_VERSION = 1;

    public $latency = null;
    public $name = null;
    Public $status = null;
    public $modded = null;
    public $version = null;


    public $responce = [];
    public $rawResponse = null;
    
    
    public $error_responce = [
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


    public function Get_server_status($address = 'localhost', $port = 7777) {
        $this->responce = $this->Get_status($address, $port);
        
    }

    private function Get_status($address, $port) {
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
            return $this->parseResponse($buffer);
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
            exit(1);
        } finally {
            socket_close($socket);
        }
    }

    private function parseResponse($data) {
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

// Example usage
$sfTools = new SF_Tools();
$sfTools->Get_server_status("10.12.6.10");
?>
<h1>Server Name: <?= $sfTools->name ?></h1>
<h2>Currently: <?= $sfTools->status ?></h2>
<h3>Running Version:<?= $sfTools->version ?></h3>
Modded: <input <?=($sfTools->modded)?"checked":""?> type="checkbox" id="modded" name="modded" value="modded">
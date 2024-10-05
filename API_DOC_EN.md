# Satisfactory Dedicated Server API
updated 9/26/24

# Introduction
Dedicated Server API consists of two separate endpoints, both operating on the same port as the game server, which is normally *7777*. If the server port is changed (through Engine.ini configuration file, or through `-Port=` command line argument), the API will listen on the specified port instead.

Dedicated Server API endpoints:

-   Dedicated Server *Lightweight Query API* is a simple **UDP** protocol designed for polling the server state through **UDP** continuously with minimal overhead.
-   Dedicated Server *HTTPS API* is a **HTTPS** server serving the requests to retrieve the more detailed state of the server, and control it's behavior.

## API Availability
Lightweight Server Query API is available at all times when the server is running, except for when it is starting up. When the server is performing a save game load or a map change, the lightweight query API will retain it's availability, but will report Loading as the server status. In that state, HTTPS API becomes temporarily unavailable until the blocking work on the server is finished.

### Usage
Query the Lightweight UDP API and check the ServerSubStates for changes to the server, Then use the HTTPS API to collect the changes.

# Contents
### [Lightweight UDP Query API ](#lightweight-udp-query-api)
 - [Flow](#flow)
 - [Protocol](#Protocol)
 - [Message Types](#Message-Types)
	 - [Poll Server State](#poll-server-state)
	 -  [Server State Responce](#server-state-response)
		 - [UniqeID](#uniqeid-unit64-le)
		 - [ServerState](#serverstate--uint8)
		 - [ServerNetCL](#servernetcl-unet32-le)
		 - [ServerFlags](#serverflags-unet64-le)
		 - [ServerSubState](#serversubstate-array)
 ### HTTP API
- [Flow](#flow-1)
	 - [Schema](#schema)
	 - Request Schema
	- Multipart Requests
	- Response Schema
	- File Responces
	- Certificate Validation and Encryption
- API Functions
	- Authentication Functions
		- VarifyAuthenticationToken
		- PasswordlessLogin
		- PasswordLogin
		- ClaimServer
		- SetClientPassword
		- SetAdminPassword
	- Server Commands
		- HealthCheck
		- RunCommand
		- Shutdown
		- RenameServer
		- SetAutoLoadSessionName
		- QueryServerState
		- GetServerOptions
		- ApplyServerOptions
		- GetAdvancedGameSettings
		- ApplyAdvancedGameSettings
	- Save/Session Functions
		- EnumerateSessions
		- CreateNewGame
		- SaveGame
		- LoadGame
		- DeleteSaveFile
		- DeleteSaveSession
		- DownloadSaveGame
		- UploadSaveGame


# Lightweight UDP Query API
Lightweight Query API is a lightweight API designed to allow continuously pulling of data from the server and track server state changes.

## Flow
>A client sends a message of type Poll Server State to the Server API with it's _UID_ When the server receives the message, it will send the Server State Response message to the relevant client, with the _UID_ value on the response copied from the received request.
>
>A client can continuously poll the server for the updates using that API without using much of server CPU. This information can be used to re-fetch only the data previously cached from the HTTPS API that has become outdated

---

## Protocol
>Lightweight Query is a simple request-response UDP protocol with a message-based approach. Note that all data used in the Lightweight Query API is always **_Little Endian_**, and not of network byte order. Since the protocol is UDP, it is unreliable, which means some of the requests might be dropped or not receive responses. It is recommended that the client should not await a response and should in turn ping the server on a set time schedule. Be wary of not trying to ping a dead Lightweight Query API for too long though, since you might end up triggering anti-DDoS measures on the host network.

The protocol consists of a simple message envelope format used for all messages:

| Offset (bytes) | Data Type |  Name | Description|
|--|---|---|--------|
|0  |uint16m (LE)  | ProticalMagic | Always set to *0xF6D5* |
|2	|uint8	|MessageType	|Integer value of the Message type|
|3	|unit8	|ProtocolVersion	|Version of the protocol to be used Current Version is 1 |
|4	|Any	| Payload | Data Sent/Received based on message type	|
|3+sizeof(Payload) | uint8 | TerminatiorBit	| Always 0x1. Messages not ending with the terminator byte will be discarded |


### Message Types:

|Message Type	| Name	| Description	|
|--|---|--------|
|0	|	Poll Server State |	A request sent to the API to retrieve the information about the current server state|
|1	| Server State Responce	| A response sent by the server API back to the client containing the current state of the server	|

 

## Poll Server State
>To poll the server construct the message and include a Unique ID as the Payload for the message. The Unique ID can be any Int64 in **_Little Endian_** format. The Game Client uses current time in UE ticks

To Poll the server state, the message in hex should look like this:
| ProtocalMagic | MessageType | ProtocalVersion | UID | TerminatorBit |
|--|--|--|--|--|
| D5 F6 | 00 | 01 | 72 D6 F5 66 00 00 00 00 | 01 |

### Server State Response
| Offset (bytes) | Data Type | Name | Description |
|--|---|---|--------|
| 0 | uint64 (LE) | UniqueID | The unique identifier for the request that triggered this response. |
| 8 | uint8 | ServerState | Current state that the server is in. See Server States table for details. |
| 9 | uint32 (LE) | ServerNetCL | Game Changelist that the server is running. Changelist of the server must match the game client changelist for the client to be able to connect|
| 13 | uint64 (LE) | ServerFlags | Flags describing this server. Most values are reserved or available for modded servers to use. See Server Flags for more information|
| 21 | uint8 | NumSubStates |Number of Sub State entries in this response. Sub state entries can be used to detect changes in server state |
| 22 | ServerSubState[] | SubStates | Sub state at index i. Number of sub states matches the value of NumSubStates|
| 22+sizeof(SubStates) | uint16 (LE) |ServerNameLength | Length of the ServerName field in bytes |
| 22+sizeof(SubStates)+1 | uint8[] | ServerName | UTF-8 encoded Server Name, as set by the player |

#### UniqeID (unit64) (LE)
>This is the same UID that was used to request the message in **_Little Endian_** format

Example:
| 8 Bytes (hex) | Example Value (int) |
|--|--|
|2F E1 F5 66 00 00 00 00 | 1727389999.546 |
#### ServerState  (uint8)
>A single byte denoting the status of the server based on this table:

| ServerState | Condition | Description |
|--|---|--------|
| 0 | Offline | The server is offline. Servers will never send this as a response |
| 1 | Idle | The server is running, but no save is currently loaded|
| 2 | Loading | The server is currently loading a map. In this state, HTTPS API is unavailable |
| 3 | Playing | The server is running, and a save is loaded. Server is joinable by players |

Example:
| 1 Byte |
|--|
| 03 |

#### ServerNetCL (unet32) (LE)
>The current version of the Change List the server is running in **_Little Endian_** format.

| 4 Bytes (hex) | Example Value (int) |
|--|--|
| FD 99 05 00 | 367101 |

#### ServerFlags (unet64) (LE)
>A series of 1 bit flags for a total of 64 flags. The first flag is to be set for modded gameplay. The remaining are open for custom settings. These may be set by other mods. When checking flags, it is important to remember they are in **_Little Endian_** format.

#### NumSubStates (uint8)
>An integer representing the number of sub states

| 1 Byte (hex) | Example Value (int) |
|--|--|
| 0A | 10|

#### ServerSubState[] (array)
>Sub States are used to determine if any changes have occurred on the server side to Reduce the need for TCP calls.
>
>The following sub states are currently defined by the vanilla dedicated server. Sub states that are not known are not invalid, and should instead be silently discarded.

| Sub State ID | Sub State Name |  Description |
|--|---|--------|
| 0 | ServerGameState | Game state of the server. Maps to QueryServerState HTTPS API function |
| 1 | ServerOptions | Global options set on the server. Maps to GetServerOptions HTTPS API function |
| 2 | AdvancedGameSettings | Advanced Game Settings in the currently loaded session. Maps to GetAdvancedGameSettings HTTPS API function |
| 3 | SaveCollection | List of saves available on the server for loading/downloading has changed. Maps to EnumerateSessions HTTPS API function |
| 4 - 7 | Custom | A value that can be used by the mods or custom servers. Not used by the vanilla clients or servers |

Each substate will be sent as a 2 part message, SubStateId and SubStateVersion. the SubStateVersion will increment when the SubSateID values have changed.
Example SubState Message
| SubStateID | SubStateVersion (hex) | SubStateVersion (int)
|--|--|--|
| 03 | F8 00 00 00 | 248 |

This would indicate that the SaveCollection is at version 248. If re-scanned and the version number has not changed, then No changes have occurred.

# HTTPS API
Dedicated Server HTTPS API is designed for reliably retrieving data from the running dedicated server instance, and performing server management tasks. It is available when the server has started up and not actively loading a save game or performing a map change. To check for the HTTPS API availability, Lightweight Query API can be used.

### Flow
A **_POST_** request is made to **https://{serverAddress}/api/v{apiVersion}** with either a **_application/json_** or **_multipart/form-data_**. This is responded to by the server with either a **_application/json_** or an **_application/octet-stream_**.

## Schema

HTTPS API is based on a simple JSON schema used to pass data to the functions executing on the server, and pass the responses back to the caller. All Server API functions are always executed as POST requests, although certain query requests support being executed through the GET requests, provided that they do not require any data to be provided to them.

### Request Schema

Content Type for requests should be set to application/json. Encoding should preferably be set to utf-8, but Dedicated Servers support all encoding supported by the ICU localization library.

Request Object has the following properties:
| Property Name | Property Type | Description |
|--|--|--------|
| function | string | Name of the API function to execute. Names of the API functions and their behavior is described below|
| data | object | Data to pass to the function to execute. Format of the object depends on the function being executed|

 An example would look like this:
 ```json
{
    "function": "SetAutoLoadSession",
    "data": {
        "SessionName": "What a game"
    }
}
```

Dedicated Server HTTPS API supports the following standard headers:
| Header Name | Notes |
|--|--------|
| Content-Encoding | Optional. Only gzip and deflate are supported |
| Authorization | Required for most non-Authorization API functions. Only Bearer tokens are supported. See Authorization for more info|

The following Satisfactory-specific headers can be also be used in the request:

| Header Name | Data Type | Description | | X-FactoryGame-PlayerId | Hex String | Hex-encoded byte array encoding the ID of the player on behalf of which the request is made |

X-FactoryGame-PlayerId header is only needed to obtain the server join/encryption tickets used for joining the server, and it's format is highly specific to the Satisfactory version running, Unreal Engine version, and the Online Backend used by the player.

Generally, first byte of the ID will be type of the Online Backend used (1 for Epic Games Store, 6 for Steam, see values in UE's EOnlineServices type), and the following bytes are specific to the Online Backend, but will generally represent the player account ID. For Steam for example, it would be a big-endian uint64 representing the player's SteamID64, and for Epic, it would be HEX-encoded EOS ProductUserId string.

Example:
```bash
$ curl --insecure -v -H "Content-Type:application/json"\
-H "Authorization:Bearer xxxxxxxxxx.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"\
-F 'data={"function":"EnumerateSessions"}'\
http://satisfactory.example.com/api/v1
```
### Multipart Requests

Certain Server API functions can take both Server API request payload and a file attachment. Such functions should use **_multipart/form-data_** Content-Type, and encode both the payload and the Server API request body as separate multipart parts.

Multipart Part named "data" should be present in **all** multipart requests, and encode the request object **_JSON_** with the same schema and restrictions as the normal non-multipart requests. The charset should be provided as a separate multipart part with a special name "_charset_", and contain the name of the charset used for encoding the Server API request as a plain text.

Names of other multipart attachments are specific to the functions using multipart requests. Currently multipart requests are only used by `UploadSaveGame` function for uploading save game files.

Example:
```bash
$ curl --insecure -v -H "Content-Type:multipart/form-data"\
-H "Authorization:Bearer xxxxxxxxxx.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"\
-F 'data={"function":"UploadSaveGame","data":{"SaveName":"My Save","LoadSaveGame":true}};type=application/json'\
-F 'saveGameFile=@mySave.sav;type=application/octet-stream'\
http://192.168.1.55/api/v1
``` 

### Response Schema

Dedicated Server can return a variety of different HTTP status codes, most prominent ones are described here:

| Status Code | Status Code Name | Description |
|--|---|--------|
| 200 | Ok | The function has been executed successfully. The response body will either be a Error Response or a Success Response |
| 201 | Created | The function has been executed and returned no error. Returned by some functions to indicate that a new file has been created. |
| 202 | Accepted | The function has been executed, but is still being processed. This is returned by some functions with side deffects, such as `LoadGame` |
| 204 | No Content | The function has been executed successfully, but returned no data (and no error), such as `UploadSaveGame` |
|	400 | Bad Request | Only returned when the request body was failed to be parsed as valid JSON or multipart request. In other cases, error response bad_request is used |
| 401 | Denied |Authentication token is missing, cannot be parsed, or has expired |
| 403 | Forbidden | Provided authentication does not allow executing the provided function, or when function requiring authentication is called without one |
| 404 | Not Found | The specified function cannot be found, or when the function cannot find the specified resource in some cases (for example, for `DownloadSaveGame`) |
| 415 | Unsupported Media | Specified charset or content encoding is not supported, or multipart data is malformed |
| 500 | Server Error | An internal server error has occurred when executing the function |

Content Type of the server response will be set to **_application/json_** and **_utf-8_** encoding. Depending on the outcome of the operation, it might return either an error response or a data response.

Error Response has the following structure:
| Property Name | Property Type | Description |
|--|--|--------|
| errorCode | string | Machine-friendly code indicating the type of the error that the executed function returned|
| errorMessage | string? | Optional. Human-friendly error message explaining the error |
| errorData | object? | Optional. Additional information about the error, for example, list of parameters that are missing |

Success Response has the following structure:
| Property Name | Property Type | Description |
|--|--|--------|
| data | object? |Data returned by the function executed. Type depends on the function the request performed|

#### File Responses

Certain Dedicated Server API functions can respond with a file attachment instead of using the standard Server Response JSON schema. Such responses can be distinguished by the presence of the Content-Disposition header, indicating that the Content-Type and body represent a file attachment and not a standard Server API response body.

Such functions can still return normal server response in case of Error Response. Currently the only function that utilizies the File Response functionality is DownloadSaveGame, which returns the save game file as a response without any additional Server API metadata attachments.

## Certificate Validation and Encryption

HTTPS API is always wrapped into the TLS tunnel, even if the user did not provide the certificate for the Dedicated Server.

User Certificate will be looked up at the following path (where `$InstallRoot$` is the path where the Dedicated Server is installed):
| File Path | File type | Description
|------|--|---|
| `$InstallRoot$/FactoryGame/Certificates/cert_chain.pem` | Certificate Chain (PEM) | Certificate chain in PEM format |
| `$InstallRoot$/FactoryGame/Certificates/private_key.pem` | Private Key (PEM) | Certificate's private key in PEM format|

If no Certificate is provided by the user, Dedicated Server will generate it's own self-signed certificate and use it to encrypt all traffic flowing through the HTTPS API. As such, the clients should be able to handle the HTTPS certificate being self-signed, recognize that case, and handle it appropriately.

The game client, when presented with a self signed certificate from the Dedicated Server, will present it to the user and ask them to manually confirm that the certificate in question is from a trusted authority. Once the user confirms it, the certificate is cached locally, and is trusted for that specific server until the user revokes it or the server changes the certificate.

## Authentication

Dedicated Server API requires authentication for most of it's functions. Authentication format used are Bearer tokens, which are issued by the Dedicated Server when using certain API functions that require no Authentication (such as `PasswordlessLogin`), or functions that require additional security verification (such as `PasswordLogin`). Tokens generated by these functions are short-lived and are bound to the specific player account.

Authentication Tokens internally consist of two parts separated by the dot character ('.'):

-   Base64-encoded JSON token payload
-   HEX-encoded Fingerprint

JSON token payload can be retrieved to determine the privilege level granted by the token, while fingerprint part is used by the server to check the validity of the token and whenever it can be used currently.

Internal Authentication Token Payload:
| Property Name | Property Type | Description |
|--|--|--------|
| pl | string |Privilege Level granted by this token. See possible values below |

Possible Privilege Level values:
| Privilege Level | Description | 
|--|--|
| NotAuthenticated | The client is not Authenticated |
| Client | Client is Authenticated with Client privileges |
| Administrator | Client is Authenticated with Admin privileges |
| InitialAdmin | Client is Authenticated as Initial Admin with privileges to Claim the server |
| APIToken | Client is Authenticated as Third Party Application |

The following functions are used by the game client to perform player authentication:
| Function Name | Description |
|--|----|
| PasswordlessLogin | Attempts logging in as a player without a password. |
| PasswordLogin | Attempts logging in as a player with a password. |
| VerifyAuthenticationToken | Checks if the provided Authentication token is valid. Returns Ok if valid |

Third Party Applications should NOT use `PasswordLogin` or `PasswordlessLogin`, and should instead rely on the Application Tokens.

Application tokens do not expire, and are granted by issuing the command `server.GenerateAPIToken` in the Dedicated Server console. The generated token can then be passed to the Authentication header with Bearer type to perform any Server API requests on the behalf of the server.

Application tokens generated previously can still be pruned using `server.InvalidateAPITokens` console command.

Authentication requirement can be lifted for locally running Dedicated Server instances serving on the loopback network adapter. To allow unrestricted Dedicated Server API access on the localhost, set `FG.DedicatedServer.AllowInsecureLocalAccess` console variable to `1`. It can be performed automatically using the following command line argument: `-ini:Engine:[SystemSettings]:FG.DedicatedServer.AllowInsecureLocalAccess=1`

## API Functions
### Authentication Functions

- #### VerifyAuthenticationToken
minimum authentication level: [NotAuthenticated](#authentication)

>Verifies the Authentication token provided to the Dedicated Server API. Returns No Content and the response code of 204 if the provided token is valid. This function does not require input parameters and does not return any data.

example:
```json
{
"function":"VerifyAuthenticationToken"
}
```
---
- #### PasswordlessLogin
minimum authentication level: [NotAuthenticated](#authentication)

>Attempts to perform a passwordless login to the Dedicated Server as a player. Passwordless login is possible if the Dedicated Server is not claimed, or if Client Protection Password is not set for the Dedicated Server. This function requires no Authentication.

Function Request Data:
| Property Name | Property Type | Description | 
|---|---|--------|
| MinimumPrivilegeLevel | string | Minimum privilege level to attempt to acquire by logging in. See Privilege Level enum for possible values |

example:
```json
{
"function":"PasswordlessLogin",
"data":[
	"MinimumPrivilageLevel": "Client"
	]
}
```
 Function Response Data:
| Property Name | Property Type | Description |
|---|---|--------|
| AuthenticationToken | string | Authentication Token in case login is successful |

example:
```json
{
"data":[
	"AuthenticationToken":"xxxxxxxxxxxxxx.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
	]
}
```

Possible errors:
| Error Code | Description |
|---|--------|
| passwordless_login_not_possible | Passwordless login is not currently possible for this Dedicated Server |

example:
```json
{
"data":[
	"errorCode":"passwordless_login_not_possible"
	"errorMessage":"Passwordless login is not currently possible for this Dedicated Server"
	]
}
```
- #### PasswordLogin
minimum authentication level: [NotAuthenticated](#authentication)

>Attempts to log in to the Dedicated Server as a player using either Admin Password or Client Protection Password. This function requires no Authentication.

Function Request Data:
| Property Name | Property Type | Description |
|---|---|--------|
| MinimumPrivilegeLevel | string |Minimum privilege level to attempt to acquire by logging in. See Privilege Level enum for possible values |
| Password | string | Password to attempt to log in with, in plaintext |
example:
```json
{
"function":"PasswordLogin",
"data":[
	"MinimumPrivilageLevel": "Administrator",
	"Password":"MyPassword"
	]
}
```
Function Response Data:
| Property Name | Property Type | Description |
|---|---|--------|
| AuthenticationToken | string | Authentication Token in case login is successful |
example:
```json
{
"data":[
	"AuthenticationToken":"xxxxxxxxxxxxxx.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
	]
}
```
Possible errors:
| Error Code | Description | 
|---|--------|
| wrong_password | Provided Password did not match any of the passwords set for this Dedicated Server |
example:
```json
{
"data":[
	"errorCode":"wrong_password"
	"errorMessage":"Provided Password did not match any of the passwords set for this Dedicated Server"
	]
}
```
- #### ClaimServer
minimum authentication level: [InitialAdmin](#authentication)

>Claims this Dedicated Server if it is not claimed. Requires InitialAdmin privilege level, which can only be acquired by attempting [PasswordlessLogin](#passwordlesslogin) while the server does not have an Admin Password set, e.g. it is not claimed yet. Function does not return any data in case of success, and the server is claimed. The client should drop InitialAdmin privileges after that and use returned AuthenticationToken instead, and update it's cached server game state by calling [QueryServerState](#QueryServerState).

Function Request Data:
| Property Name | Property Type | Description |
|---|---|--------|
| ServerName | string | New name of the Dedicated Server |
| AdminPassword | string | Admin Password to set on the Dedicated Server, in plaintext |
example:
```json
{
"function":"ClaimServer",
"data":[
	"ServerName": "My Satisfactory Server",
	"AdminPassword":"MyPassword"
	]
}
```
Function Response Data:
| Property Name | Property Type | Description |
|---|---|--------|
| AuthenticationToken | string | New Authentication Token that the Caller should use to drop Initial Admin privileges |
example:
```json
{
"data":[
	"AuthenticationToken":"xxxxxxxxxxxxxx.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
	]
}
```
Possible errors:
| Error Code | Description | 
|---|-----|
| server_claimed | Server has already been claimed |
| insufficient_scope | The client is missing required privileges to access the given function |
example:
```json
{
"data":[
	"errorCode":"server_claimed"
	"errorMessage":"Server has already been claimed"
	]
}
```
- #### SetClientPassword
minimum authentication level: [Administrator](#authentication)

>Updates the currently set Client Protection Password. **This will invalidate all previously issued Client authentication tokens.** Pass empty string to remove the password, and let anyone join the server as Client. Function does not return any data on success and returns response code 204 on success.

Function Request Data:
| Property Name | Property Type | Description |
|---|---|--------|
| Password | string | Client Password to set on the Dedicated Server, in plaintext |
example:
```json
{
"function":"SetClientPassword",
"data":[
	"Password":"MyPassword"
	]
}
```
Possible errors:
| Error Code | Description | 
|---|--------|
| server_not_claimed | Server has not been claimed yet. Use ClaimServer function instead before calling SetClientPassword |
| insufficient_scope | The client is missing required privileges to access the given function |
| password_in_use | Same password is already used as Admin Password |

- #### SetAdminPassword
minimum authentication level: [Administrator](#authentication)

>Updates the currently set Admin Password. This will invalidate all previously issued Client and Admin authentication tokens. Requires Admin privileges. Function does not return any data and returns and response code 204 on success.

Function Request Data:
| Property Name | Property Type | Description |
|---|---|--------|
| Password | string | Admin Password to set on the Dedicated Server, in plaintext
| AuthenticationToken | string | New Admin authentication token to use, since the token used for this request will become invalidated|

Possible errors:
| Error Code | Description | 
|---|--------|
| server_not_claimed | Server has not been claimed yet. Use ClaimServer function instead
| cannot_reset_admin_password | Attempt to set Password to empty string. Admin Password cannot be reset
| password_in_use | Same password is already used as Client Protection Password |
| insufficient_scope | The client is missing required privileges to access the given function |

---
## Server Functions

#### HelthCheck
minimum authentication level: [NotAuthenticated](#authentication)

> Performs a health check on the Dedicated Server API. Allows passing additional data between Modded Dedicated Server and Modded Game Client.

Function Request Data:
| Property Name | Property Type | Description | 
|---|---|------|
| ClientCustomData | string | Custom Data passed from the Game Client or Third Party service. Not used by vanilla Dedicated Servers |

	Example:
```json
{
"function":"HelthCheck",
"data": [
		"ClientCustomData": ""
		]
}
```
Function Response Data:
| Property Name | Property Type | Description |
|---|---|-----|
|Health | string | "healthy" if tick rate is above ten ticks per second, "slow" otherwise |
| ServerCustomData | string | Custom Data passed from the Dedicated Server to the Game Client or Third Party service. Vanilla Dedicated Server returns empty string |
Example:
```json
{
"data":[
	"Health":"healthy",
	"ServerCustomData":""
	]
}
```
- #### RunCommand
minimum authentication level: [Administrator](#authentication)
>Runs the given Console Command on the Dedicated Server, and returns it's output to the Console. Requires Admin privileges.

Function Request Data:
| Property Name | Property Type | Description |
|---|---|--------|
| Command | string |Command Line to run on the Dedicated Server|

Function Response Data:
| Property Name | Property Type | Description |
|---|---|--------|
| CommandResult | string | Output of the command executed, with \n used as line separator |

- #### Shutdown

>Shuts down the Dedicated Server. If automatic restart script is setup, this allows restarting the server to apply new settings or update. Requires Admin privileges. Shutdowns initiated by remote hosts are logged with their IP and their token. Function does not return any data on success, and does not take any parameters.

- #### RenameServer
- #### SetAutoLoadSessionName
- #### QueryServerState
- #### GetServerOptions
- #### ApplyServerOptions
- #### GetAdvancedGameSettings
- #### ApplyAdvancedGameSettings
### Save/Session Functions
- #### EnumerateSessions
- #### CreateNewGame
- #### SaveGame
- #### LoadGame
- #### DeleteSaveFile
- #### DeleteSaveSession
- #### DownloadSaveGame
- #### UploadSaveGame

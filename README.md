
### README.md

```markdown
# SF_Tools

`SF_Tools` is a PHP class designed to interact with a server using the v1 API. It sends a request to the server and parses the response to determine the server's state, optional settings, and advanced settings.

more to come soon

## Features

- Establishes the connection to the server and formats the requests
- Receives and parses the server's response.
- Extracts and provides detailed server state information.

## Installation

1. Clone the repository or download the `SF_Tools.php` file.
2. Include the `SF_Tools.php` file in your project.

```php
require_once 'path/to/SF_Tools.php';
```

## Usage

### Example Usage

```php
<?php
require_once 'path/to/SF_Tools.php';

$sfTools = new SF_Tools();
$sfTools->set_api_key(" {apikey}  ");
$sfTools->get_game_state();
print_r($sfTools->game_state);
?>
```

### Methods

#### `probeLightAPI($address = 'test.example.com', $port = 7777)`

Sends a UDP message to the specified server and port, and returns the response and latency.

- **Parameters:**
  - [`$address`](command:_github.copilot.openSymbolFromReferences?%5B%22%22%2C%5B%7B%22uri%22%3A%7B%22scheme%22%3A%22file%22%2C%22authority%22%3A%22%22%2C%22path%22%3A%22%2Fmnt%2Fhtml%2FSF_Tools.php%22%2C%22query%22%3A%22%22%2C%22fragment%22%3A%22%22%7D%2C%22pos%22%3A%7B%22line%22%3A341%2C%22character%22%3A35%7D%7D%5D%2C%22d90b0c81-67ba-4267-a4e5-840253b5e79d%22%5D "Go to definition") (string): The server address. Default is `'test.example.com'`.
  - [`$port`](command:_github.copilot.openSymbolFromReferences?%5B%22%22%2C%5B%7B%22uri%22%3A%7B%22scheme%22%3A%22file%22%2C%22authority%22%3A%22%22%2C%22path%22%3A%22%2Fmnt%2Fhtml%2FSF_Tools.php%22%2C%22query%22%3A%22%22%2C%22fragment%22%3A%22%22%7D%2C%22pos%22%3A%7B%22line%22%3A341%2C%22character%22%3A45%7D%7D%5D%2C%22d90b0c81-67ba-4267-a4e5-840253b5e79d%22%5D "Go to definition") (int): The server port. Default is `7777`.

- **Returns:**
  - An array containing the response data and latency.

#### `parseLightAPIResponse($data)`

Parses the response data from the server.

- **Parameters:**
  - [`$data`](command:_github.copilot.openSymbolFromReferences?%5B%22%22%2C%5B%7B%22uri%22%3A%7B%22scheme%22%3A%22file%22%2C%22authority%22%3A%22%22%2C%22path%22%3A%22%2Fmnt%2Fhtml%2FSF_Tools.php%22%2C%22query%22%3A%22%22%2C%22fragment%22%3A%22%22%7D%2C%22pos%22%3A%7B%22line%22%3A376%2C%22character%22%3A39%7D%7D%5D%2C%22d90b0c81-67ba-4267-a4e5-840253b5e79d%22%5D "Go to definition") (string): The response data from the server.

- **Returns:**
  - An associative array containing the parsed response data.

#### `Get_LW_server_status()`

Gets the server status by calling [`Get_LW_status`](command:_github.copilot.openSymbolFromReferences?%5B%22%22%2C%5B%7B%22uri%22%3A%7B%22scheme%22%3A%22file%22%2C%22authority%22%3A%22%22%2C%22path%22%3A%22%2Fmnt%2Fhtml%2FSF_Tools.php%22%2C%22query%22%3A%22%22%2C%22fragment%22%3A%22%22%7D%2C%22pos%22%3A%7B%22line%22%3A336%2C%22character%22%3A33%7D%7D%5D%2C%22d90b0c81-67ba-4267-a4e5-840253b5e79d%22%5D "Go to definition") and sets the server state.

#### `Get_LW_status($address, $port)`

Sends a UDP message to the specified server and port, and returns the parsed response.

- **Parameters:**
  - [`$address`](command:_github.copilot.openSymbolFromReferences?%5B%22%22%2C%5B%7B%22uri%22%3A%7B%22scheme%22%3A%22file%22%2C%22authority%22%3A%22%22%2C%22path%22%3A%22%2Fmnt%2Fhtml%2FSF_Tools.php%22%2C%22query%22%3A%22%22%2C%22fragment%22%3A%22%22%7D%2C%22pos%22%3A%7B%22line%22%3A341%2C%22character%22%3A35%7D%7D%5D%2C%22d90b0c81-67ba-4267-a4e5-840253b5e79d%22%5D "Go to definition") (string): The server address.
  - [`$port`](command:_github.copilot.openSymbolFromReferences?%5B%22%22%2C%5B%7B%22uri%22%3A%7B%22scheme%22%3A%22file%22%2C%22authority%22%3A%22%22%2C%22path%22%3A%22%2Fmnt%2Fhtml%2FSF_Tools.php%22%2C%22query%22%3A%22%22%2C%22fragment%22%3A%22%22%7D%2C%22pos%22%3A%7B%22line%22%3A341%2C%22character%22%3A45%7D%7D%5D%2C%22d90b0c81-67ba-4267-a4e5-840253b5e79d%22%5D "Go to definition") (int): The server port.

- **Returns:**
  - An associative array containing the parsed response data.

#### `parse_LW_Response($data)`

Parses the response data from the server.

- **Parameters:**
  - [`$data`](command:_github.copilot.openSymbolFromReferences?%5B%22%22%2C%5B%7B%22uri%22%3A%7B%22scheme%22%3A%22file%22%2C%22authority%22%3A%22%22%2C%22path%22%3A%22%2Fmnt%2Fhtml%2FSF_Tools.php%22%2C%22query%22%3A%22%22%2C%22fragment%22%3A%22%22%7D%2C%22pos%22%3A%7B%22line%22%3A376%2C%22character%22%3A39%7D%7D%5D%2C%22d90b0c81-67ba-4267-a4e5-840253b5e79d%22%5D "Go to definition") (string): The response data from the server.

- **Returns:**
  - An associative array containing the parsed response data.

## Example

```php
<<?php
require_once 'path/to/SF_Tools.php';

$sfTools = new SF_Tools();
$sfTools->set_api_key(" {apikey}  ");
$sfTools->get_game_state();
print_r($sfTools->game_state);
?>
```

## License

This project is licensed under the MIT License - see the LICENSE file for details.

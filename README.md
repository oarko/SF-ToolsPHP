
```markdown
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
$sfTools = new SF_Tools("{server address}", 7777, true);
```

### Setting the API Key

Set the API key for authentication:
Application tokens do not expire, and are granted by issuing the command server.GenerateAPIToken in the Dedicated Server console. The generated token can then be passed to the Authentication header with Bearer type to perform any Server API requests on the behalf of the server.

```php
$sfTools->set_api_key("Bearer {your_api_key_here}");
```

### Retrieving Server Options

Retrieve the server options:

```php
$sfTools->get_server_options();
```

### Setting Server Options

Set the server options: (uses $this->server_options)

```php
$sfTools->set_server_options();
```

### Retrieving Advanced Game Settings

Retrieve the advanced game settings:

```php
$sfTools->get_advanced_game_settings();
```

### Setting Advanced Game Settings

Set the Advanced Game Settings: (uses $this->advanced_game_settings)

```php
$sfTools->set_advanced_game_settings();
```

### Retrieving Advanced Game Settings

### Error Handling

Check if there was an error during the last operation:

```php
$sfTools->error ? print_r($sfTools->errormsg) : "";
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

- **Your Name** - *Initial work* - [Oarko](https://github.com/Oarko)

## Acknowledgments

- Hat tip to anyone whose code was used
- Inspiration
- etc
```

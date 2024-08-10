[![Current version](https://img.shields.io/packagist/v/maatify/cloudflare-turnstile)][pkg]
[![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/maatify/cloudflare-turnstile)][pkg]
[![Monthly Downloads](https://img.shields.io/packagist/dm/maatify/cloudflare-turnstile)][pkg-stats]
[![Total Downloads](https://img.shields.io/packagist/dt/maatify/cloudflare-turnstile)][pkg-stats]
[![Stars](https://img.shields.io/packagist/stars/maatify/cloudflare-turnstile)](https://github.com/maatify/CloudflareTurnstile/stargazers)

[pkg]: <https://packagist.org/packages/maatify/cloudflare-turnstile>
[pkg-stats]: <https://packagist.org/packages/maatify/cloudflare-turnstile/stats>

# Installation

```shell
composer require maatify/cloudflare-turnstile
```

# Usage

```PHP
<?php
/**
 * Created by Maatify.dev
 * User: Maatify.dev
 * Date: 2024-08-05
 * Time: 11:48 AM
 * https://www.Maatify.dev
 */
 
use Maatify\Turnstile\TurnstileValidation;

require 'vendor/autoload.php';

$secret_key = '1x0000000000000000000000000000000AA';

$turnstile = TurnstileValidation::getInstance($secret_key);

// ===== get result in array format
$result = $turnstile->getResponse();

// ====== get bool of validation 
$result = $turnstile->isSuccess();

// ====== using maatify json on error response with json code with die and if success there is no error
$turnstile->jsonErrors();
```

### examples
#### getResponse();
>##### Success Example
>       Array
>       (
>           [success] => 1
>           [error-codes] => Array
>               (
>               )
>
>           [challenge_ts] => 2024-08-07T04:45:23.540Z
>           [hostname] => maatify
>           [action] => 
>           [cdata] => 
>           [metadata] => stdClass Object
>               (
>                   [interactive] => 
>               )
>       
>       )
>
>##### Error Example
>       array
>           (
>               [success] =>
>               [error-codes] => Array
>                   (
>                       [0] => timeout-or-duplicate
>                   )
>
>               [messages] => Array
>                   (
>                   )
>
>           )


#### isSuccess();
>return true || false


#### jsonErrors();
>##### Error Example
> 
>   Header 400 
> 
>   Body:
> 
> - on validation error
> 
>```json
>{
>  "success": false,
>  "response": 40002,
>  "var": "captcha",
>  "description": {
>    "success": false,
>    "error-codes": [
>      "invalid-input-secret"
>    ],
>    "messages": []
>  },
>  "more_info": "invalid-input-secret",
>  "error_details": "test:72"
>}
>```
>```json
>{
>  "success": false,
>  "response": 40002,
>  "var": "captcha",
>  "description": {
>    "success": false,
>    "error-codes": [
>      "timeout-or-duplicate"
>    ],
>    "messages": []
>  },
>  "more_info": "timeout-or-duplicate",
>  "error_details": "test:72"
>}
>```

> - on missing or empty `$_POST['cf-turnstile-response']`
>```json
>   {
>       "success": false,
>       "response": 1000,
>       "var": "cf-turnstile-response",
>       "description": "MISSING Cf-turnstile-response",
>       "more_info": "",
>       "error_details": ""
>   }
>```


### Create From in HTML Code
```html
<form action="validate.php" method="POST">
    <input name="test" value="test">
    <!-- Your other form fields -->
    
    <!-- add theme and language -->
    <div class="cf-turnstile" data-sitekey="__YOUR_SITE_KEY__" data-theme="dark" data-language="ar"></div>
    <input type="submit" value="Submit">
</form>

<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
```
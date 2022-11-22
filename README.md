# Websocket Package for Laravel

Websocket supports HTTP, Websocket, SSL and other custom protocols.

## Requirement

* Laravel >=6
* PHP >=7.3
* Workerman package

## Setup package
### 1- Install packages
```shell
composer require workerman/workerman
composer require workerman/channel
```

### 2- Download files
make directory: `<laravel-project-path>/packages/sirj3x`

clone repository: `git clone <your-repo-link>`

### 3- Add service provider
add line to: `config/app.php`
``` php
'providers' => [
    /*
     * Package Service Providers...
     */
    Sirj3x\Websocket\WebsocketServiceProvider::class, // add this
]
```

### 4- Add package source to composer
add line to: `composer.json`
``` php
"autoload-dev": {
    "psr-4": {
        "Tests\\": "tests/",
        "Sirj3x\\Websocket\\": "packages/sirj3x/websocket/src" // add this
    }
},
```
and run this command: `composer dump-autoload`

### 5- Finally
first time for setup and configure files, run this command:
```bash
php artisan ws:setup
```

## Publish the config
You can run `vendor:publish` command to have config file of package on this path: `config/jxt.php`
``` bash
php artisan vendor:publish --provider="Sirj3x\Websocket\WebsocketServiceProvider"
```
You should now have a `config/websocket.php` file that allows you to configure the basics of this package.

## The Basics

### # General
Run websocket [**Debug mode**]: `php artisan ws:start`

Run websocket [**Daemon mode**]: `php artisan ws:start --d`


### # Event

#### - Defining Event
To create a new event, use the `make:ws-event` Artisan command:
``` bash
php artisan make:ws-event ExampleEvent
```
for make request for this event need add `-r` to this command: `php artisan make:ws-event Example -r`

<br>

### # Middleware

#### - Defining Middleware
To create a new event, use the `make:ws-middleware` Artisan command:
``` bash
php artisan make:ws-middleware CheckPermission
```

#### - Registering Middleware
List the middleware class in the `middleware` index of your `config/websocket.php`.

<br>

### # Request (Validator Rules)

#### - Defining Request
To create a new event, use the `make:ws-request` Artisan command:
``` bash
php artisan make:ws-request UpdateUserRequest
```

<br>

#### - Registering Request
For registering request, you can add `$request` to your event.
```php
class ExampleEvent extends Event
{
    public $request = ExampleRequest::class; // this

    public function __invoke($event, $data, $user): array
    {
        return $this->success([]);
    }
}
```


## License
The [MIT license](http://opensource.org/licenses/MIT) (MIT). Please see [License File](https://github.com/sadegh19b/laravel-persian-validation/blob/master/LICENSE.md) for more information.

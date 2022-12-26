# Websocket Package for Laravel

Websocket supports HTTP, Websocket, SSL and other custom protocols.

## Requirement

* Laravel >=6
* PHP >=7.3

## Install package from local git
```json
"repositories": {
    "sirj3x/websocket": {
        "type": "package",
        "package": {
            "name": "sirj3x/websocket",
            "require": {
                "php": ">=7.3",
                "workerman/workerman": "^4.0",
                "workerman/channel": "^1.1"
            },
            "autoload": {
                "psr-4": {
                    "Sirj3x\\Websocket\\": "src/"
                }
            },
            "extra": {
                "laravel": {
                    "providers": [
                        "Sirj3x\\Websocket\\WebsocketServiceProvider"
                    ]
                }
            },
            "version": "1.0",
            "source": {
                "url": "https://gitlab.dornica.local/structure/backend/websocket-laravel-package.git",
                "type": "git",
                "reference": "origin/main"
            }
        }
    }
}
```
then run `composer update` to install package

## Setup
first time for setup and configure files, run this command:
```bash
php artisan ws:setup
```

## Publish the config
You can run `vendor:publish` command to have config file of package on this path: `config/websocket.php`
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

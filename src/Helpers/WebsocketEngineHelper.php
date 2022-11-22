<?php

namespace Sirj3x\Websocket\Helpers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class WebsocketEngineHelper
{
    public static function sendError($conn, $message, $statusCode, $disconnect = false)
    {
        if ($conn) {
            $conn->send(ParserHelper::encode([
                'event' => 'error',
                'status' => $statusCode,
                'data' => [
                    'message' => [$message]
                ]
            ]));
            if ($disconnect) $conn->close();
            return false;
        } else {
            return [
                'event' => 'error',
                'status' => $statusCode,
                'data' => [
                    'message' => [$message]
                ]
            ];
        }
    }

    public static function routes(): array
    {
        return include base_path('routes/websocket.php');
    }

    public static function getRouteClass($event)
    {
        $routes = self::routes();
        return $routes[$event] ?? false;
    }

    public static function getUserGuardFromTableRow($row)
    {
        $guard = substr($row->getTable(), 0, strlen($row->getTable()) - 1);
        if ($guard == 'store_customer') return 'customer';
        return $guard;
    }

    public static function getGuardModel($guard)
    {
        $guardProvider = config('auth.guards')[$guard]["provider"] ?? null;
        if ($guardProvider === null) return null;
        $guardModel = config('auth.providers')["$guardProvider"]["model"] ?? null;
        if ($guardModel === null) return null;
        return $guardModel;
    }

    public static function getGuards(): array
    {
        $final = [];

        $guards = config('auth.guards');
        if (isset($guards["web"])) unset($guards["web"]);
        if (isset($guards["sanctum"])) unset($guards["sanctum"]);

        foreach ($guards as $guardName => $guardDetail) {
            $final[] = $guardName;
        }

        return $final;
    }
}

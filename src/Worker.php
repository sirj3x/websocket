<?php

namespace Sirj3x\Websocket;

use Channel\Client;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use Sirj3x\Jxt\JxtToken;
use Sirj3x\Websocket\Helpers\ParserHelper;
use Sirj3x\Websocket\Helpers\BaseHelper;
use Sirj3x\Websocket\Helpers\ResponseHelper;
use Sirj3x\Websocket\Helpers\StringHelper;
use Workerman\Worker as WorkermanWorker;

class Worker extends WorkermanWorker
{
    use ResponseHelper;

    // access with user_id
    protected array $websocket_users = [];

    // access with connection_id
    protected array $authenticated_users = [];

    public function __construct()
    {
        // set log file
        static::$logFile = config('websocket.log_path');

        // Set address
        $socket_name = 'websocket://' . config('websocket.ip') . ':' . config('websocket.port');

        // SSL context.
        $context_option = config('websocket.context');

        if (config('websocket.transport_ssl')) {
            // Enable SSL. WebSocket+SSL means that Secure WebSocket (wss://).
            // The similar approaches for Https etc.
            $this->transport = 'ssl';
        }

        $this->name = 'WebsocketServer';

        // When the client initiates a connection event, it sets various event callbacks to connect to the socket
        // When $sender_ After IO starts, it listens to a http port through which it can push data to any uid or all UIDs
        $this->onWorkerStart = function ($worker) {
            $this->onWorkerStart($worker);
            $this->timers($worker);
        };

        // Emitted when new connection come
        $this->onConnect = function ($connection) {
            $this->onConnect($connection);
        };

        // Emitted when data received
        $this->onMessage = function ($connection, $data) {
            $this->updateActivityTime($connection);
            $this->onMessage($connection, $data);
        };

        // Emitted when connection closed
        $this->onClose = function ($connection) {
            $this->onClose($connection);
        };

        parent::__construct($socket_name, $context_option);
    }

    protected function setupEvent($connection, $guard, $user)
    {
        //
    }

    protected function onConnect($connection)
    {
        $connection->onWebSocketConnect = function ($connection) {
            if (!isset($_GET["token"])) {
                BaseHelper::sendError($connection, 'Auth token not found.', 401, true);
                return;
            }

            $token = $_GET["token"];

            $user = JxtToken::loginWithToken($token);
            if (!$user) {
                BaseHelper::sendError($connection, 'Token is not valid.', 401, true);
                return;
            }

            $this->removeExpireConnections($connection->id);

            $this->websocket_users[$user["guard"]][$user["id"]][$connection->id] = [
                'connection' => $connection,
                'updated_at' => strtotime('now')
            ];

            $this->authenticated_users[$connection->id] = [
                'guard' => $user["guard"],
                'user_id' => $user["id"],
                'token' => $token
            ];

            // send setup event to client
            $this->setupEvent($connection, $user["guard"], $user);
        };
    }

    protected function onMessage($connection, $data): bool
    {
        $connection->send(ParserHelper::encode([
            'response' => 'OK'
        ]));

        return true;
    }

    protected function onClose($connection)
    {
        if (!isset($this->authenticated_users[$connection->id])) return;

        $guard = $this->authenticated_users[$connection->id]["guard"];
        $user_id = $this->authenticated_users[$connection->id]["user_id"];

        unset($this->websocket_users[$guard][$user_id][$connection->id]);
        unset($this->authenticated_users[$connection->id]);
    }

    /**
     * @throws Exception
     */
    protected function onWorkerStart($worker)
    {
        //###########################
        //### channel server
        //###########################

        // Channel client connect to Channel Server.
        Client::connect(config('websocket.ptc_channel_ip'), config('websocket.ptc_channel_port'));
        //Client::connect('unix:///tmp/workerman-channel.sock');

        // Subscribe broadcast event .
        Client::on('broadcast', function ($data) use ($worker) {
            $this->handlePtcData($data);
        });

        //###########################
        //### inner tcp connection
        //###########################

        // create local tcp-server
        $inner_tcp_worker = new WorkermanWorker("tcp://" . config('websocket.ptc_tcp_ip') . ":" . config('websocket.ptc_tcp_port'));
        $inner_tcp_worker->onMessage = function ($connection, $data) {
            $data = StringHelper::parseTcpConnectionData($data);
            if ($data) $this->handlePtcData($data);
            $connection->close();
        };
        $inner_tcp_worker->listen();
    }

    private function handlePtcData($data): void
    {
        // fix validator for data in exist_event
        if (count($data["data"]) == 0) $data["data"] = [];

        $validator = Validator::make($data, [
            'type' => ['required', 'in:exist_event'],
            'user_id' => ['required'],
            'user_guard' => ['required', 'in:' . StringHelper::arrayToIds(BaseHelper::getGuards())],
            'event' => ['required_if:type,exist_event', 'string'],
            'listener_key' => ['nullable', 'string'],
            //'data' => ['required', 'array'],
            'data.event' => ['required_if:type,custom_event', 'string'],
            'data.status' => ['required_if:type,custom_event', 'numeric', 'min:100', 'max:599'],
            'data.data' => ['required_if:type,custom_event'],
        ]);

        if ($validator->fails()) return;

        $type = $data["type"];

        $listener_key = $data["listener_key"] ?? null;

        if (is_array($data["user_id"])) {

            foreach ($data["user_id"] as $user_id) {
                if (isset($this->websocket_users[$data["user_guard"]][$user_id])) {
                    $connections = $this->websocket_users[$data["user_guard"]][$user_id];
                    foreach ($connections as $connectionItem) {
                        $connection = $connectionItem["connection"];
                        if ($type == 'exist_event') {
                            $this->sendToUserExistEvent($connection, $data['event'], $data['user_guard'], $this->getWebsocketUserData($connection->id), $data['data'], $listener_key);
                        }
                    }
                }
            }

        } elseif (is_numeric($data["user_id"])) {

            $user_id = $data["user_id"];
            if (isset($this->websocket_users[$data["user_guard"]][$user_id])) {
                $connections = $this->websocket_users[$data["user_guard"]][$user_id];
                foreach ($connections as $connectionItem) {
                    $connection = $connectionItem["connection"];
                    if ($type == 'exist_event') {
                        $this->sendToUserExistEvent($connection, $data['event'], $data['user_guard'], $this->getWebsocketUserData($connection->id), $data['data'], $listener_key);
                    }
                }

            }

        }
    }

    protected function getWebsocketUserData($connection_id, $toArray = true)
    {
        if (isset($this->authenticated_users)) {
            if (isset($this->authenticated_users[$connection_id])) {
                $authenticated_guard = $this->authenticated_users[$connection_id]["guard"];
                $authenticated_guard_model = BaseHelper::getGuardModel($authenticated_guard);
                $authenticated_user_id = $this->authenticated_users[$connection_id]["user_id"];
                $query = new $authenticated_guard_model;
                $user = $query->find($authenticated_user_id);
                if ($toArray) $user = $user->toArray();
                return $user;
            }
        }
        return null;
    }

    public static function callEvent($connection, $event, $userGuard, $userData, $data)
    {
        $event = BaseHelper::getRouteClass($event);
        if ($event === false || !class_exists($event['class'])) {
            return BaseHelper::sendError($connection, 'Event not registered.', 404);
        }
        $class = $event['class'];

        /*if ($event['type'] == 'publish' && $connection !== null) {
            return BaseHelper::sendError($connection, 'You are not authorized to call this event.', 401);
        }*/

        $classIntent = new $class();

        // dispatch middleware
        $middleware = $classIntent->middleware;
        if (count($middleware) > 0) {
            $allMiddleware = config('websocket.middleware');
            foreach ($middleware as $item) {
                if (!isset($allMiddleware[$item])) {
                    return BaseHelper::sendError($connection, 'Middleware not registered.', 400);
                }

                $middlewareResult = App::call($allMiddleware[$item], [
                    'event' => $event,
                    'userGuard' => $userGuard,
                    'userData' => $userData,
                    'request' => $data,

                ]);

                if (!isset($middlewareResult["status"])) {
                    return BaseHelper::sendError($connection, 'Middleware dispatcher error.', 400);
                }

                if ($middlewareResult["status"] == 0) {
                    if (!isset($middlewareResult["message"])) {
                        return BaseHelper::sendError($connection, 'Middleware dispatcher error.', 400);
                    }

                    return BaseHelper::sendError($connection, $middlewareResult["message"], 400);
                }
            }
        }

        // dispatch request validator
        $requestRulesClass = $classIntent->request;
        if ($requestRulesClass !== null) {
            $requestRulesClass = new $requestRulesClass();
            $requestRules = $requestRulesClass->rules($userData);
            $validator = Validator::make($data, $requestRules);
            if ($validator->fails()) {
                return BaseHelper::sendError($connection, $validator->messages(), 400);
            }
            $data = self::fieldsOutOfValidator($validator->validated(), $data);
        }

        // call event class
        $result = App::call($class, [
            'event' => $event,
            'userGuard' => $userGuard,
            'userData' => $userData,
            'request' => $data
        ]);

        if ($result === false) return false;
        if (!is_array($result)) $result = [];

        return array_merge(['event' => $event['name']], $result);
    }

    protected function timers($worker)
    {
        //
    }

    protected function sendToUserExistEvent($connection, $event, $userGuard, $userData, $data, $listener_key)
    {
        $result = self::callEvent($connection, $event, $userGuard, $userData, $data);

        if ($listener_key && strlen($listener_key) > 5) {
            $result = array_merge($result, [
                'listener_key' => $listener_key
            ]);
        }

        $connection->send(ParserHelper::encode($result));
    }

    public static function fieldsOutOfValidator($data, $request): array
    {
        $validator = Validator::make([
            'sort_field' => $request["sort_field"] ?? null,
            'sort_type' => $request["sort_type"] ?? null,
        ], [
            'sort_field' => ['required'],
            'sort_type' => ['required'],
        ]);

        if ($validator->fails()) {
            return $data;
        } else {
            return array_merge($data, [
                'sort_field' => $request["sort_field"],
                'sort_type' => $request["sort_type"],
            ]);
        }
    }

    private function updateActivityTime($connection): void
    {
        // check connection data
        if (!isset($this->authenticated_users[$connection->id])) {
            BaseHelper::sendError($connection, 'Unauthenticated.', 401, true);
            return;
        }

        // connection data ready for use
        $connection_data = $this->authenticated_users[$connection->id];

        // save connection's last activity time
        $this->websocket_users[$connection_data["guard"]][$connection_data["user_id"]][$connection->id]["updated_at"] = strtotime('now');
    }

    private function removeExpireConnections($connection_id): void
    {
        // get connection data
        $connection_data = $this->authenticated_users[$connection_id] ?? null;
        if (!$connection_data) return;

        // get list of connections
        $connections = $this->websocket_users[$connection_data["guard"]][$connection_data["user_id"]] ?? null;

        // check has connection
        if (!$connections || !is_array($connections) || count($connections) == 0) return;

        // check connection
        foreach ($connections as $connection_id => $item) {
            if ($item["updated_at"] < (strtotime('now') + 2000)) {
                unset($this->websocket_users[$connection_data["guard"]][$connection_data["user_id"]][$connection_id]);
                unset($this->authenticated_users[$connection_id]);
            }
        }
    }
}

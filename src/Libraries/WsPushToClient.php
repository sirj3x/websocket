<?php

namespace Sirj3x\Websocket\Libraries;

class WsPushToClient
{
    public static function existEvent($user_id, $user_guard, $event, array $request = [])
    {
        try {
            \Channel\Client::publish('broadcast', [
                'type' => 'exist_event',
                'user_id' => $user_id,
                'user_guard' => $user_guard,
                'event' => $event,
                'data' => $request,
            ]);
        } catch (\Exception $exception) {
        }
    }

    public static function existEventTcp($user_id, $user_guard, $event, array $request = [])
    {
        try {
            $params = [
                'type' => 'exist_event',
                'user_id' => $user_id,
                'user_guard' => $user_guard,
                'event' => $event,
                'data' => $request,
            ];

            $curl = curl_init("http://" . config('websocket.ptc_tcp_ip') . ":" . config('websocket.ptc_tcp_port'));
            curl_setopt($curl, CURLOPT_POST, false);
            curl_setopt($curl, CURLOPT_POSTFIELDS, '#0#0#' . json_encode($params) . '#1#1#');
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_VERBOSE, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_exec($curl);
            curl_close($curl);
        } catch (\Exception $exception) {}
    }

    public static function customEvent($user_id, $user_guard, $event, $data, $statusCode = 200)
    {
        try {
            \Channel\Client::publish('broadcast', [
                'type' => 'custom_event',
                'user_id' => $user_id,
                'user_guard' => $user_guard,
                'data' => [
                    'event' => $event,
                    'status' => $statusCode,
                    'data' => $data
                ],
            ]);
        } catch (\Exception $exception) {
        }
    }
}

<?php

namespace Sirj3x\Websocket\Libraries;

class PTC
{
    public static function send($user_id, $user_guard, $event, array $request = []): void
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

    public static function sendTcp($user_id, $user_guard, $event, array $request = []): void
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
        } catch (\Exception $exception) {
        }
    }
}

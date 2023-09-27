<?php

namespace Sirj3x\Websocket\Helpers;

class StringHelper
{
    public static function idsToArray($string): array
    {
        return explode(',', $string);
    }

    public static function arrayToIds($array): string
    {
        $number = 1;
        $string = '';
        foreach ($array as $item) {
            $string .= $item;
            if (count($array) != $number) $string .= ',';
            $number++;
        }
        return $string;
    }

    public static function parseTcpConnectionData($data)
    {
        try {
            $data = explode('#0#0#', $data);
            $data = $data[1];
            $data = explode('#1#1#', $data);
            $data = $data[0];
            return json_decode($data, true);
        } catch (\Exception $exception) {
            return null;
        }
    }
}

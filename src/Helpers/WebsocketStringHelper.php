<?php

namespace Sirj3x\Websocket\Helpers;

class WebsocketStringHelper
{
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
}

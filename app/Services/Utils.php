<?php

namespace App\Services;


class Utils
{
    public static function prepareInboundForUpdate($inbound)
    {
        return [
            'id'        => $inbound->id,
            'userId'    => $inbound->user_id,
            'up'        => $inbound->up,
            'down'      => $inbound->down,
            'total'     => $inbound->total,
            'remark'    => $inbound->remark,
            'enable'    => $inbound->enable,
            'expiryTime' => $inbound->expiry_time,
            'listen'    => $inbound->listen,
            'port'      => $inbound->port,
            'protocol'  => $inbound->protocol,
            'settings'  => $inbound->settings,
            'streamSettings'    => $inbound->stream_settings,
            'tag'               => $inbound->tag,
            'sniffing'          => $inbound->sniffing
        ];
    }

    public static function portGenerator()
    {
        return rand(12000, 49999);
    }
}
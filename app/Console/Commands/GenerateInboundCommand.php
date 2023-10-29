<?php

namespace App\Console\Commands;

use App\Repositories\InboundsDB;
use App\Services\Utils;
use Carbon\Carbon;
use Faker\Core\Uuid;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateInboundCommand extends Command
{

    protected $signature = 'generate:inbound';


    protected $description = 'generate bulk inbounds';

    public $ips;
    public $all_ports = [];

    public function __construct()
    {
        parent::__construct();
    }


    public function handle()
    {
        $this->ips = config('accounts.ips');
        $date = Carbon::now()->format('Y-m-d');
        $num = 500;
        foreach ($this->ips as $name => $ip) {
            if(file_exists(database_path('child.db'))) {
                unlink(database_path('child.db'));
            }
            $dest_path = "/Users/Sahand/Documents/v2ray_backup/$name/$date";
            $d = file_get_contents($dest_path . '/x-ui.db');
            file_put_contents(database_path('child.db'), $d);
            $tmp = DB::connection('sqlite3')->table('inbounds')->pluck('port')->toArray();
            $this->all_ports = array_merge($this->all_ports, $tmp);
        }
        unlink(database_path('child.db'));
        $last_user_id = 0;
        $type = 'and';
        for ($c = 1; $c <= $num; $c++) {
            $remark = config('bot.common_remark').'.'.$last_user_id + $c;
            if ($type == 'and') {
                DB::connection('sqlite2')->table('inbounds')->insert($this->_getAndroidConfig($remark));
            } else {
                DB::connection('sqlite2')->table('inbounds')->insert($this->_getIosConfig($remark));
            }
        }
    }

    private function _getAndroidConfig($remark)
    {
        $uuid = (new Uuid())->uuid3();
        $port = Utils::portGenerator();
        while (in_array($port, $this->all_ports)) {
            $port = Utils::portGenerator();
        }
        $this->all_ports[] = $port;
        return [
            'user_id' => 1,
            'up' => 0,
            'down' => 0,
            'total' => 64424509440,
            'remark' => $remark,
            'enable' => 1,
            'expiry_time' => 0,
            'listen' => '',
            'port' => $port,
            'protocol' => 'vmess',
            'settings' => '{
  "clients": [
    {
      "id": ' . json_encode($uuid) . ',
      "alterId": 0
    }
  ],
  "disableInsecureEncryption": false
}',
            'stream_settings' => '{
  "network": "tcp",
  "security": "none",
  "tcpSettings": {
    "header": {
      "type": "http",
      "request": {
        "method": "GET",
        "path": [
          "/"
        ],
        "headers": {
          "Host": [
            "soft98.ir"
          ]
        }
      },
      "response": {
        "version": "1.1",
        "status": "200",
        "reason": "OK",
        "headers": {
          "Content-Type": [
            "application/octet-stream"
          ]
        }
      }
    }
  }
}',
            'tag' => 'inbound-14255',
            'sniffing' => '{
  "enabled": true,
  "destOverride": [
    "http",
    "tls"
  ]
}'
        ];
    }

    private function _getIosConfig($remark)
    {
        $uuid = (new Uuid())->uuid3();
        $port = Utils::portGenerator();
        while (in_array($port, $this->all_ports)) {
            $port = Utils::portGenerator();
        }
        $this->all_ports[] = $port;
        return [
            'user_id' => 1,
            'up' => 0,
            'down' => 0,
            'total' => 64424509440,
            'remark' => $remark,
            'enable' => 1,
            'expiry_time' => 0,
            'listen' => '',
            'port' => $port,
            'protocol' => 'vmess',
            'settings' => '{
  "clients": [
    {
      "id": ' . json_encode($uuid) . ',
      "alterId": 0
    }
  ],
  "disableInsecureEncryption": false
}',
            'stream_settings' => '{
  "network": "ws",
  "security": "none",
  "wsSettings": {
    "path": "/",
    "headers": {}
  }
}',
            'tag' => 'inbound-' . $port,
            'sniffing' => '{
  "enabled": true,
  "destOverride": [
    "http",
    "tls"
  ]
}'];
    }
}

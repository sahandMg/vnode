<?php

namespace App\Http\Controllers;

use App\Repositories\InboundsDB;
use App\Services\Utils;
use Faker\Core\Uuid;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class InboundController extends Controller
{
    protected $all_ports;

    public function getInbound()
    {
        $record = InboundsDB::getUserByRemark(\request()->get('remark'));
        if (is_null($record)) {
            $data = [
                'status' => Response::HTTP_NOT_FOUND,
                'data' => 'حساب یافت نشد'
            ];
            return response()->json($data, Response::HTTP_NOT_FOUND);
        }
        $data = [
            'status' => Response::HTTP_OK,
            'data' => $record
        ];
        return \response()->json($data, Response::HTTP_OK);
    }

    public function getAllInbound()
    {
        $record = InboundsDB::getAllInbounds();
        if (count($record) == 0) {
            $data = [
                'status' => Response::HTTP_NOT_FOUND,
                'data' => 'حساب یافت نشد'
            ];
            return response()->json($data, Response::HTTP_NOT_FOUND);
        }
        $data = [
            'status' => Response::HTTP_OK,
            'data' => $record
        ];
        return \response()->json($data, Response::HTTP_OK);
    }

    public function addVol()
    {
        $inbound = InboundsDB::getUserByRemark(\request()->get('remark'));
        if (is_null($inbound)) {
            $data = [
                'status' => Response::HTTP_NOT_FOUND,
                'data' => 'حساب یافت نشد'
            ];
            return response()->json($data, Response::HTTP_NOT_FOUND);
        }
        $vol = (\request()->get('vol') ?? 0) * pow(10, 9);
        $inbound = InboundsDB::updateUserVol($inbound->remark, $vol);
        info('increasing' . $inbound->remark . ' vol for ' . $vol . ' GB');
        $data = [
            'status' => Response::HTTP_OK,
            'data' => $inbound
        ];
        return response()->json($data, Response::HTTP_OK);
    }

    public function addExpiry()
    {
        $inbound = InboundsDB::getUserByRemark(\request()->get('remark'));
        if (is_null($inbound)) {
            $data = [
                'status' => Response::HTTP_NOT_FOUND,
                'data' => 'حساب یافت نشد'
            ];
            return response()->json($data, Response::HTTP_NOT_FOUND);
        }
        $inbound = InboundsDB::updateExpiry($inbound->remark);
        $data = [
            'status' => Response::HTTP_OK,
            'data' => $inbound
        ];
        return response()->json($data, Response::HTTP_OK);
    }

    public function addDays()
    {
        $inbound = InboundsDB::getUserByRemark(\request()->get('remark'));
        if (is_null($inbound)) {
            $data = [
                'status' => Response::HTTP_NOT_FOUND,
                'data' => 'حساب یافت نشد'
            ];
            return response()->json($data, Response::HTTP_NOT_FOUND);
        }
        $extra_days = \request()->get('days');
        $inbound = InboundsDB::addDays($inbound->remark, $extra_days);
        $data = [
            'status' => Response::HTTP_OK,
            'data' => $inbound
        ];
        return response()->json($data, Response::HTTP_OK);
    }

    public function createAccount()
    {
        $pass = request()->get('pass');
        $num = request()->get('num');
        if ($pass != env('PASS')) {
            return 404;
        }
        $this->all_ports = InboundsDB::getAllPorts();
        $last_config = DB::table('inbounds')->orderBy('id', 'desc')->first();
        preg_match_all('!\d+!', $last_config->remark ?? env('SERVER_ID') . '.0', $matches);
        $last_user_id = end($matches[0]);
        $type = isset($_GET['type']) ? $_GET['type'] : 'and';
        for ($c = 1; $c <= $num; $c++) {
            $remark = env('SERVER_ID') . '.' . $last_user_id + $c;
            if (isset($_GET['bulk'])) {
                $remark = config('bot.common_remark').'.'.$last_user_id + $c;
            }
            if ($type == 'and') {
                DB::table('inbounds')->insert($this->_getAndroidConfig($remark));
            } elseif ($type == 'grpc') {
                DB::table('inbounds')->insert($this->_getGrpcConfig($remark));
            } else {
                DB::table('inbounds')->insert($this->_getIosConfig($remark));
            }
//            $inbound = InboundsDB::reconnect($remark);
        }
        return 200;
    }

    public function reconnectInbound()
    {
        $inbound = InboundsDB::getUserByRemark(\request()->get('remark'));
        $inbound = InboundsDB::reconnect($inbound->remark);
        $data = [
            'status' => Response::HTTP_OK,
            'data' => $inbound
        ];
        return response()->json($data, Response::HTTP_OK);
    }

    public function disconnectInbound()
    {
        $inbound = InboundsDB::getUserByRemark(\request()->get('remark'));
        $inbound = InboundsDB::disconnect($inbound->remark);
        $data = [
            'status' => Response::HTTP_OK,
            'data' => $inbound
        ];
        return response()->json($data, Response::HTTP_OK);
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
            'tag' => 'inbound-' . $port,
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

    private function _getGrpcConfig($remark)
    {
        $uuid = (new Uuid())->uuid3();
        $port = Utils::portGenerator();
        while (in_array($port, $this->all_ports)) {
            $port = Utils::portGenerator();
        }
        $this->all_ports[] = $port;
        $txt = str_replace("\n", ' ', shell_exec('/usr/local/x-ui/bin/xray-linux-amd64 x25519'));
        $key_arr = array_filter(explode(' ', $txt));
        $prive = "$key_arr[2]";
        $pub = "$key_arr[5]";
        return [
            'user_id' => 1,
            'up' => 0,
            'down' => 0,
            'total' => 0,
            'remark' => $remark,
            'enable' => 1,
            'expiry_time' => 0,
            'listen' => '',
            'port' => $port,
            'protocol' => 'vless',
            'settings' => '{
  "clients": [
      {
      "id": ' . json_encode($uuid) . ',
      "email": "",
      "flow": "",
      "fingerprint": "chrome",
      "total": 0,
      "expiryTime": 0
    }
  ],
   "decryption": "none",
   "fallbacks": []
}',
            'stream_settings' => '{
  "network": "grpc",
  "security": "reality",
  "realitySettings": {
    "show": false,
    "dest": "www.speedtest.org:443",
    "xver": 0,
    "serverNames": [
      "www.speedtest.org"
    ],
    "privateKey": '.json_encode($prive).',
    "publicKey": '.json_encode($pub).',
    "minClient": "",
    "maxClient": "",
    "maxTimediff": 0,
    "shortIds": [
      "",
      "82",
      "45e7",
      "2fcd03",
      "fe0d354d"
    ]
  },
  "grpcSettings": {
    "serviceName": ""
  }
}',
            'tag' => 'inbound-' . $port,
            'sniffing' => '{
  "enabled": true,
  "destOverride": [
    "http",
    "tls",
    "quic"
  ]
}',
            'autoreset' => 0,
            'ip_alert' => 0,
            'ip_limit' => 0,
        ];
    }
}

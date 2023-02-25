<?php

namespace App\Http\Controllers;

use App\Repositories\InboundsDB;
use Faker\Core\Uuid;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class InboundController extends Controller
{
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
        InboundsDB::updateUserVol($inbound->remark, $vol);
        $data = [
            'status' => Response::HTTP_OK,
            'data' => 'Ok'
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
        InboundsDB::updateExpiry($inbound->remark);
        $data = [
            'status' => Response::HTTP_OK,
            'data' => 'Ok'
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
        $last_config = DB::table('inbounds')->orderBy('id', 'desc')->first();
        preg_match_all('!\d+!', $last_config->remark ?? env('SERVER_ID').'.0', $matches);
        $last_user_id = end($matches[0]);
        for ($c = 1; $c <= $num; $c++) {
            $uuid = (new Uuid())->uuid3();
            $port = rand(28000, 29999);
            DB::table('inbounds')->insert([
                'user_id' => 1,
                'up' => 0,
                'down' => 0,
                'total' => 64424509440,
                'remark' => env('SERVER_ID').'.'.$last_user_id + $c,
                'enable' => 1,
                'expiry_time' => 0,
                'listen' => '',
                'port' => $port,
                'protocol' => 'vmess',
                'settings' => '{
  "clients": [
    {
      "id": '.json_encode($uuid).',
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
            "nic.ir"
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
                'tag' => 'inbound-'.$port,
                'sniffing' => '{
  "enabled": true,
  "destOverride": [
    "http",
    "tls"
  ]
}'
            ]);
        }
        return 200;
    }
}

<?php

namespace App\Http\Controllers;

use App\Repositories\InboundsDB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
}

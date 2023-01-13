<?php

namespace App\Http\Controllers;

use App\Repositories\InboundsDB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InboundController extends Controller
{
    public function getInbound()
    {
        $record =  InboundsDB::getUserByRemark(\request()->get('remark'));
        if (is_null($record)) {
            return response()->json('Data Not Found', Response::HTTP_NOT_FOUND);
        }
        return \response()->json($record, Response::HTTP_OK);
    }
}

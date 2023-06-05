<?php

namespace App;

use App\Models\Log;
use App\Models\ParameterSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Mockery\Generator\Parameter;
use Throwable;

/**
 *  Ez Helper
 *
 * @version 1.0
 * @author Zian
 * @package App
 *
 */

class EzHelper
{
    public static function ApiResponse($status, $data = array(), $message_text = NULL)
    {
        $code = 422;

        if ($status == "error") {
            $message = ['code' => 422, 'status' => false, 'message' => 'Error', 'error_message' => $message_text];
        }

        if ($status == "success") {
            $code = 200;
            $message = ['code' => 200, 'status' => true, 'message' => 'Successfully', 'data' => $data];
        }

        $code;
        return response()->json($message, $code);
    }
    
}
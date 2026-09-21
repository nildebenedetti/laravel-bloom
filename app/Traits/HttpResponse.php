<?php

namespace App\Traits;

// settign this trait to structure the response for all http requests
// needs to be bond to AuthController

trait HttpResponse
{

    // success
    protected function success($data, $message = null, $code = 200) { // as props

        return response()->json([
            'status' => 'Request was successfull',
            'message' => '$message',
            'data' => $data
        ], $code );

    }

    // error
    protected function error($data, $message = null, $code ) {

        return response()->json([
            'status' => 'Error has occurred',
            'message' => $message,
            'data' => $data
        ], $code );
    }
}

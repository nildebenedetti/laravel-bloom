<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecordResource;
use App\Models\Record;
use Illuminate\Support\Facades\Auth;

class RecordController extends Controller
{
        public function index()
            {
            // only the records of the logged user
            return RecordResource::collection(
                Record::where('user_id', Auth::user()->id)->get()
            );
            }
}

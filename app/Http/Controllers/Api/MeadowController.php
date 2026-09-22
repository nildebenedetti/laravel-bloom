<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecordResource;
use App\Models\Record;

class MeadowController extends Controller
{
    public function index() {
        // all records with visibility set as public
        $records = Record::where('visibility', 'public')->get();

        // parse to json
        return RecordResource::collection($records);
    }
}

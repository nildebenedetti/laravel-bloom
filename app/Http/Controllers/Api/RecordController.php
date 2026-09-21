<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecordResource;
use App\Models\Record;
use App\Traits\HttpResponse;
use Illuminate\Support\Facades\Auth;

class RecordController extends Controller
{
    use HttpResponse;

        public function index()
            {
                // only the records of the logged user
                return RecordResource::collection(
                    Record::where('user_id', Auth::user()->id)->get()
                );
            }

        public function show(Record $record) {

            if (Auth::user()->id !== $record->user_id) {
                return $this->error('', 'you are not authorized to access this record', 403);
            }

            return new RecordResource($record); // parsin into json for response

        }
}

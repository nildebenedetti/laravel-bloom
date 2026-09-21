<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreRecordRequest;
use App\Http\Resources\RecordResource;
use App\Models\Record;
use App\Traits\HttpResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

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

        public function store(StoreRecordRequest $request) {
            // as we have an incoming reuqest we need to validate it
            $record = Record::create([
                'user_id' => Auth::id(),
                ...$request->validated(),
            ]); 

            // we create and parse into json
            return new RecordResource($record);

        }

        public function update(StoreRecordRequest $request, Record $record) { // as we use put we can use the same function

            // if not authorized as record owner
            if (Auth::user()->id !== $record->user_id) {
                return error('', 'you are not authorized to update this record', 403);
            }

            // otherwise update current record with request data
            $data = $request->all();

            $record->update($data);

            // parse into json and return
            return new RecordResource($record);
        }

        public function destroy(Record $record) {

             // if not authorized as record owner
            if (Auth::user()->id !== $record->user_id) {
                return error('', 'you are not authorized to delete this record', 403);
            }

            $record->delete();

            return response(null, 204);
        }
}

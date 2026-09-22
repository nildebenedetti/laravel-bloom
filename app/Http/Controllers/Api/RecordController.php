<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreRecordRequest;
use App\Http\Resources\RecordResource;
use App\Models\Record;
use App\Traits\HttpResponse;
use Illuminate\Support\Facades\Auth;

class RecordController extends Controller
{
    use HttpResponse;

        public function index()
            {
                $records = $request->user() // access to auth user instance
                ->records() // access hasMany relationship method defined in user model
                ->with(['category', 'tier', 'emotions']) // Eager loads related models data (prevents N+1)
                ->get(); // executes query
                // only the records of the logged user
                return RecordResource::collection($records);
            }

        public function show(Record $record) {

            if (Auth::user()->id !== $record->user_id) {
                return $this->error('', 'you are not authorized to access this record', 403);
            }

            return new RecordResource($record->load(['category', 'tier', 'user', 'emotions'])); // parsin into json for response

        }

        public function store(StoreRecordRequest $request) {
            // as we have an incoming reuqest we need to validate it
            $validated = $request->validated();

            // then create a new record with user_id as request sender's
            // and all validated field
            $record = Record::create([
                'user_id' => Auth::id(),
                ...$validated,
            ]); 

            // link emotions in pivot table (if present)
            if (!empty($validated['emotions'])) {
                $record->emotions()->attach($validated['emotions']);
            }

            // we create and parse into json
            // with eager loading for complete data of related resources
            return new RecordResource($record->load(['category', 'tier', 'user', 'emotions']));

        }

        public function update(StoreRecordRequest $request, Record $record) { // as we use put we can use the same function

            // if not authorized as record owner
            if (Auth::user()->id !== $record->user_id) {
                return error('', 'you are not authorized to update this record', 403);
            }

            // validate request data
            $validated = $request->validated();

            // if emotions are present
            // use isset to grant option of removing emotions in PUT 
            if (isset($validated['emotions'])) {
                $record->emotions()->sync($validated['emotions']); // synch for updating db
            }

            $record->update($validated);

            // parse into json and return
            return new RecordResource($record->load(['category', 'tier', 'user', 'emotions']));
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

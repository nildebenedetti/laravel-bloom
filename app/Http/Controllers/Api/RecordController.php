<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreRecordRequest;
use App\Http\Requests\Api\UpdateRecordRequest;
use App\Http\Resources\RecordResource;
use App\Models\Record;
use App\Traits\HttpResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RecordController extends Controller
{
    use HttpResponse;

        public function index(Request $request)
            {
                // extract sort order from query string
                // (es. ?order=asc o ?order=desc)
                // strtolower is native PHP function === JS .toLowerCase()
                // order is by def desc unless query strign sets asc
                $sortOrder = strtolower($request->input('order')) === 'asc' ? 'asc' : 'desc';

                $records = $request->user() // access to auth user instance
                ->records() // access hasMany relationship method defined in user model
                // search looks up for values in the column only if search query is present
                ->when($request->filled('search'), function ($query) use ($request) {
                    $query->where('title', 'LIKE', '%' . $request->input('search') . '%');
                })
                            ->when($request->filled('emotions'), function ($query) use ($request) {
                    $query->whereHas('emotions', function ($q) use ($request) {
                        $q->whereIn('emotions.id', (array) $request->emotions);
                    });
                })
                ->when($request->filled('category_id'), function ($query) use ($request) {
                    $query->where('category_id', $request->category_id);
                })
                ->when($request->filled('tier_id'), function ($query) use ($request) {
                    $query->where('tier_id', $request->tier_id);
                })
                ->with(['category', 'tier', 'emotions', 'user']) // Eager loads related models data (prevents N+1)
                ->orderBy('date', $sortOrder)
                ->paginate(20);
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

            // save image if present
            if ($request->hasFile('image')) {
                // create field to thew associative array (!!!)
        $validated['image_path'] = $request->file('image')->store('records'); // at the same time storing file and initializing var with path!!
            // now, I remove image from $validated associative array as we are going to store it 
            // - the image is already saved , no need to put it elsewhere
                unset($validated['image']);
            }


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

        public function update(UpdateRecordRequest $request, Record $record) { // as we use put we can use the same function

            // if not authorized as record owner
            if ($request->user()->id !== $record->user_id) {
                
                return $this->error('', 'you are not authorized to update this record', 403);
            }

            // validate request data
            $validated = $request->validated();

            //if img is present, clean old img, store new, rewrite img_path
            if ($request->hasFile('image')) {

                if ($record->image_path && Storage::disk('records')->exists($record->image_path)) {

                    Storage::disk('records')->delete($record->image_path);
                    
                }

                // save new image ad inmg path
                    $validated['image_path'] = $request->file('image')->store('records');
                    // remove image from associative array
                    unset($validated['image']);
            }

            // if emotions are present
            // use isset to grant option of removing emotions in PUT 
            if (isset($validated['emotions'])) {
                $record->emotions()->sync($validated['emotions']); // synch for updating db
            }

            // delete emotions array before updating
            unset($validated['emotions']);

            $record->update($validated);

            // parse into json and return
            return new RecordResource($record->load(['category', 'tier', 'user', 'emotions']));
        }

        public function destroy(Record $record) {

             // if not authorized as record owner
            if (Auth::user()->id !== $record->user_id) {
                // FIXME(td-02) CRITICAL: second missing `$this->` → HTTP 500 instead of 403.
                // Fix: return $this->error('', '…', 403);
                return $this->error('', 'you are not authorized to delete this record', 403);
            }

            // if present, delete the file from storage
            // FIXME(td-03a) CRITICAL: same missing disk as in update() — throws for any
            // record that actually has an image, so DELETE /api/records/{id} returns 500
            // instead of 204. Records with no image delete fine, which is why this is
            // invisible in a seed-data walkthrough.
            // Fix: Storage::exists($record->image_path) on the default disk.
            if ( $record->image_path && Storage::disk('records')->exists($record->image_path)) {
                Storage::disk('records')->delete($record->image_path);
            }

            $record->delete();

            return response(null, 204);
        }
}

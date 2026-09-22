<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecordResource;
use App\Models\Record;
use Illuminate\Http\Request;

class MeadowController extends Controller
{
    public function index(Request $request) { // dependency injection
        // all records with visibility set as public
        $records = Record::where('visibility', 'public')
        // Conditionally applies a SQL WHERE clause to filter results by 'category_id'.
        // Executes the callback only if 'category_id' is present and non-empty in the HTTP request.
        // Keeps query filtering optional without breaking the fluent Eloquent chain.
        ->when($request->filled('category_id'), function ($query) use ($request) {
            $query->where('category_id', $request->category_id);
        })
        // Layer 1 (Request): 'when' checks if the 'emotions' parameter is present before executing the outer closure.
        ->when($request->filled('emotions'), function ($query) use ($request) {
        // Layer 2 (Relation subquery): 'whereHas' opens an EXISTS subquery to scope the query down to the 'emotions' relationship.
            $query->whereHas('emotions', function ($q) use ($request) {
            // Layer 3 (Filter): 'whereIn' filters inside the relation context, matching 'emotions.id' against the array of IDs.
            $q->whereIn('emotions.id', (array) $request->emotions);
            });
        })
        ->orderBy('created_at', 'desc')
        ->paginate(15);

        // parse to json
        return RecordResource::collection($records);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Enums\RecordVisibility;
use App\Http\Controllers\Controller;
use App\Http\Resources\RecordResource;
use App\Models\Record;
use Illuminate\Http\Request;

class MeadowController extends Controller
{
    public function index(Request $request) { // dependency injection
        $records = Record::query()
        ->with(['category', 'tier', 'user', 'emotions'])
        ->where('visibility', RecordVisibility::PUBLIC)
        // Conditionally applies a SQL WHERE clause to filter results 
        ->when($request->filled('category_id'), function ($query) use ($request) {
            $query->where('category_id', $request->category_id);
        })
        ->when($request->filled('emotions'), function ($query) use ($request) {
            $query->whereHas('emotions', function ($q) use ($request) {
            // Layer 3 (Filter): 'whereIn' filters inside the relation context
            $q->whereIn('emotions.id', (array) $request->emotions);
            });
        })
        ->orderBy('date', 'desc')
        ->paginate(15);
        // parse to json
        return RecordResource::collection($records);
    }
}

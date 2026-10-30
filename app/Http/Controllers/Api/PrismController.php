<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecordResource;
use Illuminate\Http\Request;

class PrismController extends Controller
{
    public function index(Request $request) {
        // extraact sort order from query string
        // (es. ?order=asc o ?order=desc)

        $sortOrder = strtolower($request->input('order')) === 'asc' ? 'asc' : 'desc';
        
        $records = $request->user()->records()
        ->with(['category', 'tier', 'user', 'emotions'])
        ->whereNotNull('image_path')
        ->when($request->filled('emotions'), function ($query) use ($request) {
            $query->whereHas('emotions', function ($q) use ($request) {
                $q->whereIn('emotions.id', (array) $request->emotions);
            });
        })
        ->orderBy('date', $sortOrder)
        ->paginate(20);
        
        return RecordResource::collection($records);
        

    }
}

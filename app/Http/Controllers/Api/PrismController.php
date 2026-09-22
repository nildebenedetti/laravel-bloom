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
        // strtolower is native PHP function === JS .toLowerCase()
        // order is by def desc unless query strign sets asc
        $sortOrder = strtolower($request->input('order')) === 'asc' ? 'asc' : 'desc';
        
        $records = $request->user()->records()
        ->when($request->filled('emotions'), function ($query) use ($request) {
            $query->whereHas('emotions', function ($q) use ($request) {
                $q->whereIn('emotions.id', (array) $request->emotions);
            });
        })
        ->orderBy('date', $sortOrder)
        ->paginate(20);

        return RecordResource::collection($records->load('emotions'));
        

    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecordResource;
use Illuminate\Http\Request;

class PrismController extends Controller
{
    public function index(Request $request) {
        
        $records = $request->user()->records()
        ->when($request->filled('emotions'), function ($query) use ($request) {
            $query->whereHas('emotions', function ($q) use ($request) {
                $q->whereIn('emotions.id', (array) $request->emotions);
            });
        })
        ->orderBy('date', 'desc')
        ->paginate(20);

        return RecordResource::collection($records);
        

    }
}

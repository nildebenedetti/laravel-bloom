<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Record;
use Illuminate\Http\Request;

class RecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $records = Record::all();

        return view("records.index", compact("records"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("records.create");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $newRecord = new Record();

        $newRecord->title = $request['title'];
        $newRecord->description = $request['description'];
        $newRecord->date = $request['date'];
        $newRecord->visibility = $request['visibility'];

        $newRecord->save();

        return redirect()->route('records.show', $newRecord);

    }

    /**
     * Display the specified resource.
     */
    public function show(Record $record)
    {
        return view("records.show", compact("record"));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

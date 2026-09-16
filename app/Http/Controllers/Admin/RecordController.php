<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Record;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        $data = $request->all();

        $newRecord = new Record();

        $newRecord->title = $data['title'];
        $newRecord->description = $data['description'];
        $newRecord->date = $data['date'];
        $newRecord->visibility = $data['visibility'];


        if(array_key_exists("image", $data)) {
		
						// se c`è usiamo il metodo statico Storage::putFile()
						// con una variabile di appoggio per salvare il path
						// creato per raggiungere il file
						
						$image_path = Storage::putFile('records', $data['image']);
						// il primo parametro è il nome della cartella dove carichiamo le cose
						// se la cartella ancora non esiste, viene creata
						// il secondo parametro è il file, gli passa lárray che vedevamo prima 
						// con tutte le info di competenza
                        $newRecord->image_path = $image_path;
				}
        if(array_key_exists("image_alt", $data)) {
            $newRecord->image_alt = $data['image_alt'];
        }
		

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

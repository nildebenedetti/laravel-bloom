<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordVisibility;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Emotion;
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
        $categories = Category::all();
        $emotions = Emotion::all();

        return view("records.create", compact('categories', 'emotions'));
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
        $newRecord->category_id = $data['category_id'];
        $newRecord->date = $data['date'];
        $newRecord->visibility = $data['visibility'];


        if(array_key_exists("image_path", $data)) {
		
						// se c`è usiamo il metodo statico Storage::putFile()
						// con una variabile di appoggio per salvare il path
						// creato per raggiungere il file
						
						$image_path = Storage::putFile('records', $data['image_path']);
						// il primo parametro è il nome della cartella dove carichiamo le cose
						// se la cartella ancora non esiste, viene creata
						// il secondo parametro è il file, gli passa lárray che vedevamo prima 
						// con tutte le info di competenza
                        $newRecord->image_path = $image_path;
				}

        if(array_key_exists("image_alt", $data)) {
            $newRecord->image_alt = $data['image_alt'];
        }

        if($request->has('emotions')) {
            $newRecord->emotions()->attach($data['emotions']);
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
    public function edit(Record $record)
    {
        $categories = Category::all();
        $emotions = Emotion::all();


        return view("records.edit", compact("record", 'categories', 'emotions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Record $record)
    {
        $data = $request->all();

        /*
        * Safely fetches the 'visibility' input from the HTTP request and attempts to convert it 
        * into a RecordVisibility Enum instance using tryFrom() to avoid throwing a ValueError on invalid input.
        * If the input is invalid, missing, or null, the null coalescing operator (??) gracefully <3 falls back 
        * to RecordVisibility::PRIVATE, guaranteeing a strictly typed Enum instance is assigned to $record->visibility.
        */
        
        $data['visibility'] = $record->visibility = RecordVisibility::tryFrom($request->input('visibility')) ?? RecordVisibility::PRIVATE;

        if($request->hasFile('image_path')) {

            if($record->image_path) {
                // if an image is already present:
                // delete old
                Storage::delete($record->image_path);
            }

            // for all cases we need upload
            // write new file on disk and overriding the field with the string
            $data['image_path'] = Storage::putFile('records', $data['image_path']);
        } else {
        // avoid deletion of old image
        unset($data['image_path']);
        }

        $record->update($data);

                // shall the request have the array
        if ($request->has('emotions')) {
            // update the array overriding it with the new one in pivot table
            $record->emotions()->sync($data['emotions']);
        } else {
            // if nothing is selected in the checkbox, it means empty and we shall remove the array in db
            $record->emotions()->detach();
        }

        return redirect()->route('records.show', $record);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Record $record)
    {
        if($record->image) {
            
            Storage::delete($record->image_path);
        }
        

        $record->delete();

        return redirect()->route('records.index');
    }
}

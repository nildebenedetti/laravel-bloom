<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Emotion;
use Illuminate\Http\Request;

class EmotionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $emotions = Emotion::all();
        return view('emotions.index', compact('emotions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('emotions.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->all();

        $newEmotion = new Emotion();

        $newEmotion->name = $data['name'];
        $newEmotion->color = $data['color'];

        $newEmotion->save();

        return redirect()->route('emotions.show', $newEmotion);
    }

    /**
     * Display the specified resource.
     */
    public function show(Emotion $emotion)
    {
        return view('emotions.show', compact('emotion'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Emotion $emotion)
    {
        return view('emotions.edit', compact('emotion'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Emotion $emotion)
    {
        $data = $request->all();

        $emotion->update($data);
        
        return redirect()->route('emotions.show', $emotion);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Emotion $emotion)
    {
        $emotion->delete();

        return redirect()->route('emotions.index');
    }
}

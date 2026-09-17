<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tier;
use Illuminate\Http\Request;

class TierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tiers = Tier::all();
        return view('tiers.index', compact('tiers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('tiers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->all();

        $newTier = new Tier();

        $newTier->name = $data['name'];
        $newTier->description = $data['description'];

        $newTier->save();

        return redirect()->route('tiers.show', $newTier);
    }

    /**
     * Display the specified resource.
     */
    public function show(Tier $tier)
    {
        return view('tiers.show', compact('tier'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tier $tier)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Tier $tier)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tier $tier)
    {
        //
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\TypeRegime;
use Illuminate\Http\Request;

class TypeRegimeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return TypeRegime::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(TypeRegime $typeRegime)
    {
        return $typeRegime;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TypeRegime $typeRegime)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TypeRegime $typeRegime)
    {
        //
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\TypeLiability;
use Illuminate\Http\Request;

class TypeLiabilityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return TypeLiability::all();
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
    public function show(TypeLiability $typeLiability)
    {
        return $typeLiability;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TypeLiability $typeLiability)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TypeLiability $typeLiability)
    {
        //
    }
}

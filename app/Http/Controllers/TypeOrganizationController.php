<?php

namespace App\Http\Controllers;

use App\Models\TypeOrganization;
use Illuminate\Http\Request;

class TypeOrganizationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return TypeOrganization::all();
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
    public function show(TypeOrganization $typeOrganization)
    {
        return $typeOrganization;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TypeOrganization $typeOrganization)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TypeOrganization $typeOrganization)
    {
        //
    }
}

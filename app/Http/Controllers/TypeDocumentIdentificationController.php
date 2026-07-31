<?php

namespace App\Http\Controllers;

use App\Models\TypeDocumentIdentification;
use Illuminate\Http\Request;

class TypeDocumentIdentificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return TypeDocumentIdentification::all();
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
    public function show(TypeDocumentIdentification $typeDocumentIdentification)
    {
        return $typeDocumentIdentification;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TypeDocumentIdentification $typeDocumentIdentification)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TypeDocumentIdentification $typeDocumentIdentification)
    {
        //
    }
}

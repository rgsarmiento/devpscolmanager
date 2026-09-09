<?php

namespace App\Http\Controllers;

use App\Models\SmtpPreset;
use Illuminate\Http\Request;

class SmtpPresetController extends Controller
{
    public function index()
    {
        return response()->json(SmtpPreset::latest()->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'port' => 'required|string|max:255',
            'encryption' => 'nullable|string|max:255',
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'from_address' => 'required|string|max:255',
            'from_name' => 'required|string|max:255',
        ]);

        $preset = SmtpPreset::create($validated);

        return back()->with('flash.banner', 'Configuración SMTP guardada exitosamente.');
    }

    public function destroy(SmtpPreset $smtpPreset)
    {
        $smtpPreset->delete();
        return back()->with('flash.banner', 'Configuración SMTP eliminada.');
    }
}

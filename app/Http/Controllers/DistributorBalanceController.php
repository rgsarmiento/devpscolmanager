<?php

namespace App\Http\Controllers;

use App\Models\DistributorPendingBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DistributorBalanceController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'distributor_id' => 'required|exists:distributors,id',
            'amount' => 'required|numeric|min:0',
            'observation' => 'nullable|string',
            'image' => 'nullable|file|image|max:5120', // Max 5MB
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('balances', 'public');
        }

        DistributorPendingBalance::create([
            'distributor_id' => $validated['distributor_id'],
            'amount' => $validated['amount'],
            'observation' => $validated['observation'],
            'image_path' => $imagePath,
            'status' => 'pending',
        ]);

        return back()->with('flash.banner', 'Saldo pendiente registrado exitosamente.');
    }

    public function pay(DistributorPendingBalance $balance)
    {
        $balance->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return back()->with('flash.banner', 'Saldo pendiente marcado como pagado.');
    }
}

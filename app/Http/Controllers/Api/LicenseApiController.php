<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LicenseApiController extends Controller
{
    public function check(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string',
            'machine_id' => 'nullable|string', // Optional HWID binding for future use
        ]);

        $computer = \App\Models\Computer::with(['client', 'client.invoicingInfo'])
            ->where('license_key', $request->license_key)
            ->first();

        if (!$computer) {
            return response()->json([
                'status' => 'error',
                'message' => 'License key not found',
                'code' => 'LICENSE_NOT_FOUND',
            ], 404);
        }

        if (!$computer->is_active) {
             return response()->json([
                'status' => 'error',
                'message' => 'License is inactive',
                'code' => 'LICENSE_INACTIVE',
            ], 403);
        }

        if ($computer->expiration_date && now()->gt($computer->expiration_date)) {
             return response()->json([
                'status' => 'error',
                'message' => 'License has expired',
                 'expiration_date' => $computer->expiration_date->format('Y-m-d'),
                'code' => 'LICENSE_EXPIRED',
            ], 403);
        }

        // Return success with Client & Config Info
        return response()->json([
            'status' => 'success',
            'message' => 'License is valid',
            'data' => [
                'license_id' => $computer->id,
                'license_key' => $computer->license_key,
                'box_number' => $computer->box_number,
                'expiration_date' => $computer->expiration_date ? $computer->expiration_date->format('Y-m-d') : null,
                'client' => [
                    'name' => $computer->client->name,
                    'nit' => $computer->client->nit,
                    'email' => $computer->client->email,
                    'phone' => $computer->client->phone,
                    'address' => $computer->client->address,
                ],
                'invoicing' => [
                    'enabled' => !!$computer->client->invoicingInfo?->api_token,
                    'company_id' => $computer->client->invoicingInfo?->company_id,
                    'api_token' => $computer->client->invoicingInfo?->api_token,
                    // Resolution/Software info could be added here later
                ]
            ]
        ]);
    }
}

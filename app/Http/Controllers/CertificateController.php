<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function index()
    {
        $certificates = \App\Models\InvoicingInfo::with(['client', 'client.distributor'])
            ->whereNotNull('certificate_expiration_date')
            ->where('is_active', true)
            ->orderBy('certificate_expiration_date', 'asc')
            ->paginate(15);

        return \Inertia\Inertia::render('Certificates/Index', [
            'certificates' => $certificates
        ]);
    }
}

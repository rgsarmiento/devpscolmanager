<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Exception;

class BillingApiService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.billing_api.url', env('BILLING_API_URL', 'http://api.devpscol.com:81/api/ubl2.1'));
    }

    /**
     * Step 1: Configure Company (Get Token)
     * POST /config/{nit}/{dv}
     */
    public function configCompany(array $data, string $nit, string $dv)
    {
        // Force JSON to avoid HTML login redirects
        // Disable redirects to see the real status code
        $response = Http::acceptJson()
            ->withoutVerifying() // Correct method name
            ->withoutRedirecting()
            ->post("{$this->baseUrl}/config/{$nit}/{$dv}", $data);

        if ($response->failed()) {
            throw new Exception("Error configuring company: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Step 2: Configure Software
     * PUT /config/software
     */
    public function configSoftware(string $token, string $softwareId, string $pin)
    {
        $response = Http::withToken($token)
            ->put("{$this->baseUrl}/config/software", [
                'id' => $softwareId,
                'pin' => $pin,
            ]);

        if ($response->failed()) {
            throw new Exception("Error configuring software: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Get Numbering Range from DIAN
     * POST /numbering-range
     */
    public function getNumberingRange(string $token, string $nit, string $softwareId)
    {
        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/numbering-range", [
                'IDSoftware' => $softwareId,
            ]);

        if ($response->failed()) {
            throw new Exception("Error al consultar resoluciones en la DIAN: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Step 3: Configure Resolution
     * PUT /config/resolution
     */
    public function configResolution(string $token, array $resolutionData)
    {
        $response = Http::withToken($token)
            ->put("{$this->baseUrl}/config/resolution", $resolutionData);

        if ($response->failed()) {
            throw new Exception("Error configuring resolution: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Step 4: Configure Certificate
     * PUT /config/certificate
     */
    public function configCertificate(string $token, string $certificateBase64, string $password)
    {
        $response = Http::withToken($token)
            ->put("{$this->baseUrl}/config/certificate", [
                'certificate' => $certificateBase64,
                'password' => $password,
            ]);

        if ($response->failed()) {
            throw new Exception("Error configuring certificate: " . $response->body());
        }

        return $response->json();
    }
    /**
     * Set Environment Configuration
     * PUT /config/environment
     */
    public function configEnvironment(string $token, int $environmentId)
    {
        $response = Http::withToken($token)
            ->put("{$this->baseUrl}/config/environment", [
                'type_environment_id' => $environmentId,
                'payroll_type_environment_id' => $environmentId,
                'eqdocs_type_environment_id' => $environmentId,
            ]);

        if ($response->failed()) {
            throw new Exception("Error configuring environment: " . $response->body());
        }

        return $response->json();
    }
    
    /**
     * Get Plan Information (Folios consumption) from Direct DB
     */
    public function getPlanInfo(string $nit)
    {
        $cacheKey = "plan_info_db_" . $nit;

        return cache()->remember($cacheKey, now()->addMinutes(15), function () use ($nit) {
            $query = "
                SELECT 
                    companies.identification_number AS Nit,
                    companies.absolut_plan_documents,
                    companies.absolut_start_plan_date,
                    COUNT(documents.id) AS documentos_usados,
                    (companies.absolut_plan_documents - COUNT(documents.id)) AS documentos_restantes,
                    DATEDIFF(NOW(), companies.absolut_start_plan_date) AS dias_transcurridos,
                    IF(DATEDIFF(NOW(), companies.absolut_start_plan_date) > 0, 
                       ROUND(COUNT(documents.id) / DATEDIFF(NOW(), companies.absolut_start_plan_date), 2), 
                       0) AS promedio_folios_usados_por_dia,
                    IF(
                       ROUND(COUNT(documents.id) / DATEDIFF(NOW(), companies.absolut_start_plan_date), 2) > 0,
                       ROUND((companies.absolut_plan_documents - COUNT(documents.id)) / 
                             (ROUND(COUNT(documents.id) / DATEDIFF(NOW(), companies.absolut_start_plan_date), 2)), 2),
                       0) AS dias_estimados_para_terminar
                FROM 
                    companies
                LEFT JOIN 
                    documents 
                    ON companies.identification_number = documents.identification_number
                    AND documents.state_document_id = 1
                    AND documents.created_at BETWEEN companies.absolut_start_plan_date AND NOW()
                WHERE 
                    companies.identification_number = ?
                GROUP BY 
                    companies.id,
                    companies.identification_number,
                    companies.absolut_plan_documents,
                    companies.absolut_start_plan_date
            ";

            $result = DB::connection('api_external')->select($query, [$nit]);

            if (empty($result)) {
                return null;
            }

            $data = (array) $result[0];
            
            // Format for compatibility with existing code
            return [
                'success' => true,
                'absolut_plan_documents' => $data['absolut_plan_documents'],
                'docs_left_absolut' => $data['documentos_restantes'],
                'absolut_start_plan_date' => $data['absolut_start_plan_date'],
                // Extra stats
                'dias_transcurridos' => $data['dias_transcurridos'],
                'promedio_folios_usados_por_dia' => $data['promedio_folios_usados_por_dia'],
                'dias_estimados_para_terminar' => $data['dias_estimados_para_terminar'],
            ];
        });
    }

    /**
     * Sync all clients at once
     */
    public function syncAllClients()
    {
        $query = "
            SELECT 
                companies.identification_number AS Nit,
                companies.absolut_plan_documents,
                companies.absolut_start_plan_date,
                (companies.absolut_plan_documents - COUNT(documents.id)) AS documentos_restantes
            FROM 
                companies
            LEFT JOIN 
                documents 
                ON companies.identification_number = documents.identification_number
                AND documents.state_document_id = 1
                AND documents.created_at BETWEEN companies.absolut_start_plan_date AND NOW()
            WHERE 
                companies.absolut_plan_documents > 0 AND companies.state = 1
            GROUP BY 
                companies.id,
                companies.identification_number,
                companies.absolut_plan_documents,
                companies.absolut_start_plan_date
        ";

        return DB::connection('api_external')->select($query);
    }

    public function sendTestSet(string $token, string $testSetId, array $invoiceData)
    {
        $response = \Illuminate\Support\Facades\Http::withToken($token)
            ->acceptJson()
            ->withoutVerifying()
            ->withoutRedirecting()
            ->post("{$this->baseUrl}/invoice/{$testSetId}", $invoiceData);

        if (!$response->successful()) {
            throw new \Exception("Error al enviar TestSet: " . $response->body());
        }

        return $response->json();
    }

    public function checkZipStatus(string $token, string $zipKey)
    {
        $response = \Illuminate\Support\Facades\Http::withToken($token)
            ->acceptJson()
            ->withoutVerifying()
            ->withoutRedirecting()
            ->post("{$this->baseUrl}/status/zip/{$zipKey}");

        if (!$response->successful()) {
            throw new \Exception("Error al consultar ZipKey: " . $response->body());
        }

        return $response->json();
    }
}

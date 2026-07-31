const fs = require('fs');

let content = fs.readFileSync('app/Http/Controllers/InvoicingController.php', 'utf8');

const searchStr = `            $this->billingService->configResolution($token, $payload);
            
            return back()->with('flash.banner', 'Resolución Configurada Correctamente.');`;

const replaceStr = `            $this->billingService->configResolution($token, $payload);
            
            \App\Models\ClientResolution::updateOrCreate(
                [
                    'client_id' => $clientId,
                    'prefix' => $payload['prefix'],
                    'resolution' => $payload['resolution'] ?? null,
                ],
                [
                    'type_document_id' => $payload['type_document_id'],
                    'resolution_date' => $payload['resolution_date'] ?? null,
                    'technical_key' => $payload['technical_key'] ?? null,
                    'from' => $payload['from'],
                    'to' => $payload['to'],
                    'date_from' => $payload['date_from'] ?? null,
                    'date_to' => $payload['date_to'] ?? null,
                    'environment' => 'produccion'
                ]
            );

            return back()->with('flash.banner', 'Resolución Configurada Correctamente.');`;

content = content.replace(searchStr, replaceStr);

// Now I also need to pass resolutions to Show.vue
// Let's find ClientController@show
// Actually, let's just search for it manually in a moment.

fs.writeFileSync('app/Http/Controllers/InvoicingController.php', content);
console.log('InvoicingController updated');

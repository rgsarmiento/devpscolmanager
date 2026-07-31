const fs = require('fs');

let content = fs.readFileSync('app/Http/Controllers/InvoicingController.php', 'utf8');

const searchRegex = /\/\/ Validation based on type[\s\S]*?(?=try \{)/;
const replaceStr = `// Validation based on type
        $rules = [
            'type_document_id' => 'required|integer',
            'prefix' => 'required|string',
            'from' => 'required|integer',
            'to' => 'required|integer',
        ];

        if (in_array($type, [1, 3, 11])) {
            $rules['resolution'] = 'required|string';
            $rules['resolution_date'] = 'required|date';
            $rules['date_from'] = 'required|date';
            $rules['date_to'] = 'required|date';
        }
        
        if ($type == 1) {
            $rules['technical_key'] = 'nullable|string';
        }

        $validated = $request->validate($rules);

        // Build payload
        $payload = [
            'type_document_id' => $validated['type_document_id'],
            'prefix' => $validated['prefix'],
            'from' => $validated['from'],
            'to' => $validated['to'],
        ];

        if (in_array($type, [1, 3, 11])) {
            $payload['resolution'] = $validated['resolution'];
            $payload['resolution_date'] = $validated['resolution_date'];
            $payload['date_from'] = $validated['date_from'];
            $payload['date_to'] = $validated['date_to'];
            $payload['generated_to_date'] = 0;
            if ($type == 1) {
                $payload['technical_key'] = $request->input('technical_key') ?? '';
            }
        } elseif (in_array($type, [4, 5])) {
            $payload['resolution'] = '0000000000';
        }
        
        `;

content = content.replace(searchRegex, replaceStr);
fs.writeFileSync('app/Http/Controllers/InvoicingController.php', content);
console.log('InvoicingController payload rules updated');

const fs = require('fs');

let content = fs.readFileSync('resources/js/Pages/Clients/Show.vue', 'utf8');

// 1. Update the disabled state of the Technical Key input
// Old: :disabled="resolutionEnv === 'pruebas' || isDocumentTypeWithoutResolution" :required="resolutionEnv === 'produccion' && !isDocumentTypeWithoutResolution"
// New: :disabled="resolutionEnv === 'pruebas' || resolutionForm.type_document_id != 1" :required="resolutionEnv === 'produccion' && resolutionForm.type_document_id == 1"

const searchInput = `<InputLabel value="Clave Técnica (Technical Key)" />
                                                    <TextInput v-model="resolutionForm.technical_key" class="w-full text-sm font-mono text-xs" :disabled="resolutionEnv === 'pruebas' || isDocumentTypeWithoutResolution" :required="resolutionEnv === 'produccion' && !isDocumentTypeWithoutResolution" />`;

const replaceInput = `<InputLabel value="Clave Técnica (Technical Key)" />
                                                    <TextInput v-model="resolutionForm.technical_key" class="w-full text-sm font-mono text-xs" :disabled="resolutionEnv === 'pruebas' || resolutionForm.type_document_id != 1" :required="resolutionEnv === 'produccion' && resolutionForm.type_document_id == 1" />`;

content = content.replace(searchInput, replaceInput);

// 2. Clear technical_key when switching away from type 1
const searchWatch = `    if (prefixes[newVal]) {
        resolutionForm.prefix = prefixes[newVal];
        resolutionForm.resolution = '';
        resolutionForm.resolution_date = '';
        resolutionForm.date_from = '';
        resolutionForm.date_to = '';
        resolutionForm.technical_key = '';
    }
});`;

const replaceWatch = `    if (prefixes[newVal]) {
        resolutionForm.prefix = prefixes[newVal];
        resolutionForm.resolution = '';
        resolutionForm.resolution_date = '';
        resolutionForm.date_from = '';
        resolutionForm.date_to = '';
    }
    
    // Solo la Factura Electrónica (1) mantiene la clave técnica
    if (newVal != 1) {
        resolutionForm.technical_key = '';
    }
});`;

content = content.replace(searchWatch, replaceWatch);

fs.writeFileSync('resources/js/Pages/Clients/Show.vue', content);
console.log('UI updated to only allow technical key for type 1');

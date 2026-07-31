const fs = require('fs');

let content = fs.readFileSync('resources/js/Pages/Clients/Show.vue', 'utf8');

// We need to watch resolutionForm.type_document_id and adjust prefix and disabled fields.
const searchWatch = `const submitResolutionConfig = () => {`;
const replaceWatch = `
import { watch } from 'vue';

watch(() => resolutionForm.type_document_id, (newVal) => {
    // If it's not a standard invoice type, auto-suggest the prefix and clear fields
    const prefixes = {
        '4': 'NC',
        '5': 'ND',
        '9': 'NI',
        '10': 'NIA',
        '13': 'NADSE'
    };
    
    if (prefixes[newVal]) {
        resolutionForm.prefix = prefixes[newVal];
        resolutionForm.resolution = '';
        resolutionForm.resolution_date = '';
        resolutionForm.date_from = '';
        resolutionForm.date_to = '';
        resolutionForm.technical_key = '';
    }
});

const isDocumentTypeWithoutResolution = computed(() => {
    return ['4', '5', '9', '10', '13'].includes(String(resolutionForm.type_document_id));
});

const submitResolutionConfig = () => {`;

if (!content.includes('isDocumentTypeWithoutResolution')) {
    content = content.replace(searchWatch, replaceWatch);
}

// Ensure watch is imported (already handled if we just use watch from vue, but let's check)
const searchImport = `import { ref, computed } from 'vue';`;
const replaceImport = `import { ref, computed, watch } from 'vue';`;
if (content.includes(searchImport)) {
    content = content.replace(searchImport, replaceImport);
} else if (content.includes(`import { ref } from 'vue';`)) {
    content = content.replace(`import { ref } from 'vue';`, `import { ref, computed, watch } from 'vue';`);
}

// Now update the template to use `isDocumentTypeWithoutResolution` to disable the fields.
// The fields to disable are: resolution, resolution_date, date_from, date_to, technical_key.
// They currently have `:disabled="resolutionEnv === 'pruebas'"`
// I will replace that with `:disabled="resolutionEnv === 'pruebas' || isDocumentTypeWithoutResolution"`
// Wait, prefix is NOT disabled by `isDocumentTypeWithoutResolution`, just auto-filled.
// So only update the specific fields.

// Number of resolution
let s = `:disabled="resolutionEnv === 'pruebas'" :required="resolutionEnv === 'produccion'"`;

// We need to be careful with string replacements on the specific fields.
// Let's use regex to find each block.
function replaceInput(labelStr, fieldStr) {
    const regex = new RegExp(`(<InputLabel value="${labelStr}" \\/>\\s*<TextInput v-model="resolutionForm\\.${fieldStr}".*?):disabled="resolutionEnv === 'pruebas'" (:required="resolutionEnv === 'produccion'" \\/>)`, 'g');
    content = content.replace(regex, `$1:disabled="resolutionEnv === 'pruebas' || isDocumentTypeWithoutResolution" :required="resolutionEnv === 'produccion' && !isDocumentTypeWithoutResolution" />`);
}

replaceInput('Número de Resolución', 'resolution');
replaceInput('Fecha Resolución', 'resolution_date');
replaceInput('Válido Desde', 'date_from');
replaceInput('Válido Hasta', 'date_to');
replaceInput('Clave Técnica \\(Technical Key\\)', 'technical_key');

fs.writeFileSync('resources/js/Pages/Clients/Show.vue', content);
console.log('UI updated for Document Types without resolution');

const fs = require('fs');

let content = fs.readFileSync('resources/js/Pages/Clients/Show.vue', 'utf8');

// 1. Rename the dropdown text
const searchOption = `<option value="1">Factura Electrónica</option>`;
const replaceOption = `<option value="1">Factura Electrónica de Venta</option>`;
content = content.replace(searchOption, replaceOption);

// 2. Add auto-select type logic in selectResolution
const searchSelect = `const selectResolution = (res) => {
    resolutionForm.prefix = res.Prefix || '';`;
    
const replaceSelect = `const selectResolution = (res) => {
    // Determinar si tiene Clave Técnica real o es nula (objeto)
    const hasTechnicalKey = res.TechnicalKey && typeof res.TechnicalKey === 'string' && res.TechnicalKey.trim() !== '';
    resolutionForm.type_document_id = hasTechnicalKey ? '1' : '11';

    resolutionForm.prefix = res.Prefix || '';`;

content = content.replace(searchSelect, replaceSelect);

fs.writeFileSync('resources/js/Pages/Clients/Show.vue', content);
console.log('Update applied');

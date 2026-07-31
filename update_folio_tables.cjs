const fs = require('fs');

function updateFile(filePath, isGlobal) {
    let content = fs.readFileSync(filePath, 'utf8');

    // Add Swal import if not present
    if (!content.includes('import Swal')) {
        content = content.replace('<script setup>', `<script setup>\nimport Swal from 'sweetalert2';`);
    }

    // Add methods
    const methodsToAdd = `
const generateFolioMessage = (info) => {
    const clientName = info.client?.name || 'Cliente';
    const total = new Intl.NumberFormat('de-DE').format(info.folios_total);
    const startDate = info.plan_start_date ? info.plan_start_date.split('T')[0] : 'N/A';
    const dias = Math.round(info.dias_transcurridos || info.sql_dias || 0); 
    const promedio = info.promedio_folios_usados_por_dia || (Math.round((info.sql_promedio || 0) * 100) / 100);
    const remaining = new Intl.NumberFormat('de-DE').format(info.folios_remaining);
    
    return \`🚨 *¡ALERTA DE CONSUMO!* 🚨
Hola *\${clientName}*, le informamos que su plan de folios está próximo a agotarse. 📉

Inició su plan de *\${total}* folios el día *\${startDate}*.
Han transcurrido *\${dias}* días. A su ritmo de consumo de *\${promedio}* folios diarios, le restan únicamente *\${remaining}* folios disponibles.

⚠️ *Por favor renueve su plan pronto para evitar interrupciones en su servicio de facturación electrónica.* ⚠️\`;
}

const copyFolioMessage = (info) => {
    const msg = generateFolioMessage(info);
    navigator.clipboard.writeText(msg).then(() => {
        Swal.fire({
            icon: 'success',
            title: '¡Mensaje Copiado!',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    });
}

const sendFolioWhatsApp = (info) => {
    const msg = generateFolioMessage(info);
    const phone = info.client?.phone;
    if (!phone) {
        Swal.fire('Error', 'El cliente no tiene un teléfono registrado.', 'error');
        return;
    }
    const url = \`https://api.whatsapp.com/send?phone=57\${phone}&text=\${encodeURIComponent(msg)}\`;
    window.open(url, '_blank');
}
`;

    if (!content.includes('const generateFolioMessage')) {
        content = content.replace('</script>', methodsToAdd + '\n</script>');
    }

    // Replace the dias_transcurridos interpolation to Math.round
    if (!isGlobal) {
        content = content.replace(/\{\{\s*info\.dias_transcurridos\s*\}\}\s*días/g, '{{ Math.round(info.dias_transcurridos) }} días');
    }

    // Add Actions column header
    const thSearch = `<th class="px-4 py-3 text-right text-xs font-bold text-red-500 uppercase tracking-wider">Días Estimados</th>`;
    const thGlobalSearch = `<th class="px-4 py-3 text-right text-xs font-bold text-indigo-500 uppercase tracking-wider">Días Estimados</th>`;
    const thReplace = `${isGlobal ? thGlobalSearch : thSearch}
                                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>`;
    
    if (!content.includes('<th>Acciones</th>') && !content.includes('uppercase tracking-wider">Acciones</th>')) {
        content = content.replace(isGlobal ? thGlobalSearch : thSearch, thReplace);
    }

    // Add Actions column buttons
    const tdSearch = `<span class="text-[10px] text-gray-400 block -mt-1">Días</span>
                                        </td>`;
    const tdReplace = `<span class="text-[10px] text-gray-400 block -mt-1">Días</span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <div class="flex justify-center items-center gap-3">
                                                <button @click="copyFolioMessage(info)" class="text-gray-400 hover:text-indigo-600 transition" title="Copiar Mensaje">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                                </button>
                                                <button @click="sendFolioWhatsApp(info)" class="text-gray-400 hover:text-green-500 transition" title="Enviar por WhatsApp">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>`;
    
    if (!content.includes('copyFolioMessage(info)') && content.includes(tdSearch)) {
        content = content.replace(tdSearch, tdReplace);
    }
    
    // Adjust colspan for "No data" rows
    const noDataSearch = `colspan="8"`;
    const noDataSearchGlobal = `colspan="10"`;
    const noDataReplace = `colspan="9"`;
    const noDataReplaceGlobal = `colspan="11"`;

    if (isGlobal && content.includes(noDataSearchGlobal)) {
        content = content.replace(noDataSearchGlobal, noDataReplaceGlobal);
    } else if (!isGlobal && content.includes(noDataSearch)) {
        content = content.replace(noDataSearch, noDataReplace);
    }

    fs.writeFileSync(filePath, content);
}

updateFile('resources/js/Pages/Dashboard.vue', false);
updateFile('resources/js/Pages/FolioConsumption/Index.vue', true);

console.log('Successfully updated Vue components');

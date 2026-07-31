const fs = require('fs');

let content = fs.readFileSync('resources/js/Pages/Dashboard.vue', 'utf8');

// 1. We will extract the blocks and restructure them.
// Currently they are wrapped in:
// <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
//    <!-- Licencias por Expirar -->
//    ...
//    <!-- Servicios por Expirar -->
//    ...
//    <!-- Recent Clients List -->
//    ...
// </div>

const licensesRegex = /<!-- Expiring Licenses Table -->[\s\S]*?(?=<!-- Expiring Services List -->)/;
const servicesRegex = /<!-- Expiring Services List -->[\s\S]*?(?=<!-- Recent Clients List -->)/;
const recentClientsRegex = /<!-- Recent Clients List -->[\s\S]*?(?=<\/div>\s*<!-- Consumo Global de Folios)/;

const matchLicenses = content.match(licensesRegex);
const matchServices = content.match(servicesRegex);
const matchRecent = content.match(recentClientsRegex);

if (matchLicenses && matchServices && matchRecent) {
    let newLicensesHtml = matchLicenses[0];
    let newServicesHtml = matchServices[0];
    let newRecentHtml = matchRecent[0];

    // Remove the 'lg:col-span-1' classes
    newLicensesHtml = newLicensesHtml.replace('lg:col-span-1 bg-white', 'bg-white');
    newServicesHtml = newServicesHtml.replace('lg:col-span-1 bg-white', 'bg-white');
    newRecentHtml = newRecentHtml.replace('lg:col-span-1 bg-white', 'bg-white');

    // Add remaining days column and actions to Licenses
    if (!newLicensesHtml.includes('<th>Días Restantes</th>')) {
        newLicensesHtml = newLicensesHtml.replace(
            '<th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Computador</th>',
            '<th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Computador</th>\n                                        <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Días Restantes</th>'
        );
        newLicensesHtml = newLicensesHtml.replace(
            '<th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Valor</th>',
            '<th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Valor</th>\n                                        <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>'
        );
        newLicensesHtml = newLicensesHtml.replace(
            '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-bold">\n                                            ${{ new Intl.NumberFormat(\'de-DE\').format(license.price) }}\n                                        </td>',
            `<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-bold">
                                            \${{ new Intl.NumberFormat('de-DE').format(license.price) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <div class="flex justify-center items-center gap-3">
                                                <button @click="copyLicenseMessage(license)" class="text-gray-400 hover:text-indigo-600 transition" title="Copiar Mensaje">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                                </button>
                                                <button @click="sendLicenseWhatsApp(license)" class="text-gray-400 hover:text-green-500 transition" title="Enviar por WhatsApp">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>`
        );
        newLicensesHtml = newLicensesHtml.replace(
            '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">\n                                                {{ license.expiration_date }}\n                                            </span>',
            `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                {{ license.expiration_date ? license.expiration_date.split('T')[0] : 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span class="text-sm font-bold" :class="license.dias_restantes < 7 ? 'text-red-600' : 'text-amber-500'">
                                                {{ license.dias_restantes }} días
                                            </span>`
        );
        newLicensesHtml = newLicensesHtml.replace('colspan="4"', 'colspan="6"');
    }

    // Add remaining days column and actions to Services
    if (!newServicesHtml.includes('<th>Días Restantes</th>')) {
        newServicesHtml = newServicesHtml.replace(
            '<th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Servicio</th>',
            '<th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Servicio</th>\n                                        <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Días Restantes</th>'
        );
        newServicesHtml = newServicesHtml.replace(
            '<th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Valor</th>',
            '<th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Valor</th>\n                                        <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>'
        );
        newServicesHtml = newServicesHtml.replace(
            '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-bold">\n                                            ${{ new Intl.NumberFormat(\'de-DE\').format(service.price) }}\n                                        </td>',
            `<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-bold">
                                            \${{ new Intl.NumberFormat('de-DE').format(service.price) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <div class="flex justify-center items-center gap-3">
                                                <button @click="copyServiceMessage(service)" class="text-gray-400 hover:text-indigo-600 transition" title="Copiar Mensaje">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                                </button>
                                                <button @click="sendServiceWhatsApp(service)" class="text-gray-400 hover:text-green-500 transition" title="Enviar por WhatsApp">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>`
        );
        newServicesHtml = newServicesHtml.replace(
            '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">\n                                                {{ service.expiration_date }}\n                                            </span>',
            `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                {{ service.expiration_date ? service.expiration_date.split('T')[0] : 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span class="text-sm font-bold" :class="service.dias_restantes < 7 ? 'text-red-600' : 'text-amber-500'">
                                                {{ service.dias_restantes }} días
                                            </span>`
        );
        newServicesHtml = newServicesHtml.replace('colspan="4"', 'colspan="6"');
    }

    // Now reconstruct the template:
    // We remove the old grid and place them directly as top-level children of the max-w-7xl space-y-8 container.
    // The Folios table is also a top-level child, and the recent clients can go at the very end.
    
    // First remove the old grid entirely
    const fullGridRegex = /<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">[\s\S]*?(?=<!-- Consumo Global de Folios \(Top 5 Críticos\) -->)/;
    
    let layoutReplacement = `
                ${newLicensesHtml}
                
                ${newServicesHtml}
                
                `;

    content = content.replace(fullGridRegex, layoutReplacement);
    
    // Now place newRecentHtml at the end, after Consumo Global de Folios table
    // It should be inside the max-w-7xl container, which ends with:
    /*
                    </div>
                </div>
            </div>
    */
    // We'll append it right before the last closing div of max-w-7xl
    const endContainerRegex = /<\/div>\s*<\/div>\s*<\/div>\s*<\/AppLayout>/;
    content = content.replace(endContainerRegex, `    </div>\n                </div>\n            </div>\n\n            <!-- Recent Clients List -->\n            <div class="mt-8">\n                ${newRecentHtml}\n            </div>\n\n        </div>\n    </AppLayout>`);

    // Add JS methods for Licenses and Services
    const jsMethods = `
const generateLicenseMessage = (license) => {
    const clientName = license.client?.name || 'Cliente';
    const computerName = license.name || 'Licencia';
    const expDate = license.expiration_date ? license.expiration_date.split('T')[0] : 'N/A';
    const val = new Intl.NumberFormat('de-DE').format(license.price);
    
    return \`🚨 *¡RECORDATORIO DE VENCIMIENTO!* 🚨
Hola *\${clientName}*, le informamos que su licencia para *\${computerName}* está próxima a vencer.

🗓️ Fecha de vencimiento: *\${expDate}* (Faltan \${license.dias_restantes} días)
💰 Valor de renovación: *\$\${val}*

⚠️ *Por favor renueve pronto para evitar la suspensión del servicio.* ⚠️\`;
}

const copyLicenseMessage = (license) => {
    const msg = generateLicenseMessage(license);
    fallbackCopyTextToClipboard(msg);
}

const sendLicenseWhatsApp = (license) => {
    const msg = generateLicenseMessage(license);
    const phone = license.client?.phone;
    if (!phone) {
        Swal.fire('Error', 'El cliente no tiene un teléfono registrado.', 'error');
        return;
    }
    const url = \`https://api.whatsapp.com/send?phone=57\${phone}&text=\${encodeURIComponent(msg)}\`;
    window.open(url, '_blank');
}

const generateServiceMessage = (service) => {
    const clientName = service.client?.name || 'Cliente';
    const serviceName = service.name || 'Servicio';
    const expDate = service.expiration_date ? service.expiration_date.split('T')[0] : 'N/A';
    const val = new Intl.NumberFormat('de-DE').format(service.price);
    
    return \`🚨 *¡RECORDATORIO DE VENCIMIENTO!* 🚨
Hola *\${clientName}*, le informamos que su servicio de *\${serviceName}* está próximo a vencer.

🗓️ Fecha de vencimiento: *\${expDate}* (Faltan \${service.dias_restantes} días)
💰 Valor de renovación: *\$\${val}*

⚠️ *Por favor renueve pronto para evitar la interrupción de este servicio.* ⚠️\`;
}

const copyServiceMessage = (service) => {
    const msg = generateServiceMessage(service);
    fallbackCopyTextToClipboard(msg);
}

const sendServiceWhatsApp = (service) => {
    const msg = generateServiceMessage(service);
    const phone = service.client?.phone;
    if (!phone) {
        Swal.fire('Error', 'El cliente no tiene un teléfono registrado.', 'error');
        return;
    }
    const url = \`https://api.whatsapp.com/send?phone=57\${phone}&text=\${encodeURIComponent(msg)}\`;
    window.open(url, '_blank');
}
`;

    // Wait, I need fallbackCopyTextToClipboard to be available globally in the script.
    // Right now it's defined inside copyFolioMessage. I need to move it out.
    content = content.replace(/const fallbackCopyTextToClipboard = \(text\) => {[\s\S]*?}\n\n    if \(!navigator\.clipboard\)/, 
        `if (!navigator.clipboard)`);
        
    const globalFallback = `
const fallbackCopyTextToClipboard = (text) => {
    var textArea = document.createElement("textarea");
    textArea.value = text;
    
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";

    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        document.execCommand('copy');
        Swal.fire({
            icon: 'success',
            title: '¡Mensaje Copiado!',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    } catch (err) {
        Swal.fire('Error', 'No se pudo copiar el mensaje', 'error');
    }

    document.body.removeChild(textArea);
}
`;
    
    if (!content.includes('const fallbackCopyTextToClipboard = (text) => {')) {
        content = content.replace('const generateFolioMessage', globalFallback + '\nconst generateFolioMessage');
    }
    
    // Also, we must change fallbackCopyTextToClipboard inside copyFolioMessage
    content = content.replace('fallbackCopyTextToClipboard(msg);\n        return;\n    }\n    \n    navigator.clipboard.writeText(msg).then',
        'fallbackCopyTextToClipboard(msg);\n        return;\n    }\n    navigator.clipboard.writeText(msg).then'
    );
    
    if (!content.includes('generateServiceMessage')) {
        content = content.replace('</script>', jsMethods + '\n</script>');
    }

    fs.writeFileSync('resources/js/Pages/Dashboard.vue', content);
    console.log('Successfully updated Dashboard layout and logic');
}

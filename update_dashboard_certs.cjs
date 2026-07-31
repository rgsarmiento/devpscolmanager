const fs = require('fs');

let content = fs.readFileSync('resources/js/Pages/Dashboard.vue', 'utf8');

// Add expiringCertificates to props
content = content.replace(
    'criticalFolios: Array,',
    'criticalFolios: Array,\n    expiringCertificates: Array,'
);

// Add Certificate Methods
const certMethods = `
const generateCertMessage = (info) => {
    const clientName = info.client?.name || 'Cliente';
    const date = info.certificate_expiration_date ? info.certificate_expiration_date.split('T')[0] : 'N/A';
    const dias = info.dias_restantes_certificado;
    
    const isJuridica = info.client?.type_organization_id === "1" || info.client?.type_organization_id === 1;
    const requirement = isJuridica 
        ? "Certificado de existencia y representación legal (Cámara de Comercio) emitido con menos de 30 días de vigencia" 
        : "Copia de la cédula";

    return \`🚨 *¡RECORDATORIO DE VENCIMIENTO DE CERTIFICADO DIGITAL!* 🚨
Hola *\${clientName}*, le informamos que su Certificado de Firma Digital está próximo a vencer.

🗓️ Fecha de vencimiento: *\${date}* (Faltan \${dias} días)

⚠️ *Por favor recuerde que para renovar su certificado se necesita:*
👉 \${requirement}

Agradecemos realizar el trámite con prontitud para evitar interrupciones en su facturación electrónica.\`;
}

const copyCertMessage = (info) => {
    const msg = generateCertMessage(info);
    if (!navigator.clipboard) {
        fallbackCopyTextToClipboard(msg);
        return;
    }
    navigator.clipboard.writeText(msg).then(() => {
        Swal.fire({
            icon: 'success',
            title: '¡Mensaje Copiado!',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    }).catch(err => {
        fallbackCopyTextToClipboard(msg);
    });
}

const sendCertWhatsApp = (info) => {
    const msg = generateCertMessage(info);
    const phone = info.client?.phone;
    if (!phone) {
        Swal.fire('Error', 'El cliente no tiene un teléfono registrado.', 'error');
        return;
    }
    const url = \`https://api.whatsapp.com/send?phone=57\${phone}&text=\${encodeURIComponent(msg)}\`;
    window.open(url, '_blank');
}
`;

content = content.replace('</script>', certMethods + '\n</script>');

// Insert Cert Table before Recent Clients List
const certTableHTML = `
            <!-- Certificados Próximos a Vencer (Top 5) -->
            <div class="mt-8 bg-white overflow-hidden shadow-xl sm:rounded-2xl border border-blue-100">
                <div class="px-6 py-5 bg-gradient-to-r from-blue-50 to-white border-b border-blue-100 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-500 rounded-lg p-2 text-white shadow-md shadow-blue-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-800 tracking-tight">Certificados Próximos a Vencer</h3>
                            <p class="text-xs text-blue-500 font-medium">Top 5 clientes más cercanos a expiración de firma</p>
                        </div>
                    </div>
                    <Link :href="route('certificates.index')" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Ver Todos
                    </Link>
                </div>
                
                <div class="p-6 bg-gray-50">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Cliente (Datos y Contacto)</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Vencimiento</th>
                                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Días Restantes</th>
                                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="info in expiringCertificates" :key="info.id" class="hover:bg-blue-50/30 transition">
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="font-bold text-slate-900">
                                                <Link :href="route('clients.show', info.client.id)" class="hover:text-indigo-600 hover:underline">
                                                    {{ info.client?.name }}
                                                </Link>
                                            </div>
                                            <div class="text-[10px] text-gray-600 font-mono mt-0.5">
                                                NIT: {{ info.client?.nit }} | {{ info.client?.distributor ? info.client.distributor.name : 'Directo' }}
                                            </div>
                                            <div class="flex items-center gap-2 mt-1">
                                                <div class="text-[10px] text-indigo-600 font-medium">{{ info.client?.email }}</div>
                                                <div class="text-[10px] text-gray-500">{{ info.client?.phone }}</div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                {{ info.certificate_expiration_date ? info.certificate_expiration_date.split('T')[0] : 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-center">
                                            <span class="text-sm font-black" :class="info.dias_restantes_certificado < 30 ? 'text-red-600' : 'text-amber-500'">
                                                {{ info.dias_restantes_certificado }} días
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-center">
                                            <div class="flex justify-center items-center gap-3">
                                                <button @click="copyCertMessage(info)" class="text-gray-400 hover:text-indigo-600 transition" title="Copiar Mensaje">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                                </button>
                                                <button @click="sendCertWhatsApp(info)" class="text-gray-400 hover:text-green-500 transition" title="Enviar por WhatsApp">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="!expiringCertificates || expiringCertificates.length === 0">
                                        <td colspan="9" class="px-4 py-8 text-center text-gray-400 font-medium text-sm">
                                            No hay certificados registrados próximamente a vencer.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

`;
content = content.replace('<!-- Recent Clients List -->', certTableHTML + '<!-- Recent Clients List -->');

fs.writeFileSync('resources/js/Pages/Dashboard.vue', content);
console.log('Successfully updated Dashboard with cert table');

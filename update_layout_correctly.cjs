const fs = require('fs');

let content = fs.readFileSync('resources/js/Pages/Dashboard.vue', 'utf8');

// 1. Remove the grid wrapper.
// The grid is `<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">`
// We want to remove this opening tag and its corresponding closing tag before the Folio table.
// However, the easiest way is to just replace the opening tag with a div that stacks them vertically.
content = content.replace('<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">', '<div class="space-y-8 mt-8">');

// 2. Remove the 'lg:col-span-2' from Expiring Licenses Table
content = content.replace('<div class="lg:col-span-2 bg-white overflow-hidden shadow-xl sm:rounded-lg">', '<div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">');

// 3. Update Columns for Licenses
// Replace <th class="...">PC / Caja</th> with PC / Caja + Días Restantes
content = content.replace(
    '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PC / Caja</th>',
    '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PC / Caja</th>\n                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Días Restantes</th>'
);

// Replace Accion header with Accion + Acciones (or rename)
content = content.replace(
    '<th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acción</th>',
    '<th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acción</th>\n                                        <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Contacto</th>'
);

// Update columns in loop for Licenses
content = content.replace(
    /<td class="px-6 py-4 whitespace-nowrap">\s*<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"\s*:class="new Date\(license\.expiration_date\) < new Date\(\) \? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'">\s*\{\{ new Date\(license\.expiration_date\)\.toLocaleDateString\(\) \}\}\s*<\/span>\s*<\/td>/,
    `<td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                                :class="new Date(license.expiration_date) < new Date() ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'">
                                                {{ license.expiration_date ? license.expiration_date.split('T')[0] : 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span class="text-sm font-bold" :class="license.dias_restantes < 7 ? 'text-red-600' : 'text-amber-500'">
                                                {{ license.dias_restantes }} días
                                            </span>
                                        </td>`
);

content = content.replace(
    '<td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">\n                                            <Link :href="route(\'clients.show\', license.client.id)" class="text-indigo-600 hover:text-indigo-900">Gestionar</Link>\n                                        </td>',
    `<td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <Link :href="route('clients.show', license.client.id)" class="text-indigo-600 hover:text-indigo-900">Gestionar</Link>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <div class="flex justify-center items-center gap-3">
                                                <button @click="copyLicenseMessage(license)" class="text-gray-400 hover:text-indigo-600 transition" title="Copiar Mensaje">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                                </button>
                                                <button @click="sendLicenseWhatsApp(license)" class="text-gray-400 hover:text-green-500 transition" title="Enviar por WhatsApp">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                                </button>
                                            </div>
                                        </td>`
);

content = content.replace('colspan="4" class="px-6 py-4 text-center text-sm text-gray-500"', 'colspan="6" class="px-6 py-4 text-center text-sm text-gray-500"');

// 4. Update Columns for Services
// It has columns: Cliente, Servicio, Vencimiento, Valor
content = content.replace(
    '<th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Servicio</th>',
    '<th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Servicio</th>\n                                        <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Días Restantes</th>'
);

content = content.replace(
    '<th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Valor</th>',
    '<th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Valor</th>\n                                        <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Contacto</th>'
);

content = content.replace(
    /<td class="px-6 py-4 whitespace-nowrap">\s*<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">\s*\{\{ service\.expiration_date \}\}\s*<\/span>\s*<\/td>/,
    `<td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                {{ service.expiration_date ? service.expiration_date.split('T')[0] : 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span class="text-sm font-bold" :class="service.dias_restantes < 7 ? 'text-red-600' : 'text-amber-500'">
                                                {{ service.dias_restantes }} días
                                            </span>
                                        </td>`
);

content = content.replace(
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
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                                </button>
                                            </div>
                                        </td>`
);

content = content.replace('colspan="4" class="px-6 py-4 text-center text-sm text-gray-500"\n                                            >No hay servicios', 'colspan="6" class="px-6 py-4 text-center text-sm text-gray-500"\n                                            >No hay servicios');
content = content.replace('colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">\n                                            No hay servicios', 'colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">\n                                            No hay servicios');


fs.writeFileSync('resources/js/Pages/Dashboard.vue', content);
console.log('Successfully updated Dashboard layout iteratively');

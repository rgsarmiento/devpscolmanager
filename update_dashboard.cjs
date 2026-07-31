const fs = require('fs');

let content = fs.readFileSync('resources/js/Pages/Dashboard.vue', 'utf8');

const search = `import AppLayout from '@/Layouts/AppLayout.vue';`;
const replace = `import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';`;

if (!content.includes('import { Link } from')) {
    content = content.replace(search, replace);
}

// Ensure criticalFolios is received
const searchProps = `    expiringServices: Array,`;
const replaceProps = `    expiringServices: Array,
    criticalFolios: Array,`;
if (content.includes(searchProps) && !content.includes('criticalFolios: Array')) {
    content = content.replace(searchProps, replaceProps);
}

// Add the table at the bottom of the dashboard before the final closing div
const searchEnd = `        </div>
    </AppLayout>
</template>`;

const replaceEnd = `
            <!-- Consumo Global de Folios (Top 5 Críticos) -->
            <div class="mt-8 bg-white overflow-hidden shadow-xl sm:rounded-2xl border border-red-100">
                <div class="px-6 py-5 bg-gradient-to-r from-red-50 to-white border-b border-red-100 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="bg-red-500 rounded-lg p-2 text-white shadow-md shadow-red-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-800 tracking-tight">Consumo Crítico de Folios</h3>
                            <p class="text-xs text-red-500 font-medium">Clientes más próximos a terminar su plan</p>
                        </div>
                    </div>
                    <Link :href="route('folio-consumption.index')" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Ver Todos
                    </Link>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Cliente</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">NIT</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Contacto</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Inicio Plan</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Contratado</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Restantes</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Promedio Diario</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-red-500 uppercase tracking-wider">Días Estimados</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="info in criticalFolios" :key="info.id" class="hover:bg-red-50/30 transition">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-bold text-slate-900">{{ info.client?.name }}</div>
                                    <div class="text-[10px] text-indigo-600 font-bold mt-0.5">
                                        {{ info.client?.distributor ? info.client.distributor.name : 'Directo' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600 font-mono text-xs">
                                    {{ info.client?.nit }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-xs text-gray-900">{{ info.client?.email }}</div>
                                    <div class="text-[10px] text-gray-500">{{ info.client?.phone }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-500 text-xs">
                                    {{ info.plan_start_date ? info.plan_start_date.split('T')[0] : 'N/A' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right font-medium text-slate-700">
                                    {{ new Intl.NumberFormat('de-DE').format(info.folios_total) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800">
                                        {{ new Intl.NumberFormat('de-DE').format(info.folios_remaining) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-xs text-gray-600">
                                    {{ info.promedio_folios_usados_por_dia }} / día
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <span class="text-lg font-black" :class="info.dias_estimados_para_terminar < 30 ? 'text-red-600' : 'text-amber-600'">
                                        {{ info.dias_estimados_para_terminar < 999999 ? info.dias_estimados_para_terminar : '∞' }}
                                    </span>
                                    <span class="text-[10px] text-gray-400 block -mt-1">Días</span>
                                </td>
                            </tr>
                            <tr v-if="!criticalFolios || criticalFolios.length === 0">
                                <td colspan="8" class="px-4 py-8 text-center text-gray-400 font-medium text-sm">
                                    No hay datos de consumo registrados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AppLayout>
</template>`;

content = content.replace(searchEnd, replaceEnd);

fs.writeFileSync('resources/js/Pages/Dashboard.vue', content);
console.log('Added table to dashboard');

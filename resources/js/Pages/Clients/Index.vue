<script setup>
import { ref, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import Pagination from '@/Components/Pagination.vue';
import pickBy from 'lodash/pickBy';
import throttle from 'lodash/throttle';

const props = defineProps({
    clients: Object,
    filters: Object,
});

const form = ref({
    search: props.filters.search || '',
    sort: props.filters.sort || 'id',
    direction: props.filters.direction || 'desc',
    per_page: props.filters.per_page || 10,
});

watch(form, throttle(() => {
    router.get(route('clients.index'), pickBy(form.value), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 150), { deep: true });

const sort = (field) => {
    if (form.value.sort === field) {
        form.value.direction = form.value.direction === 'asc' ? 'desc' : 'asc';
    } else {
        form.value.sort = field;
        form.value.direction = 'asc';
    }
};

const formatNumber = (num, decimals = 0) => {
    if (num == null) return '0';
    return new Intl.NumberFormat('de-DE', { maximumFractionDigits: decimals }).format(num);
};

const syncAllLoading = ref(false);
const syncAll = () => {
    if (confirm('¿Estás seguro de sincronizar el consumo de todos los clientes?')) {
        router.post(route('clients.sync-all'), {}, {
            preserveScroll: true,
            onStart: () => syncAllLoading.value = true,
            onFinish: () => syncAllLoading.value = false,
        });
    }
};
</script>

<template>
    <AppLayout title="Listado de Empresas">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    Gestión de Empresas
                </h2>
                <div class="flex gap-2">
                    <template v-if="$page.props.auth.user.role === 'admin'">
                        <Link :href="route('computers.index')" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                            Ver Licencias
                        </Link>
                    </template>
                    <button @click="syncAll" :disabled="syncAllLoading" class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 focus:bg-emerald-700 active:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-lg shadow-emerald-200">
                        <svg class="w-4 h-4 mr-2" :class="{ 'animate-spin': syncAllLoading }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Sincronizar Todo
                    </button>
                    <template v-if="$page.props.auth.user.role === 'admin'">
                        <Link :href="route('clients.create')" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-lg shadow-indigo-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Nueva Empresa
                        </Link>
                    </template>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Filters Section -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 mb-8 border-b-4 border-indigo-500">
                    <div class="flex flex-col md:flex-row md:items-end gap-4">
                        <div class="flex-grow">
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Buscar Empresa</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </span>
                                <TextInput 
                                    v-model="form.search" 
                                    type="text" 
                                    class="w-full pl-10 border-gray-200 focus:ring-indigo-500" 
                                    placeholder="Nombre, NIT o Email..." 
                                />
                            </div>
                        </div>
                        <div class="w-full md:w-32">
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Por Página</label>
                            <select v-model="form.per_page" class="w-full border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Table Container -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg border border-gray-100">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th @click="sort('id')" class="px-6 py-4 text-left text-xs font-bold cursor-pointer hover:text-indigo-600 transition flex items-center group">
                                        ID
                                        <svg v-if="form.sort === 'id'" class="w-4 h-4 ml-1 transition" :class="form.direction === 'asc' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </th>
                                    <th @click="sort('name')" class="px-6 py-4 text-left text-xs font-bold cursor-pointer hover:text-indigo-600 transition">
                                        <div class="flex items-center">
                                            Razón Social / NIT
                                            <svg v-if="form.sort === 'name'" class="w-4 h-4 ml-1 transition" :class="form.direction === 'asc' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        </div>
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-bold">Consumo Folios</th>
                                    <th @click="sort('estimated_days')" class="px-6 py-4 text-left text-xs font-bold cursor-pointer hover:text-indigo-600 transition">
                                        <div class="flex items-center">
                                            Información Plan
                                            <svg v-if="form.sort === 'estimated_days'" class="w-4 h-4 ml-1 transition" :class="form.direction === 'asc' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        </div>
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-bold">Contacto</th>
                                    <th class="px-6 py-4 text-right text-xs font-bold">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 divide-dashed">
                                <tr v-for="client in clients.data" :key="client.id" class="hover:bg-indigo-50/30 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">{{ client.id }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900">{{ client.name }}</div>
                                        <div class="text-xs text-gray-500 mb-1">{{ client.nit }}<span v-if="client.dv">-{{ client.dv }}</span></div>
                                        <div class="mt-1">
                                            <span v-if="client.distributor" class="px-2 py-0.5 bg-indigo-100 text-indigo-800 rounded text-[10px] font-semibold border border-indigo-200 uppercase tracking-tighter">
                                                Dist: {{ client.distributor.name }}
                                            </span>
                                            <span v-else class="px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded text-[10px] font-semibold border border-emerald-200 uppercase tracking-tighter">
                                                Directo
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3 text-sm">
                                        <div v-if="client.invoicing_info && client.invoicing_info.folios_total > 0" class="flex items-center gap-3">
                                            <!-- Consumo con barra -->
                                            <div class="flex-1">
                                                <template v-if="client.invoicing_info.folios_total >= 1000000">
                                                    <div class="text-lg font-bold text-indigo-600">
                                                        {{ formatNumber(client.invoicing_info.folios_total - client.invoicing_info.folios_remaining) }}
                                                        <span class="text-[10px] text-indigo-400 font-bold uppercase tracking-widest ml-0.5">Usados</span>
                                                    </div>
                                                    <div class="mt-1">
                                                        <span class="bg-indigo-100 text-indigo-800 text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wider">Plan Ilimitado</span>
                                                    </div>
                                                </template>
                                                <template v-else>
                                                    <div class="text-lg font-bold text-indigo-600">
                                                        {{ formatNumber(client.invoicing_info.folios_remaining) }} 
                                                        <span class="text-[10px] text-indigo-400 font-bold uppercase tracking-widest ml-0.5">Disp</span>
                                                    </div>
                                                    <div class="text-xs text-gray-500">{{ formatNumber(client.invoicing_info.folios_total - client.invoicing_info.folios_remaining) }} / {{ formatNumber(client.invoicing_info.folios_total) }}</div>
                                                    <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1">
                                                        <div class="bg-indigo-500 h-1.5 rounded-full transition-all" :style="{ width: Math.min(100, Math.max(0, (1 - (client.invoicing_info.folios_remaining / client.invoicing_info.folios_total)) * 100)) + '%' }"></div>
                                                    </div>
                                                </template>
                                            </div>
                                            <!-- Botón Sincronizar -->
                                            <button @click="router.post(route('clients.refresh-plan', client.id), {}, { preserveScroll: true, onStart: () => client.syncing = true, onFinish: () => client.syncing = false })" 
                                                    class="px-2 py-1 text-xs text-indigo-600 bg-indigo-50 border border-indigo-200 rounded hover:bg-indigo-600 hover:text-white transition-all disabled:opacity-50"
                                                    :disabled="client.syncing"
                                                    title="Sincronizar">
                                                <svg class="w-3.5 h-3.5" :class="{ 'animate-spin': client.syncing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                            </button>
                                        </div>
                                        <span v-else class="text-xs text-gray-400 italic">Sin datos</span>
                                    </td>
                                    <td class="px-6 py-3">
                                        <div v-if="client.invoicing_info && client.invoicing_info.plan_start_date" class="space-y-1">
                                            <!-- Proyección Restante (destacada) -->
                                            <div v-if="client.invoicing_info.folios_total >= 1000000" class="text-lg font-bold text-indigo-600">
                                                <span class="text-sm">Ilimitado</span>
                                            </div>
                                            <div v-else class="text-lg font-bold" :class="client.invoicing_info.dias_estimados_para_terminar < 30 ? 'text-red-600' : 'text-indigo-600'">
                                                {{ formatNumber(client.invoicing_info.dias_estimados_para_terminar) }} días
                                            </div>
                                            <!-- Días Transcurridos y Promedio -->
                                            <div class="flex gap-3 text-xs text-gray-600">
                                                <span><strong>{{ formatNumber(client.invoicing_info.dias_transcurridos) }}</strong> días transc.</span>
                                                <span><strong>{{ formatNumber(client.invoicing_info.promedio_folios_usados_por_dia, 2) }}</strong> f/día</span>
                                            </div>
                                        </div>
                                        <span v-else class="text-xs text-gray-400 italic">-</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-700">{{ client.email }}</div>
                                        <div class="text-xs text-gray-400">{{ client.phone }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <Link :href="route('clients.show', client.id)" class="inline-flex items-center px-3 py-1 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                                            Gestionar
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="clients.data.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-12 h-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 9.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            <p class="text-gray-500">No se encontraron empresas con esos criterios.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination Section -->
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                        <p class="text-sm text-gray-600">
                            Mostrando <span class="font-bold">{{ clients.from }}</span> a <span class="font-bold">{{ clients.to }}</span> de <span class="font-bold">{{ clients.total }}</span> resultados
                        </p>
                        <Pagination :links="clients.links" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

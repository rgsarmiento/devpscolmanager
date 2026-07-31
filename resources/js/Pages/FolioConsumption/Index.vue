<script setup>
import Swal from 'sweetalert2';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    consumptions: Object
});

const generateFolioMessage = (info) => {
    const clientName = info.client?.name || 'Cliente';
    const total = new Intl.NumberFormat('de-DE').format(info.folios_total);
    const startDate = info.plan_start_date ? info.plan_start_date.split('T')[0] : 'N/A';
    const dias = Math.round(info.dias_transcurridos || info.sql_dias || 0); 
    const promedio = info.promedio_folios_usados_por_dia || (Math.round((info.sql_promedio || 0) * 100) / 100);
    const remaining = new Intl.NumberFormat('de-DE').format(info.folios_remaining);
    
    let diasEstimados = info.dias_estimados_para_terminar || info.sql_estimados || 0;
    diasEstimados = Math.round(diasEstimados);
    const estimacion = diasEstimados < 999999 ? `*¡Se estima que su plan terminará en ${diasEstimados} días!*` : '';
    
    return `🚨 *¡ALERTA DE CONSUMO!* 🚨
Hola *${clientName}*, le informamos que su plan de folios está próximo a agotarse. ${estimacion} 📉

Inició su plan de *${total}* folios el día *${startDate}*.
Han transcurrido *${dias}* días. A su ritmo de consumo de *${promedio}* folios diarios, le restan únicamente *${remaining}* folios disponibles.

⚠️ *Por favor renueve su plan pronto para evitar interrupciones en su servicio de facturación electrónica.* ⚠️`;
}

const copyFolioMessage = (info) => {
    const msg = generateFolioMessage(info);
    
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
        // Fallback in case clipboard API fails (e.g. not HTTPS)
        fallbackCopyTextToClipboard(msg);
    });
}

const sendWhatsApp = (info) => {
    const msg = generateFolioMessage(info);
    const wContact = info.client?.whatsapp_contact;
    const url = wContact 
        ? `https://api.whatsapp.com/send?phone=57${wContact}&text=${encodeURIComponent(msg)}`
        : `https://api.whatsapp.com/send?text=${encodeURIComponent(msg)}`;
    window.open(url, '_blank');
}

</script>

<template>
    <AppLayout title="Consumo Global de Folios">
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h2 class="font-bold text-3xl text-gray-900 tracking-tight flex items-center gap-3">
                        Consumo Global de Folios
                    </h2>
                    <p class="text-indigo-600 font-medium mt-1">
                        Monitoreo general de todos los clientes activos
                    </p>
                </div>
                <div class="flex gap-2 w-full md:w-auto">
                    <Link :href="route('dashboard')" class="flex-1 md:flex-none text-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 transition">
                        Volver al Dashboard
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl border border-gray-100">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Cliente (Datos y Contacto)</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Inicio Plan</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Contratado</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Usados</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Restantes</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Transcurridos</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Promedio Diario</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold text-indigo-500 uppercase tracking-wider">Días Estimados</th>
                                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="info in consumptions.data" :key="info.id" class="hover:bg-slate-50 transition">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="font-bold text-slate-900">{{ info.client?.name }}</div>
                                        <div class="text-[10px] text-gray-600 font-mono mt-0.5">
                                            NIT: {{ info.client?.nit }} | {{ info.client?.distributor ? info.client.distributor.name : 'Directo' }}
                                        </div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <div class="text-[10px] text-indigo-600 font-medium">{{ info.client?.email }}</div>
                                            <div class="text-[10px] text-gray-500">{{ info.client?.phone }}</div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-500 text-xs">
                                        {{ info.plan_start_date ? info.plan_start_date.split('T')[0] : 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right font-medium text-slate-700">
                                        {{ new Intl.NumberFormat('de-DE').format(info.folios_total) }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right text-slate-500">
                                        {{ new Intl.NumberFormat('de-DE').format(info.sql_usados) }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-indigo-100 text-indigo-800">
                                            {{ new Intl.NumberFormat('de-DE').format(info.folios_remaining) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right text-xs text-gray-500">
                                        {{ info.sql_dias }} días
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right text-xs font-bold text-slate-600">
                                        {{ Math.round(info.sql_promedio * 100) / 100 }} / día
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right">
                                        <span class="text-lg font-black" :class="info.sql_estimados < 30 ? 'text-red-600' : 'text-indigo-600'">
                                            {{ info.sql_estimados < 999999 ? Math.round(info.sql_estimados) : '∞' }}
                                        </span>
                                        <span class="text-[10px] text-gray-400 block -mt-1">Días</span>
                                    </td>
                                </tr>
                                <tr v-if="consumptions.data.length === 0">
                                    <td colspan="11" class="px-4 py-12 text-center text-gray-400 font-medium text-base">
                                        No hay datos de consumo registrados.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100" v-if="consumptions.data.length > 0">
                        <Pagination :links="consumptions.links" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

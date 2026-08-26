<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    distributors: Array,
    directClients: Array,
    debts: Array
});

// Format currency
const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0
    }).format(value);
};

// Generate debt
const generateForm = useForm({
    distributor_id: '',
    client_id: ''
});

const generateDebt = (distId, clientId) => {
    if (confirm('¿Generar liquidación para las licencias pendientes de este cliente?')) {
        generateForm.distributor_id = distId;
        generateForm.client_id = clientId;
        generateForm.post(route('debts.store'));
    }
};

const payForm = useForm({});
const payDebt = (debtId) => {
    if (confirm('¿Marcar como pagado?')) {
        payForm.post(route('debts.pay', debtId));
    }
};

const serviceForm = useForm({});
const renewService = (transactionId) => {
    if (confirm('¿Renovar este servicio por 1 año y marcar como pagado?')) {
        serviceForm.post(route('license-transactions.renew', transactionId));
    }
};
const cancelService = (transactionId) => {
    if (confirm('¿Cancelar este servicio? Se eliminará el cobro y se marcará inactivo.')) {
        serviceForm.post(route('license-transactions.cancel', transactionId));
    }
};

const txForm = useForm({});
const payTx = (transactionId) => {
    if (confirm('¿Marcar este cobro individual como pagado?')) {
        txForm.post(route('license-transactions.pay', transactionId));
    }
};

const sendServiceWhatsApp = (client, srv) => {
    const formatter = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 });
    const formattedPrice = formatter.format(srv.computed_price || 0);
    const dateStr = srv.service_expiration ? new Date(srv.service_expiration).toLocaleDateString() : 'la fecha de corte';
    const message = `¡Hola, ${client.name}! 👋 Te recordamos que tu servicio de *${srv.service_name}* venció el ${dateStr} 🗓️.\n\n¿Deseas continuar con la renovación de este servicio por un valor de *${formattedPrice}*? 💳 Quedamos atentos para ayudarte. 😊`;
    const wContact = client.whatsapp_contact;
    const url = wContact 
        ? `https://api.whatsapp.com/send?phone=57${wContact}&text=${encodeURIComponent(message)}`
        : `https://api.whatsapp.com/send?text=${encodeURIComponent(message)}`;
    window.open(url, '_blank');
};

const copyServiceMessage = (client, srv) => {
    const formatter = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 });
    const formattedPrice = formatter.format(srv.computed_price || 0);
    const dateStr = srv.service_expiration ? new Date(srv.service_expiration).toLocaleDateString() : 'la fecha de corte';
    const message = `¡Hola, ${client.name}! 👋 Te recordamos que tu servicio de *${srv.service_name}* venció el ${dateStr} 🗓️.\n\n¿Deseas continuar con la renovación de este servicio por un valor de *${formattedPrice}*? 💳 Quedamos atentos para ayudarte. 😊`;
    
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(message).then(() => {
            alert('Mensaje copiado al portapapeles');
        });
    } else {
        let textArea = document.createElement("textarea");
        textArea.value = message;
        textArea.style.position = "fixed";
        textArea.style.left = "-999999px";
        textArea.style.top = "-999999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            alert('Mensaje copiado al portapapeles');
        } catch (err) {
            alert('Error al copiar el texto');
        }
        textArea.remove();
    }
};
</script>

<template>
    <AppLayout title="Cuentas por Cobrar">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Cuentas por Cobrar (Distribuidores)
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- Pending Transactions to Bill -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Licencias Pendientes por Liquidar</h3>
                        
                        <div v-if="distributors.length === 0" class="text-gray-500 text-sm">
                            No hay licencias pendientes por facturar.
                        </div>

                        <div v-for="distributor in distributors" :key="'dist-' + distributor.id" class="mb-6">
                            <h4 class="font-semibold text-indigo-700 bg-indigo-50 p-3 rounded-t-lg flex justify-between items-center">
                                <span>Distribuidor: {{ distributor.name }}</span>
                                <span class="bg-indigo-600 text-white px-3 py-1 rounded-full text-sm">Total a cobrar: {{ formatCurrency(distributor.clients.reduce((sum, c) => sum + (c.pending_amount || 0), 0)) }}</span>
                            </h4>
                            <div class="border rounded-b-lg p-4 bg-gray-50">
                                <div v-for="client in distributor.clients" :key="'cli-' + client.id" class="mb-4 last:mb-0 flex items-center justify-between bg-white p-4 rounded shadow-sm">
                                    <div class="flex-1">
                                        <div class="font-bold text-gray-800">{{ client.name }}</div>
                                        <!-- Transacciones de Licencias y Folios -->
                                        <div v-if="client.license_transactions.filter(t => t.type !== 'service').length > 0" class="mb-3 space-y-1 mt-2">
                                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Licencias y Folios</p>
                                            <div v-for="tx in client.license_transactions.filter(t => t.type !== 'service')" :key="tx.id" class="flex justify-between items-center bg-gray-50 border border-gray-200 p-2 rounded text-sm">
                                                <span class="font-medium text-slate-700 flex items-center gap-2">
                                                    <span v-if="tx.type === 'folios'" class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-xs font-bold">Paquete de {{ tx.folios_count }} Folios</span>
                                                    <span v-else-if="tx.type === 'unlimited_folios'" class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-xs font-bold">Folios Ilimitados</span>
                                                    <span v-else-if="tx.type === 'new'" class="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded text-xs font-bold">Licencia Nueva</span>
                                                    <span v-else-if="tx.type === 'renewal'" class="bg-orange-100 text-orange-700 px-2 py-0.5 rounded text-xs font-bold">Renovación</span>
                                                    <span class="ml-2 font-bold text-gray-800">{{ formatCurrency(tx.computed_price || 0) }}</span>
                                                </span>
                                                <button @click="payTx(tx.id)" class="text-indigo-600 hover:text-indigo-800 font-bold text-xs tracking-wider transition px-3 py-1 bg-indigo-50 hover:bg-indigo-100 rounded border border-indigo-200">Pagar (Solo este)</button>
                                            </div>
                                        </div>
                                        
                                        <!-- Servicios Pendientes (Distribuidor) -->
                                        <div v-if="client.license_transactions.filter(t => t.type === 'service').length > 0" class="mt-3">
                                            <p class="text-xs font-bold text-indigo-500 uppercase tracking-wider mb-2">Servicios Anuales Pendientes</p>
                                            <div v-for="srv in client.license_transactions.filter(t => t.type === 'service')" :key="srv.id" class="flex justify-between items-center bg-indigo-50/50 border border-indigo-100 p-2 rounded mb-2 text-sm">
                                                <span class="font-medium text-slate-700">{{ srv.service_name }} <span class="ml-2 font-bold text-gray-800">{{ formatCurrency(srv.computed_price || 0) }}</span></span>
                                                <div class="flex items-center gap-3">
                                                    <button @click="sendServiceWhatsApp(client, srv)" class="text-green-500 hover:text-green-700 transition" title="Enviar Recordatorio WhatsApp">
                                                        <svg class="w-5 h-5 inline" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                                    </button>
                                                    <button @click="copyServiceMessage(client, srv)" class="text-slate-500 hover:text-slate-700 transition" title="Copiar Datos">
                                                        <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                                    </button>
                                                    <button @click="renewService(srv.id)" class="text-green-600 hover:text-green-800 font-bold transition ml-2">Renovar (+1 Año)</button>
                                                    <button @click="cancelService(srv.id)" class="text-red-500 hover:text-red-700 font-bold transition">Cancelar</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-end gap-2 ml-4">
                                        <div class="text-lg font-bold text-indigo-700">Total: {{ new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(client.pending_amount || 0) }}</div>
                                        <PrimaryButton @click="generateDebt(distributor.id, client.id)" :disabled="generateForm.processing">
                                            Marcar como Pagado
                                        </PrimaryButton>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Clientes Directos -->
                        <div v-if="directClients && directClients.length > 0" class="mb-6">
                            <h4 class="font-semibold text-emerald-700 bg-emerald-50 p-3 rounded-t-lg flex justify-between items-center">
                                <span>Clientes Directos</span>
                                <span class="bg-emerald-600 text-white px-3 py-1 rounded-full text-sm">Total a cobrar: {{ formatCurrency(directClients.reduce((sum, c) => sum + (c.pending_amount || 0), 0)) }}</span>
                            </h4>
                            <div class="border rounded-b-lg p-4 bg-gray-50">
                                <div v-for="client in directClients" :key="'cli-dir-' + client.id" class="mb-4 last:mb-0 flex items-center justify-between bg-white p-4 rounded shadow-sm border-l-4 border-emerald-500">
                                    <div class="flex-1">
                                        <div class="font-bold text-gray-800">{{ client.name }}</div>
                                        <!-- Transacciones de Licencias y Folios -->
                                        <div v-if="client.license_transactions.filter(t => t.type !== 'service').length > 0" class="mb-3 space-y-1 mt-2">
                                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Licencias y Folios</p>
                                            <div v-for="tx in client.license_transactions.filter(t => t.type !== 'service')" :key="tx.id" class="flex justify-between items-center bg-gray-50 border border-gray-200 p-2 rounded text-sm">
                                                <span class="font-medium text-slate-700 flex items-center gap-2">
                                                    <span v-if="tx.type === 'folios'" class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-xs font-bold">Paquete de {{ tx.folios_count }} Folios</span>
                                                    <span v-else-if="tx.type === 'unlimited_folios'" class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-xs font-bold">Folios Ilimitados</span>
                                                    <span v-else-if="tx.type === 'new'" class="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded text-xs font-bold">Licencia Nueva</span>
                                                    <span v-else-if="tx.type === 'renewal'" class="bg-orange-100 text-orange-700 px-2 py-0.5 rounded text-xs font-bold">Renovación</span>
                                                    <span class="ml-2 font-bold text-gray-800">{{ formatCurrency(tx.computed_price || 0) }}</span>
                                                </span>
                                                <button @click="payTx(tx.id)" class="text-emerald-600 hover:text-emerald-800 font-bold text-xs tracking-wider transition px-3 py-1 bg-emerald-50 hover:bg-emerald-100 rounded border border-emerald-200">Pagar (Solo este)</button>
                                            </div>
                                        </div>
                                        
                                        <!-- Servicios Pendientes (Directo) -->
                                        <div v-if="client.license_transactions.filter(t => t.type === 'service').length > 0" class="mt-3">
                                            <p class="text-xs font-bold text-emerald-500 uppercase tracking-wider mb-2">Servicios Anuales Pendientes</p>
                                            <div v-for="srv in client.license_transactions.filter(t => t.type === 'service')" :key="srv.id" class="flex justify-between items-center bg-emerald-50/50 border border-emerald-100 p-2 rounded mb-2 text-sm">
                                                <span class="font-medium text-slate-700">{{ srv.service_name }} <span class="ml-2 font-bold text-gray-800">{{ formatCurrency(srv.computed_price || 0) }}</span></span>
                                                <div class="flex items-center gap-3">
                                                    <button @click="sendServiceWhatsApp(client, srv)" class="text-green-500 hover:text-green-700 transition" title="Enviar Recordatorio WhatsApp">
                                                        <svg class="w-5 h-5 inline" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                                    </button>
                                                    <button @click="copyServiceMessage(client, srv)" class="text-slate-500 hover:text-slate-700 transition" title="Copiar Datos">
                                                        <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                                    </button>
                                                    <button @click="renewService(srv.id)" class="text-green-600 hover:text-green-800 font-bold transition ml-2">Renovar (+1 Año)</button>
                                                    <button @click="cancelService(srv.id)" class="text-red-500 hover:text-red-700 font-bold transition">Cancelar</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-end gap-2 ml-4">
                                        <div class="text-lg font-bold text-emerald-700">Total: {{ new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(client.pending_amount || 0) }}</div>
                                        <PrimaryButton @click="generateDebt(null, client.id)" :disabled="generateForm.processing" class="!bg-emerald-600 hover:!bg-emerald-700">
                                            Marcar como Pagado
                                        </PrimaryButton>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Debts / Invoices -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Historial de Pagos</h3>
                        
                        <table class="min-w-full divide-y divide-gray-200 mt-4">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Distribuidor / Cliente</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Detalle</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Pagado</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="debt in debts" :key="debt.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ new Date(debt.created_at).toLocaleDateString() }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div class="font-bold">{{ debt.distributor ? debt.distributor.name : 'Cliente Directo' }}</div>
                                        <div class="text-xs text-gray-500">{{ debt.client.name }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs">
                                        {{ debt.details }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-green-600">
                                        {{ formatCurrency(debt.amount) }}
                                    </td>
                                </tr>
                                <tr v-if="debts.length === 0">
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">No hay pagos registrados.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    totalClients: Number,
    totalLicenses: Number,
    activeLicenses: Number,
    expiringLicenses: Object,
    expiringServices: Array,
    criticalFolios: Array,
    expiringCertificates: Array,
    recentClients: Array,
});


const fallbackCopyTextToClipboard = (text) => {
    var textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";
    textArea.style.opacity = "0";

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

const sendFolioWhatsApp = (info) => {
    const msg = generateFolioMessage(info);
    const wContact = info.client?.whatsapp_contact;
    const url = wContact 
        ? `https://api.whatsapp.com/send?phone=57${wContact}&text=${encodeURIComponent(msg)}`
        : `https://api.whatsapp.com/send?text=${encodeURIComponent(msg)}`;
    window.open(url, '_blank');
}


const generateLicenseMessage = (license) => {
    const clientName = license.client?.name || 'Cliente';
    const computerName = license.name || 'Licencia';
    const expDate = license.expiration_date ? license.expiration_date.split('T')[0] : 'N/A';
    
    return `🚨 *¡RECORDATORIO DE VENCIMIENTO!* 🚨
Hola *${clientName}*, le informamos que su licencia para *${computerName}* está próxima a vencer.

🗓️ Fecha de vencimiento: *${expDate}* (Faltan ${license.dias_restantes} días)

⚠️ *Por favor renueve pronto para evitar la suspensión del servicio.* ⚠️`;
}

const copyLicenseMessage = (license) => {
    const msg = generateLicenseMessage(license);
    fallbackCopyTextToClipboard(msg);
}

const sendLicenseWhatsApp = (license) => {
    const msg = generateLicenseMessage(license);
    const wContact = license.client?.whatsapp_contact;
    const url = wContact 
        ? `https://api.whatsapp.com/send?phone=57${wContact}&text=${encodeURIComponent(msg)}`
        : `https://api.whatsapp.com/send?text=${encodeURIComponent(msg)}`;
    window.open(url, '_blank');
}

const generateServiceMessage = (service) => {
    const clientName = service.client?.name || 'Cliente';
    const serviceName = service.name || 'Servicio';
    const expDate = service.expiration_date ? service.expiration_date.split('T')[0] : 'N/A';
    
    return `🚨 *¡RECORDATORIO DE VENCIMIENTO!* 🚨
Hola *${clientName}*, le informamos que su servicio de *${serviceName}* está próximo a vencer.

🗓️ Fecha de vencimiento: *${expDate}* (Faltan ${service.dias_restantes} días)

⚠️ *Por favor renueve pronto para evitar la interrupción de este servicio.* ⚠️`;
}

const copyServiceMessage = (service) => {
    const msg = generateServiceMessage(service);
    fallbackCopyTextToClipboard(msg);
}

const sendServiceWhatsApp = (service) => {
    const msg = generateServiceMessage(service);
    const wContact = service.client?.whatsapp_contact;
    const url = wContact 
        ? `https://api.whatsapp.com/send?phone=57${wContact}&text=${encodeURIComponent(msg)}`
        : `https://api.whatsapp.com/send?text=${encodeURIComponent(msg)}`;
    window.open(url, '_blank');
}


const generateCertMessage = (info) => {
    const clientName = info.client?.name || 'Cliente';
    const date = info.certificate_expiration_date ? info.certificate_expiration_date.split('T')[0] : 'N/A';
    const dias = info.dias_restantes_certificado;
    
    const isJuridica = info.client?.type_organization_id === "1" || info.client?.type_organization_id === 1;
    const requirement = isJuridica 
        ? "Certificado de existencia y representación legal (Cámara de Comercio) emitido con menos de 30 días de vigencia" 
        : "Copia de la cédula";

    return `🚨 *¡RECORDATORIO DE VENCIMIENTO DE CERTIFICADO DIGITAL!* 🚨
Hola *${clientName}*, le informamos que su Certificado de Firma Digital está próximo a vencer.

🗓️ Fecha de vencimiento: *${date}* (Faltan ${dias} días)

⚠️ *Por favor recuerde que para renovar su certificado se necesita:*
👉 ${requirement}

Agradecemos realizar el trámite con prontitud para evitar interrupciones en su facturación electrónica.`;
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

const sendCertWhatsApp = (cert) => {
    const msg = generateCertMessage(cert);
    const wContact = cert.client?.whatsapp_contact;
    const url = wContact 
        ? `https://api.whatsapp.com/send?phone=57${wContact}&text=${encodeURIComponent(msg)}`
        : `https://api.whatsapp.com/send?text=${encodeURIComponent(msg)}`;
    window.open(url, '_blank');
}

</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Panel de Control General
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Stats Row -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Total Clients -->
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 flex items-center border-l-4 border-indigo-500">
                        <div class="p-3 rounded-full bg-indigo-100 text-indigo-500 mr-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                        <div>
                            <div class="text-gray-500 font-bold uppercase tracking-wider text-xs">Total Clientes</div>
                            <div class="text-3xl font-bold text-gray-800">{{ totalClients }}</div>
                        </div>
                    </div>

                    <!-- Active Licenses -->
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 flex items-center border-l-4 border-green-500">
                        <div class="p-3 rounded-full bg-green-100 text-green-500 mr-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <div class="text-gray-500 font-bold uppercase tracking-wider text-xs">Licencias Activas</div>
                            <div class="text-3xl font-bold text-gray-800">{{ activeLicenses }} <span class="text-sm text-gray-400 font-normal">/ {{ totalLicenses }}</span></div>
                        </div>
                    </div>

                    <!-- Expiring Soon -->
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 flex items-center border-l-4 border-yellow-500">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <div class="text-gray-500 font-bold uppercase tracking-wider text-xs">Vencidas y Próximas a Vencer</div>
                            <div class="text-3xl font-bold text-gray-800">{{ expiringLicenses.total }}</div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="space-y-8 mt-8">
                    
                    <!-- Expiring Licenses Table -->
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                            <h3 class="font-bold text-gray-700">Licencias Próximas a Vencer</h3>
                            <Link :href="route('clients.index')" class="text-sm text-indigo-600 hover:text-indigo-900">Ver Clientes &rarr;</Link>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PC / Caja</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Días Restantes</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vencimiento</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acción</th>
                                        <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Contacto</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="license in expiringLicenses.data" :key="license.id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ license.client.name }}</div>
                                            <div class="text-xs text-gray-500">{{ license.client.nit }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ license.name }}</div>
                                            <div class="text-xs text-gray-500">Caja #{{ license.box_number }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                                :class="new Date(license.expiration_date) < new Date() ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'">
                                                {{ license.expiration_date ? license.expiration_date.split('T')[0] : 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span class="text-sm font-bold" :class="license.dias_restantes < 7 ? 'text-red-600' : 'text-amber-500'">
                                                {{ license.dias_restantes }} días
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
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
                                        </td>
                                    </tr>
                                    <tr v-if="expiringLicenses.data.length === 0">
                                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No hay licencias por vencer o vencidas recientemente.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Pagination -->
                        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex items-center justify-between" v-if="expiringLicenses.data.length > 0">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <Link v-if="expiringLicenses.prev_page_url" :href="expiringLicenses.prev_page_url" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"> Anterior </Link>
                                <Link v-if="expiringLicenses.next_page_url" :href="expiringLicenses.next_page_url" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"> Siguiente </Link>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Mostrando <span class="font-medium">{{ expiringLicenses.from }}</span> a <span class="font-medium">{{ expiringLicenses.to }}</span> de <span class="font-medium">{{ expiringLicenses.total }}</span> resultados
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                        <template v-for="(link, key) in expiringLicenses.links" :key="key">
                                            <Link
                                                v-if="link.url"
                                                :href="link.url"
                                                v-html="link.label"
                                                class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                                                :class="{ 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600': link.active, 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50': !link.active, 'rounded-l-md': key === 0, 'rounded-r-md': key === expiringLicenses.links.length - 1 }"
                                            />
                                            <span
                                                v-else
                                                v-html="link.label"
                                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 cursor-not-allowed"
                                                :class="{ 'rounded-l-md': key === 0, 'rounded-r-md': key === expiringLicenses.links.length - 1 }"
                                            ></span>
                                        </template>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Expiring Services List -->
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mt-6">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                            <h3 class="font-bold text-gray-700">Servicios Adicionales Próximos a Vencer (15 días)</h3>
                            <span class="px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-bold rounded-full">
                                {{ expiringServices?.length || 0 }} servicios
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Cliente</th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Servicio</th>
                                        <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Días Restantes</th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Vencimiento</th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Valor</th>
                                        <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Contacto</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="service in expiringServices" :key="service.id" class="hover:bg-slate-50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ service.client.name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 font-bold">{{ service.name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                {{ service.expiration_date ? service.expiration_date.split('T')[0] : 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span class="text-sm font-bold" :class="service.dias_restantes < 7 ? 'text-red-600' : 'text-amber-500'">
                                                {{ service.dias_restantes }} días
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-bold">
                                            ${{ new Intl.NumberFormat('de-DE').format(service.price) }}
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
                                        </td>
                                    </tr>
                                    <tr v-if="!expiringServices || expiringServices.length === 0">
                                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No hay servicios próximos a vencer en los próximos 15 días.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    </div>

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
                
                <div class="p-6 bg-gray-50">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100">
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
                                        <th class="px-4 py-3 text-right text-xs font-bold text-red-500 uppercase tracking-wider">Días Estimados</th>
                                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="info in criticalFolios" :key="info.id" class="hover:bg-red-50/30 transition">
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
                                            {{ new Intl.NumberFormat('de-DE').format(info.folios_usados) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800">
                                                {{ new Intl.NumberFormat('de-DE').format(info.folios_remaining) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right text-xs text-gray-500">
                                            {{ Math.round(info.dias_transcurridos) }} días
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right text-xs font-bold text-slate-600">
                                            {{ info.promedio_folios_usados_por_dia }} / día
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right">
                                            <span class="text-lg font-black" :class="info.dias_estimados_para_terminar < 30 ? 'text-red-600' : 'text-amber-600'">
                                                {{ info.dias_estimados_para_terminar < 999999 ? info.dias_estimados_para_terminar : '∞' }}
                                            </span>
                                            <span class="text-[10px] text-gray-400 block -mt-1">Días</span>
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
                                        </td>
                                    </tr>
                                    <tr v-if="!criticalFolios || criticalFolios.length === 0">
                                        <td colspan="9" class="px-4 py-8 text-center text-gray-400 font-medium text-sm">
                                            No hay datos de consumo registrados.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>


            
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

<!-- Recent Clients List -->
            <div class="mt-8 bg-white overflow-hidden shadow-xl sm:rounded-lg h-fit">

                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h3 class="font-bold text-gray-700">Clientes Recientes</h3>
                        </div>
                        <ul class="divide-y divide-gray-200">
                            <li v-for="client in recentClients" :key="client.id" class="px-6 py-4 hover:bg-gray-50 transition-colors">
                                <Link :href="route('clients.show', client.id)" class="flex items-center justify-between group">
                                    <div>
                                        <p class="text-sm font-medium text-indigo-600 group-hover:text-indigo-800">{{ client.name }}</p>
                                        <p class="text-xs text-gray-500">{{ client.email }}</p>
                                    </div>
                                    <svg class="h-5 w-5 text-gray-400 group-hover:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </Link>
                            </li>
                        </ul>
                        <div class="bg-gray-50 px-6 py-3 border-t border-gray-200">
                            <Link :href="route('clients.create')" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 block text-center">
                                + Registrar Nuevo Cliente
                            </Link>
                        
            </div>
            
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import DialogModal from '@/Components/DialogModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import pickBy from 'lodash/pickBy';
import throttle from 'lodash/throttle';
import Swal from 'sweetalert2';

const showToast = (message, type = 'success') => {
    Swal.fire({
        icon: type === 'danger' ? 'error' : type,
        title: message,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
    });
};

const props = defineProps({
    computers: Object,
    filters: Object,
    clients: Array,
    isAdmin: Boolean,
});

const form = ref({
    search: props.filters.search || '',
    client_id: props.filters.client_id || '',
    sort: props.filters.sort || 'id',
    direction: props.filters.direction || 'desc',
    per_page: props.filters.per_page || 15,
});

watch(form, throttle(() => {
    router.get(route('computers.index'), pickBy(form.value), {
        preserveState: true,
        replace: true,
    });
}, 150), { deep: true });

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return dateString.split('T')[0];
};

// Edit Modal Logic
const confirmingComputerManagement = ref(false);
const editingComputer = ref(null);

const computerForm = useForm({
    client_id: '',
    box_number: '',
    name: '',
    pin: '',
    license_key: '',
    license_type: 'normal',
    expiration_date: '',
    is_active: true,
    generate_charge: true,
    observation: '',
});

const openEditModal = (pc) => {
    editingComputer.value = pc;
    computerForm.client_id = pc.client_id;
    computerForm.box_number = pc.box_number;
    computerForm.name = pc.name;
    computerForm.pin = pc.pin || '';
    computerForm.license_key = pc.license_key;
    computerForm.license_type = pc.license_type;
    computerForm.expiration_date = formatDate(pc.expiration_date);
    computerForm.is_active = !!pc.is_active;
    computerForm.generate_charge = true;
    computerForm.observation = pc.observation;
    confirmingComputerManagement.value = true;
};

const closeModal = () => {
    confirmingComputerManagement.value = false;
    computerForm.reset();
};

const saveComputer = () => {
    computerForm.put(route('computers.update', editingComputer.value.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const deleteComputer = (pc) => {
    Swal.fire({
        title: '¿Eliminar licencia?',
        text: "Esta acción no se puede deshacer.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('computers.destroy', pc.id), {
                preserveScroll: true,
                onSuccess: () => showToast('Licencia eliminada', 'success'),
            });
        }
    });
};

const getStatusClass = (days) => {
    if (days < 0) return 'bg-red-100 text-red-700 border-red-200';
    if (days < 15) return 'bg-amber-100 text-amber-700 border-amber-200';
    return 'bg-green-100 text-green-700 border-green-200';
};

const generateLicense = async () => {
    if (!computerForm.pin || !computerForm.expiration_date) {
        showToast('Por favor ingrese el PIN y la Fecha de Vencimiento primero.', 'warning');
        return;
    }
    try {
        const response = await axios.post(route('computers.generate-license'), {
            pin: computerForm.pin,
            expiration_date: computerForm.expiration_date,
            client_id: computerForm.client_id
        });
        if (response.data && response.data.license_key) {
            computerForm.license_key = response.data.license_key;
            if (response.data.extracted_data) {
                if (!computerForm.name && response.data.extracted_data.pc_name) {
                    computerForm.name = response.data.extracted_data.pc_name;
                }
                if (!computerForm.box_number && response.data.extracted_data.box_number) {
                    computerForm.box_number = response.data.extracted_data.box_number;
                }
            }
        }
    } catch (error) {
        console.error(error);
        showToast(error.response?.data?.message || 'Error al generar la licencia. Verifique los datos.', 'danger');
    }
};

const setNextYear = () => {
    let baseDate = new Date();
    if (computerForm.expiration_date) {
        const parts = computerForm.expiration_date.split('-');
        if (parts.length === 3) {
            baseDate = new Date(parts[0], parts[1] - 1, parts[2]);
        }
    }
    baseDate.setFullYear(baseDate.getFullYear() + 1);
    const y = baseDate.getFullYear();
    const m = String(baseDate.getMonth() + 1).padStart(2, '0');
    const d = String(baseDate.getDate()).padStart(2, '0');
    computerForm.expiration_date = `${y}-${m}-${d}`;
};

const getLicenseMessage = (pc) => {
    const clientName = pc.client?.name || 'Cliente';
    return `\u{1F44B} Hola *${clientName}*,

Aqu\u00ED tienes los detalles de tu licencia:

\u{1F4BB} *Estaci\u00F3n:* ${pc.box_number} - ${pc.name || 'N/A'}
\u{1F4C5} *Vence el:* ${formatDate(pc.expiration_date)}

\u{1F511} *Clave de Activaci\u00F3n:*
*${pc.license_key}*

\u00A1Gracias por tu confianza! \u{1F680}`;
};

const copyLicenseData = (pc) => {
    const text = getLicenseMessage(pc);
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text);
        showToast('¡Datos de licencia copiados!', 'success');
    } else {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        
        // Prevent scrolling by styling the textarea out of view but fixed
        textArea.style.position = "fixed";
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.opacity = "0";
        
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            showToast('¡Datos de licencia copiados!', 'success');
        } catch (err) {
            showToast('Error al copiar', 'danger');
        }
        document.body.removeChild(textArea);
    }
};

const sendWhatsApp = (pc) => {
    const text = getLicenseMessage(pc);
    const wContact = pc.client?.whatsapp_contact;
    const url = wContact 
        ? `https://api.whatsapp.com/send?phone=57${wContact}&text=${encodeURIComponent(text)}`
        : `https://api.whatsapp.com/send?text=${encodeURIComponent(text)}`;
    window.open(url, '_blank');
};
</script>

<template>
    <AppLayout title="Gestión de Licencias">
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h2 class="font-bold text-3xl text-gray-900 tracking-tight">
                        Control de Licencias
                    </h2>
                    <p class="text-indigo-600 font-medium">Panel centralizado de estaciones y claves de activación</p>
                </div>
                <div class="flex gap-2">
                    <Link :href="route('clients.index')" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-bold text-gray-700 hover:bg-gray-50 transition shadow-sm uppercase tracking-wider">
                        Ver Empresas
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Filters Section -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div class="md:col-span-2">
                            <InputLabel value="Búsqueda Global (Nombre, Clave, Caja, PIN, Notas)" />
                            <TextInput v-model="form.search" type="text" class="w-full mt-1" placeholder="Buscar..." />
                        </div>
                        <div>
                            <InputLabel value="Filtrar por Empresa" />
                            <select v-model="form.client_id" class="w-full border-gray-200 rounded-lg text-sm focus:ring-indigo-500 mt-1">
                                <option value="">Todas las empresas</option>
                                <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel value="Registros por Página" />
                            <select v-model="form.per_page" class="w-full border-gray-200 rounded-lg text-sm focus:ring-indigo-500 mt-1">
                                <option :value="15">15 registros</option>
                                <option :value="50">50 registros</option>
                                <option :value="100">100 registros</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Table Content -->
                <div class="bg-white rounded-2xl shadow-xl border border-indigo-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-slate-900">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-indigo-200 uppercase tracking-widest cursor-pointer hover:text-white transition" @click="form.sort = 'id'; form.direction = form.direction === 'asc' ? 'desc' : 'asc'">
                                        ID <span v-if="form.sort === 'id'">{{ form.direction === 'asc' ? '↑' : '↓' }}</span>
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-indigo-200 uppercase tracking-widest cursor-pointer hover:text-white transition" @click="form.sort = 'client_name'; form.direction = form.direction === 'asc' ? 'desc' : 'asc'">
                                        Empresa <span v-if="form.sort === 'client_name'">{{ form.direction === 'asc' ? '↑' : '↓' }}</span>
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-indigo-200 uppercase tracking-widest">Estación / Caja</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-indigo-200 uppercase tracking-widest">Clave de Activación</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-indigo-200 uppercase tracking-widest cursor-pointer hover:text-white transition" @click="form.sort = 'days_remaining'; form.direction = form.direction === 'asc' ? 'desc' : 'asc'">
                                        Vencimiento <span v-if="form.sort === 'days_remaining'">{{ form.direction === 'asc' ? '↑' : '↓' }}</span>
                                    </th>
                                    <th class="px-6 py-4 text-right text-xs font-bold text-indigo-200 uppercase tracking-widest">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 bg-white">
                                <tr v-for="pc in computers.data" :key="pc.id" class="hover:bg-indigo-50/30 transition group">
                                    <td class="px-6 py-5 whitespace-nowrap text-xs font-mono text-gray-400">#{{ pc.id }}</td>
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <Link :href="route('clients.show', pc.client_id)" class="text-sm font-bold text-slate-800 hover:text-indigo-600 transition">
                                            {{ pc.client?.name }}
                                        </Link>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <span class="w-7 h-7 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-xs mr-3 border border-slate-200">{{ pc.box_number }}</span>
                                            <span class="text-sm font-medium text-slate-700">{{ pc.name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <code class="text-xs bg-slate-50 text-indigo-700 border border-indigo-100 px-2 py-1 rounded select-all font-bold tracking-tighter">
                                            {{ pc.license_key }}
                                        </code>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="text-sm text-slate-600 font-medium">{{ formatDate(pc.expiration_date) }}</div>
                                            <span :class="['px-3 py-1 rounded-full text-xs font-bold border', getStatusClass(pc.days_remaining)]">
                                                {{ pc.days_remaining }} días
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap text-right text-sm">
                                        <div class="flex justify-end gap-2">
                                            <button v-if="isAdmin" @click="openEditModal(pc)" class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-xl transition font-bold text-xs" title="Editar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                                Editar
                                            </button>
                                            
                                            <button @click="sendWhatsApp(pc)" class="flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-xl transition font-bold text-xs" title="Enviar por WhatsApp">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                                Enviar
                                            </button>
                                            
                                            <button @click="copyLicenseData(pc)" class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 text-slate-700 hover:bg-slate-200 rounded-xl transition font-bold text-xs" title="Copiar Datos">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                                Copiar
                                            </button>
                                            
                                            <button v-if="isAdmin" @click="deleteComputer(pc)" class="flex items-center gap-1.5 px-3 py-1.5 bg-red-50 text-red-700 hover:bg-red-100 rounded-xl transition font-bold text-xs" title="Eliminar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                Eliminar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!computers.data.length">
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-400 italic">
                                        No se encontraron licencias con los filtros actuales.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                        <Pagination :links="computers.links" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <DialogModal :show="confirmingComputerManagement" @close="closeModal">
            <template #title>
                <div class="flex items-center text-slate-900 font-bold">
                    <div class="p-2 bg-indigo-100 rounded-lg mr-3 text-indigo-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
                    </div>
                    Actualizar Licencia / Estación
                </div>
            </template>

            <template #content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                    <div class="col-span-1 md:col-span-2">
                        <InputLabel value="Empresa Propietaria" />
                        <select v-model="computerForm.client_id" class="w-full border-gray-200 rounded-xl text-sm focus:ring-indigo-500 mt-1 bg-gray-100" disabled>
                            <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
                        </select>
                    </div>

                    <div class="space-y-4">
                         <div>
                            <InputLabel for="box_number" value="Puesto de Trabajo / Caja #" />
                            <div class="relative mt-1">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 font-bold">#</span>
                                <TextInput id="box_number" v-model="computerForm.box_number" type="text" class="pl-8 block w-full !rounded-xl" autofocus />
                            </div>
                            <InputError :message="computerForm.errors.box_number" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="name" value="Identificador Estación (Nombre PC)" />
                            <TextInput id="name" v-model="computerForm.name" type="text" class="mt-1 block w-full !rounded-xl" placeholder="Ej: CAJA-PRINCIPAL-01" />
                            <InputError :message="computerForm.errors.name" class="mt-2" />
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <InputLabel for="license_type" value="Modalidad de Licencia" />
                            <select id="license_type" v-model="computerForm.license_type" class="border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl mt-1 block w-full shadow-sm text-sm">
                                <option value="normal">Licencia Estándar</option>
                                <option value="vinculado">Licencia Vinculada</option>
                            </select>
                        </div>

                        <div>
                            <InputLabel for="expiration_date" value="Fecha de Vencimiento" />
                            <TextInput id="expiration_date" v-model="computerForm.expiration_date" type="date" class="mt-1 block w-full !rounded-xl" />
                            <InputError :message="computerForm.errors.expiration_date" class="mt-2" />
                        </div>
                    </div>

                    <div class="col-span-1 md:col-span-2">
                        <InputLabel for="pin" value="PIN de Activación" />
                        <TextInput id="pin" v-model="computerForm.pin" type="text" class="mt-1 block w-full !rounded-xl" />
                        <InputError :message="computerForm.errors.pin" class="mt-2" />
                    </div>

                    <div class="col-span-1 md:col-span-2">
                        <InputLabel for="license_key" value="Clave de Activación (License Key)" />
                        <div class="flex items-end gap-2 mt-1">
                            <div class="flex-grow">
                                <TextInput id="license_key" v-model="computerForm.license_key" type="text" class="block w-full font-mono !rounded-xl text-center !text-lg !font-bold !text-indigo-600 border-indigo-200" />
                            </div>
                            <button type="button" @click="generateLicense" class="px-4 py-2 bg-indigo-600 text-white !rounded-xl hover:bg-indigo-700 transition font-bold whitespace-nowrap h-[46px]" title="Generar nueva clave">
                                Generar Clave
                            </button>
                            <button type="button" @click="setNextYear" class="px-4 py-2 bg-slate-600 text-white !rounded-xl hover:bg-slate-700 transition font-bold whitespace-nowrap h-[46px]" title="Sugerir 1 Año">
                                +1 Año
                            </button>
                        </div>
                        <InputError :message="computerForm.errors.license_key" class="mt-2" />
                    </div>

                    <div class="col-span-1 md:col-span-2">
                        <InputLabel for="observation" value="Notas Internas" />
                        <TextInput id="observation" v-model="computerForm.observation" type="text" class="mt-1 block w-full !rounded-xl" />
                    </div>
                    
                    <div class="col-span-1 md:col-span-2 bg-gray-50 p-3 rounded-xl border border-gray-200 flex items-center">
                        <input id="is_active" type="checkbox" v-model="computerForm.is_active" class="rounded-lg border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 w-5 h-5">
                        <label for="is_active" class="ml-3 block text-sm font-bold text-slate-700">Estado: Habilitada para uso</label>
                    </div>

                    <div class="col-span-1 md:col-span-2 bg-indigo-50 p-3 rounded-xl border border-indigo-200 flex items-center">
                        <input id="generate_charge" type="checkbox" v-model="computerForm.generate_charge" class="rounded-lg border-indigo-300 text-indigo-600 shadow-sm focus:ring-indigo-500 w-5 h-5">
                        <label for="generate_charge" class="ml-3 block text-sm font-bold text-indigo-900">Generar cobro para esta licencia/estación automáticamente</label>
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeModal" class="!rounded-xl">
                    Cancelar
                </SecondaryButton>

                <PrimaryButton class="ms-3 !bg-indigo-600 !hover:bg-indigo-700 !rounded-xl !px-8" :class="{ 'opacity-25': computerForm.processing }" :disabled="computerForm.processing" @click="saveComputer">
                    Guardar Cambios
                </PrimaryButton>
            </template>
        </DialogModal>
    </AppLayout>
</template>


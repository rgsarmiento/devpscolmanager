<script setup>
import { ref, onMounted, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Combobox from '@/Components/Combobox.vue';

defineProps({
    distributors: Array,
});

const form = useForm({
    name: '',
    nit: '',
    dv: '',
    merchant_registration: '',
    email: '',
    phone: '',
    address: '',
    municipality_id: '',
    type_document_identification_id: '3', // Default NIT
    type_organization_id: '1', // Default Persona Juridica
    type_liability_id: '', 
    type_regime_id: '',
    distributor_id: '',
});

// ... (rest of the script is same, just ensure Combobox is used in template)
// Need to keep the rest of the script mostly intact, just adding the import
// But I need to replace the TEMPLATE section specifically for Dep/Muni

// Catalogs
const documentTypes = ref([]);
const liabilities = ref([]);
const organizations = ref([]);
const regimes = ref([]);
const departments = ref([]);
const municipalities = ref([]);
const filteredMunicipalities = ref([]);
const selectedDepartment = ref('');

const processingFile = ref(false);

onMounted(async () => {
    try {
        const [docsRes, liabRes, orgsRes, regsRes, deptsRes, munisRes] = await Promise.all([
            axios.get(route('type-document-identifications.index')),
            axios.get(route('type-liabilities.index')),
            axios.get(route('type-organizations.index')),
            axios.get(route('type-regimes.index')),
            axios.get(route('departments.index')),
            axios.get(route('municipalities.index')),
        ]);

        documentTypes.value = docsRes.data;
        liabilities.value = liabRes.data;
        organizations.value = orgsRes.data;
        regimes.value = regsRes.data;
        departments.value = deptsRes.data;
        municipalities.value = munisRes.data;

    } catch (error) {
        console.error('Error loading catalogs:', error);
    }
});

// Filter Municipalities when Department changes
watch(selectedDepartment, (newDeptId) => {
    form.municipality_id = ''; // Reset selection
    if (!newDeptId) {
        filteredMunicipalities.value = [];
        return;
    }
    filteredMunicipalities.value = municipalities.value.filter(m => m.department_id == newDeptId);
});

const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    processingFile.value = true;
    const formData = new FormData();
    formData.append('rut_file', file);

    axios.post(route('clients.parse-rut'), formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
    })
    .then(response => {
        const data = response.data;
        if (data.nit) form.nit = data.nit;
        if (data.dv) form.dv = data.dv;
        if (data.name) form.name = data.name;
        if (data.address) form.address = data.address;
        if (data.email) form.email = data.email;
        if (data.phone) form.phone = data.phone;
        
        // Try to match department/municipality from text if possible (advanced)
        // For now just basic fields
    })
    .catch(error => {
        console.error('Error parsing RUT:', error);
        alert('No se pudo procesar el archivo. Por favor ingresa los datos manualmente.');
    })
    .finally(() => {
        processingFile.value = false;
    });
};

const submit = () => {
    form.post(route('clients.store'), {
        onSuccess: () => {
            // Optional: reset or notify
        },
        onError: (errors) => {
            console.error('Validation Errors:', errors);
            // Construct a message listing the errors
            let msg = 'Por favor corrige los siguientes errores:\n\n';
            Object.values(errors).forEach(err => {
                msg += `- ${err}\n`;
            });
            alert(msg);
        }
    });
};
</script>

<template>
    <AppLayout title="Nueva Empresa">
        <!-- ... Header code ... -->
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Configuración de nueva empresa
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Wizard Steps -->
                <div class="mb-8 flex justify-between max-w-2xl mx-auto">
                    <!-- ... Wizard steps code ... -->
                    <div class="flex items-center text-indigo-600 font-bold">
                        <span class="w-8 h-8 flex items-center justify-center border-2 border-indigo-600 rounded-lg mr-2">
                             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        </span>
                        <span>Empresa</span>
                    </div>
                    <div class="flex-1 border-t-2 border-gray-200 self-center mx-4"></div>
                    <div class="flex items-center text-gray-400">
                         <span class="w-8 h-8 flex items-center justify-center border-2 border-gray-200 rounded-lg mr-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </span>
                        <span>Certificado</span>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-8">
                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                            
                            <!-- Left: File Upload -->
                            <div class="lg:col-span-1">
                                <label for="rut-upload" class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center h-full flex flex-col justify-center items-center hover:border-indigo-500 transition cursor-pointer bg-gray-50 relative">
                                    <div v-if="processingFile" class="absolute inset-0 bg-white/80 flex items-center justify-center">
                                        <span class="text-indigo-600 font-bold animate-pulse">Analizando...</span>
                                    </div>
                                    <div class="mb-4 text-gray-400">
                                        <svg class="mx-auto h-12 w-12" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                    <p class="text-sm text-gray-600 font-medium">Subir Ficha RUT (PDF)</p>
                                    <p class="text-xs text-gray-400 mt-1">Arrastra tu archivo aquí o haz clic</p>
                                    <input id="rut-upload" type="file" class="hidden" accept="application/pdf" @change="handleFileUpload" />
                                </label>
                            </div>

                            <!-- Right: Form Fields -->
                            <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                                
                                <!-- Document -->
                                <div>
                                    <InputLabel value="Tipo de Documento" />
                                    <select v-model="form.type_document_identification_id" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1">
                                        <option value="">Seleccione...</option>
                                        <option v-for="item in documentTypes" :key="item.id" :value="item.id">
                                            {{ item.name }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.type_document_identification_id" class="mt-2" />
                                </div>
                                <div class="flex gap-2">
                                    <div class="flex-1">
                                        <InputLabel value="Número de documento" />
                                        <TextInput v-model="form.nit" type="text" class="w-full mt-1" required />
                                        <InputError :message="form.errors.nit" class="mt-2" />
                                    </div>
                                    <div class="w-16">
                                        <InputLabel value="DV" />
                                        <TextInput v-model="form.dv" type="text" class="w-full mt-1 text-center" />
                                        <InputError :message="form.errors.dv" class="mt-2" />
                                    </div>
                                </div>

                                <!-- Company Info -->
                                <div class="md:col-span-2">
                                    <InputLabel value="Empresa / Razón Social" />
                                    <TextInput v-model="form.name" type="text" class="w-full mt-1 bg-indigo-50" required />
                                    <InputError :message="form.errors.name" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel value="Registro Mercantil" />
                                    <TextInput v-model="form.merchant_registration" type="text" class="w-full mt-1" />
                                    <InputError :message="form.errors.merchant_registration" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel value="Teléfono" />
                                    <TextInput v-model="form.phone" type="text" class="w-full mt-1 bg-indigo-50" />
                                    <InputError :message="form.errors.phone" class="mt-2" />
                                </div>

                                <div class="md:col-span-2">
                                    <InputLabel value="Correo Electrónico" />
                                    <TextInput v-model="form.email" type="email" class="w-full mt-1 bg-indigo-50" />
                                    <InputError :message="form.errors.email" class="mt-2" />
                                </div>

                                <div class="md:col-span-2">
                                    <InputLabel value="Dirección" />
                                    <TextInput v-model="form.address" type="text" class="w-full mt-1 bg-indigo-50" />
                                    <InputError :message="form.errors.address" class="mt-2" />
                                </div>

                                <!-- Location (COMBOBOXES) -->
                                <div>
                                    <Combobox
                                        label="Departamento"
                                        v-model="selectedDepartment"
                                        :options="departments"
                                        placeholder="Buscar departamento..."
                                    />
                                </div>
                                <div>
                                    <Combobox
                                        label="Municipio"
                                        v-model="form.municipality_id"
                                        :options="filteredMunicipalities"
                                        placeholder="Buscar municipio..."
                                        :disabled="!selectedDepartment"
                                        :error="form.errors.municipality_id"
                                    />
                                </div>

                                <!-- Fiscal Info -->
                                <div>
                                    <InputLabel value="Tipo Responsabilidad" />
                                    <select v-model="form.type_liability_id" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1">
                                        <option value="">Seleccione...</option>
                                        <option v-for="item in liabilities" :key="item.id" :value="item.id">
                                            {{ item.name }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.type_liability_id" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel value="Organización" />
                                    <select v-model="form.type_organization_id" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1">
                                        <option value="">Seleccione...</option>
                                        <option v-for="item in organizations" :key="item.id" :value="item.id">
                                            {{ item.name }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.type_organization_id" class="mt-2" />
                                </div>
                                 <div class="md:col-span-2">
                                    <InputLabel value="Régimen" />
                                    <select v-model="form.type_regime_id" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1">
                                        <option value="">Seleccione...</option>
                                        <option v-for="item in regimes" :key="item.id" :value="item.id">
                                            {{ item.name }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.type_regime_id" class="mt-2" />
                                </div>

                                <!-- Distributor Select (Admin only) -->
                                <div v-if="distributors.length > 0" class="md:col-span-2 border-t pt-4 mt-2">
                                    <!-- Use combobox for distributor too? Maybe later if needed, list might be short -->
                                    <InputLabel value="Asignar a Distribuidor" class="font-bold text-gray-700" />
                                    <select v-model="form.distributor_id" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1">
                                        <option value="">Seleccione...</option>
                                        <option v-for="dist in distributors" :key="dist.id" :value="dist.id">
                                            {{ dist.name }}
                                        </option>
                                    </select>
                                </div>

                            </div>
                        </div>

                        <div class="flex items-center justify-center mt-8">
                            <PrimaryButton class="px-8 py-3 text-lg" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Siguiente
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

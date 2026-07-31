<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DialogModal from '@/Components/DialogModal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

defineProps({
    packages: Array,
    folioRates: Array,
    serviceRates: Array
});

const isModalOpen = ref(false);
const isEditMode = ref(false);
const currentPackage = ref(null);

const form = useForm({
    name: '',
    type: 'distributor',
    min_licenses: 1,
    max_licenses: 1,
    total_price: 0,
});

const openCreateModal = () => {
    form.reset();
    form.clearErrors();
    isEditMode.value = false;
    currentPackage.value = null;
    isModalOpen.value = true;
};

const openEditModal = (pkg) => {
    form.reset();
    form.clearErrors();
    form.name = pkg.name;
    form.type = pkg.type;
    form.min_licenses = pkg.min_licenses;
    form.max_licenses = pkg.max_licenses;
    form.total_price = pkg.total_price;
    isEditMode.value = true;
    currentPackage.value = pkg;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    setTimeout(() => form.reset(), 300);
};

const savePackage = () => {
    if (isEditMode.value) {
        form.put(route('license-packages.update', currentPackage.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('license-packages.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteForm = useForm({});
const deletePackage = (pkg) => {
    if (confirm('¿Está seguro de eliminar este paquete de precios?')) {
        deleteForm.delete(route('license-packages.destroy', pkg.id));
    }
};

// --- FOLIO RATES ---
const isFolioModalOpen = ref(false);
const isFolioEditMode = ref(false);
const currentFolio = ref(null);

const folioForm = useForm({
    min_folios: 0,
    max_folios: null,
    price: 0,
});

const openFolioModal = (rate = null) => {
    folioForm.reset();
    folioForm.clearErrors();
    if (rate) {
        isFolioEditMode.value = true;
        currentFolio.value = rate;
        folioForm.min_folios = rate.min_folios;
        folioForm.max_folios = rate.max_folios;
        folioForm.price = rate.price;
    } else {
        isFolioEditMode.value = false;
        currentFolio.value = null;
    }
    isFolioModalOpen.value = true;
};

const closeFolioModal = () => {
    isFolioModalOpen.value = false;
    setTimeout(() => folioForm.reset(), 300);
};

const saveFolio = () => {
    if (isFolioEditMode.value) {
        folioForm.put(route('folio-rates.update', currentFolio.value.id), { onSuccess: () => closeFolioModal() });
    } else {
        folioForm.post(route('folio-rates.store'), { onSuccess: () => closeFolioModal() });
    }
};

const deleteFolio = (rate) => {
    if (confirm('¿Eliminar tarifa de folios?')) {
        deleteForm.delete(route('folio-rates.destroy', rate.id));
    }
};

// --- SERVICE RATES ---
const isServiceModalOpen = ref(false);
const isServiceEditMode = ref(false);
const currentService = ref(null);

const serviceForm = useForm({
    name: '',
    annual_price: 0,
});

const openServiceModal = (rate = null) => {
    serviceForm.reset();
    serviceForm.clearErrors();
    if (rate) {
        isServiceEditMode.value = true;
        currentService.value = rate;
        serviceForm.name = rate.name;
        serviceForm.annual_price = rate.annual_price;
    } else {
        isServiceEditMode.value = false;
        currentService.value = null;
    }
    isServiceModalOpen.value = true;
};

const closeServiceModal = () => {
    isServiceModalOpen.value = false;
    setTimeout(() => serviceForm.reset(), 300);
};

const saveService = () => {
    if (isServiceEditMode.value) {
        serviceForm.put(route('service-rates.update', currentService.value.id), { onSuccess: () => closeServiceModal() });
    } else {
        serviceForm.post(route('service-rates.store'), { onSuccess: () => closeServiceModal() });
    }
};

const deleteService = (rate) => {
    if (confirm('¿Eliminar tarifa de servicio?')) {
        deleteForm.delete(route('service-rates.destroy', rate.id));
    }
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(value);
};
</script>

<template>
    <AppLayout title="Paquetes de Precios">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Configuración de Paquetes y Precios
                </h2>
                <PrimaryButton @click="openCreateModal">
                    Nuevo Paquete
                </PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Paquetes para Distribuidores</h3>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre / Rango</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor Total</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="pkg in packages.filter(p => p.type === 'distributor')" :key="pkg.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ pkg.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ pkg.min_licenses }} a {{ pkg.max_licenses }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-indigo-600">
                                        {{ formatCurrency(pkg.total_price) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="openEditModal(pkg)" class="text-indigo-600 hover:text-indigo-900 mr-4">Editar</button>
                                        <button @click="deletePackage(pkg)" class="text-red-600 hover:text-red-900">Eliminar</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Paquetes para Clientes Directos</h3>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre / Rango</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor Total</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="pkg in packages.filter(p => p.type === 'direct')" :key="pkg.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ pkg.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ pkg.min_licenses }} a {{ pkg.max_licenses }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-emerald-600">
                                        {{ formatCurrency(pkg.total_price) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="openEditModal(pkg)" class="text-indigo-600 hover:text-indigo-900 mr-4">Editar</button>
                                        <button @click="deletePackage(pkg)" class="text-red-600 hover:text-red-900">Eliminar</button>
                                    </td>
                                </tr>
                                <tr v-if="packages.filter(p => p.type === 'direct').length === 0">
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">No hay paquetes directos configurados.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Folio Rates -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-bold text-gray-800">Tarifas de Folios (Escalones)</h3>
                            <PrimaryButton @click="openFolioModal()" class="!bg-blue-600 hover:!bg-blue-700">Nueva Tarifa</PrimaryButton>
                        </div>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rango (Mín - Máx)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Precio Base (c/u o Fijo)</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="rate in folioRates" :key="'folio-'+rate.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ rate.min_folios }} a {{ rate.max_folios ? rate.max_folios : 'Ilimitados' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-blue-600">
                                        {{ formatCurrency(rate.price) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="openFolioModal(rate)" class="text-indigo-600 hover:text-indigo-900 mr-4">Editar</button>
                                        <button @click="deleteFolio(rate)" class="text-red-600 hover:text-red-900">Eliminar</button>
                                    </td>
                                </tr>
                                <tr v-if="!folioRates || folioRates.length === 0">
                                    <td colspan="3" class="px-6 py-4 text-center text-gray-500">No hay tarifas de folios configuradas.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Service Rates -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-bold text-gray-800">Servicios Adicionales Anuales</h3>
                            <PrimaryButton @click="openServiceModal()" class="!bg-teal-600 hover:!bg-teal-700">Nuevo Servicio</PrimaryButton>
                        </div>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Servicio</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="rate in serviceRates" :key="'srv-'+rate.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ rate.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="openServiceModal(rate)" class="text-indigo-600 hover:text-indigo-900 mr-4">Editar</button>
                                        <button @click="deleteService(rate)" class="text-red-600 hover:text-red-900">Eliminar</button>
                                    </td>
                                </tr>
                                <tr v-if="!serviceRates || serviceRates.length === 0">
                                    <td colspan="3" class="px-6 py-4 text-center text-gray-500">No hay servicios configurados.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <DialogModal :show="isModalOpen" @close="closeModal">
            <template #title>
                {{ isEditMode ? 'Editar Paquete' : 'Nuevo Paquete' }}
            </template>
            <template #content>
                <div class="space-y-4">
                    <div>
                        <InputLabel for="name" value="Nombre del Paquete (Ej: 2 a 3 Licencias)" />
                        <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" autofocus />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel value="Tipo de Tarifa" class="font-bold text-gray-700" />
                        <select v-model="form.type" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1">
                            <option value="distributor">Para Distribuidores</option>
                            <option value="direct">Para Clientes Directos</option>
                        </select>
                        <InputError :message="form.errors.type" class="mt-2" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="min_licenses" value="Min Licencias" />
                            <TextInput id="min_licenses" v-model="form.min_licenses" type="number" min="1" class="mt-1 block w-full" />
                            <InputError :message="form.errors.min_licenses" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="max_licenses" value="Max Licencias" />
                            <TextInput id="max_licenses" v-model="form.max_licenses" type="number" min="1" class="mt-1 block w-full" />
                            <InputError :message="form.errors.max_licenses" class="mt-2" />
                        </div>
                    </div>
                    <div>
                        <InputLabel for="total_price" value="Precio Total del Paquete" />
                        <TextInput id="total_price" v-model="form.total_price" type="number" step="0.01" class="mt-1 block w-full" />
                        <InputError :message="form.errors.total_price" class="mt-2" />
                    </div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="closeModal" class="mr-3">Cancelar</SecondaryButton>
                <PrimaryButton @click="savePackage" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Guardar
                </PrimaryButton>
            </template>
        </DialogModal>

        <!-- Folio Rate Modal -->
        <DialogModal :show="isFolioModalOpen" @close="closeFolioModal">
            <template #title>
                {{ isFolioEditMode ? 'Editar Tarifa de Folios' : 'Nueva Tarifa de Folios' }}
            </template>

            <template #content>
                <div class="space-y-4">
                    <div>
                        <InputLabel for="min_folios" value="Folios Mínimos" />
                        <TextInput id="min_folios" v-model="folioForm.min_folios" type="number" class="mt-1 block w-full" />
                        <InputError :message="folioForm.errors.min_folios" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="max_folios" value="Folios Máximos (Dejar en 0 o vacío para Ilimitados)" />
                        <TextInput id="max_folios" v-model="folioForm.max_folios" type="number" class="mt-1 block w-full" />
                        <InputError :message="folioForm.errors.max_folios" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="folio_price" value="Precio (Por unidad o Fijo si es Ilimitado)" />
                        <TextInput id="folio_price" v-model="folioForm.price" type="number" step="0.01" class="mt-1 block w-full" />
                        <InputError :message="folioForm.errors.price" class="mt-2" />
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeFolioModal" class="mr-3">Cancelar</SecondaryButton>
                <PrimaryButton @click="saveFolio" :disabled="folioForm.processing">Guardar</PrimaryButton>
            </template>
        </DialogModal>

        <!-- Service Rate Modal -->
        <DialogModal :show="isServiceModalOpen" @close="closeServiceModal">
            <template #title>
                {{ isServiceEditMode ? 'Editar Servicio Anual' : 'Nuevo Servicio Anual' }}
            </template>

            <template #content>
                <div class="space-y-4">
                    <div>
                        <InputLabel for="srv_name" value="Nombre del Servicio (Ej: Nube, Factus)" />
                        <TextInput id="srv_name" v-model="serviceForm.name" type="text" class="mt-1 block w-full" />
                        <InputError :message="serviceForm.errors.name" class="mt-2" />
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeServiceModal" class="mr-3">Cancelar</SecondaryButton>
                <PrimaryButton @click="saveService" :disabled="serviceForm.processing">Guardar</PrimaryButton>
            </template>
        </DialogModal>

    </AppLayout>
</template>

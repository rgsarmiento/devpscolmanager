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
import DangerButton from '@/Components/DangerButton.vue';

defineProps({
    distributors: Array
});

const isModalOpen = ref(false);
const isEditMode = ref(false);
const currentDistributor = ref(null);

const form = useForm({
    name: '',
    phone: '',
});

const openCreateModal = () => {
    form.reset();
    form.clearErrors();
    isEditMode.value = false;
    currentDistributor.value = null;
    isModalOpen.value = true;
};

const openEditModal = (distributor) => {
    form.reset();
    form.clearErrors();
    form.name = distributor.name;
    form.phone = distributor.phone;
    isEditMode.value = true;
    currentDistributor.value = distributor;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    setTimeout(() => form.reset(), 300);
};

const saveDistributor = () => {
    if (isEditMode.value) {
        form.put(route('distributors.update', currentDistributor.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('distributors.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteForm = useForm({});
const deleteDistributor = (distributor) => {
    if (confirm('¿Está seguro de eliminar este distribuidor? Se eliminarán todos sus clientes asociados.')) {
        deleteForm.delete(route('distributors.destroy', distributor.id));
    }
};
</script>

<template>
    <AppLayout title="Distribuidores">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Distribuidores
                </h2>
                <PrimaryButton @click="openCreateModal">
                    Nuevo Distribuidor
                </PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    
                    <div class="p-6">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teléfono</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Clientes</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="distributor in distributors" :key="distributor.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ distributor.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ distributor.phone || 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold text-indigo-600">
                                        {{ distributor.clients_count }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="openEditModal(distributor)" class="text-indigo-600 hover:text-indigo-900 mr-4">Editar</button>
                                        <button @click="deleteDistributor(distributor)" class="text-red-600 hover:text-red-900">Eliminar</button>
                                    </td>
                                </tr>
                                <tr v-if="distributors.length === 0">
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">No hay distribuidores registrados.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

        <DialogModal :show="isModalOpen" @close="closeModal">
            <template #title>
                {{ isEditMode ? 'Editar Distribuidor' : 'Nuevo Distribuidor' }}
            </template>
            <template #content>
                <div class="space-y-4">
                    <div>
                        <InputLabel for="name" value="Nombre" />
                        <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" autofocus />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="phone" value="Teléfono" />
                        <TextInput id="phone" v-model="form.phone" type="text" class="mt-1 block w-full" />
                        <InputError :message="form.errors.phone" class="mt-2" />
                    </div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="closeModal" class="mr-3">Cancelar</SecondaryButton>
                <PrimaryButton @click="saveDistributor" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Guardar
                </PrimaryButton>
            </template>
        </DialogModal>
    </AppLayout>
</template>

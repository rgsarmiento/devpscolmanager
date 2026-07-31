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

const props = defineProps({
    users: Array,
    distributors: Array,
});

const isModalOpen = ref(false);
const isEditMode = ref(false);
const currentUser = ref(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    distributor_id: '',
});

const openCreateModal = () => {
    form.reset();
    form.clearErrors();
    isEditMode.value = false;
    currentUser.value = null;
    isModalOpen.value = true;
};

const openEditModal = (user) => {
    form.reset();
    form.clearErrors();
    form.name = user.name;
    form.email = user.email;
    form.password = '';
    form.distributor_id = user.distributor_id || '';
    isEditMode.value = true;
    currentUser.value = user;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    setTimeout(() => form.reset(), 300);
};

const saveUser = () => {
    if (isEditMode.value) {
        form.put(route('users.update', currentUser.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('users.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteForm = useForm({});
const deleteUser = (user) => {
    if (confirm('¿Está seguro de eliminar este usuario?')) {
        deleteForm.delete(route('users.destroy', user.id));
    }
};
</script>

<template>
    <AppLayout title="Gestión de Usuarios">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Gestión de Usuarios
                </h2>
                <PrimaryButton @click="openCreateModal">
                    Nuevo Usuario
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Rol</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Distribuidor</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="user in users" :key="user.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ user.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ user.email }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                        <span v-if="!user.distributor_id" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800">
                                            Administrador
                                        </span>
                                        <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-emerald-100 text-emerald-800">
                                            Distribuidor
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                        {{ user.distributor ? user.distributor.name : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium flex justify-end gap-2">
                                        <SecondaryButton @click="openEditModal(user)" class="!py-1 !px-2 text-xs">Editar</SecondaryButton>
                                        <DangerButton @click="deleteUser(user)" class="!py-1 !px-2 text-xs" v-if="$page.props.auth.user.id !== user.id">Eliminar</DangerButton>
                                    </td>
                                </tr>
                                <tr v-if="!users.length">
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500 italic">No hay usuarios registrados.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Crear/Editar -->
        <DialogModal :show="isModalOpen" @close="closeModal">
            <template #title>
                {{ isEditMode ? 'Editar Usuario' : 'Crear Nuevo Usuario' }}
            </template>

            <template #content>
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <InputLabel value="Nombre" />
                        <TextInput v-model="form.name" type="text" class="mt-1 block w-full" required autofocus />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel value="Correo Electrónico" />
                        <TextInput v-model="form.email" type="email" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.email" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel :value="isEditMode ? 'Contraseña (Dejar en blanco para no cambiar)' : 'Contraseña'" />
                        <TextInput v-model="form.password" type="password" class="mt-1 block w-full" :required="!isEditMode" />
                        <InputError :message="form.errors.password" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel value="Asignar a Distribuidor" />
                        <select v-model="form.distributor_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">-- Es Administrador (Acceso Total) --</option>
                            <option v-for="dist in distributors" :key="dist.id" :value="dist.id">
                                {{ dist.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.distributor_id" class="mt-2" />
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeModal" class="mr-3">
                    Cancelar
                </SecondaryButton>
                <PrimaryButton @click="saveUser" :disabled="form.processing">
                    {{ isEditMode ? 'Guardar Cambios' : 'Crear Usuario' }}
                </PrimaryButton>
            </template>
        </DialogModal>
    </AppLayout>
</template>

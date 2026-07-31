const fs = require('fs');

let content = fs.readFileSync('resources/js/Pages/Clients/Show.vue', 'utf8');

// 1. Add planForm definition
const searchForm = `const companyForm = useForm({`;
const replaceForm = `const planForm = useForm({
    plan_documents: props.client.invoicing_info?.plan_documents || '',
    plan_start_date: props.client.invoicing_info?.plan_start_date ? props.client.invoicing_info.plan_start_date.split('.')[0].replace(' ', 'T') : '',
});

const submitPlanUpdate = () => {
    planForm.post(route('invoicing.update-plan', props.client.id), {
        preserveScroll: true,
        onSuccess: () => {
            showToast('Plan actualizado directamente con éxito', 'success');
        },
        onError: () => {
            showToast('Error al actualizar el plan', 'danger');
        }
    });
};

const companyForm = useForm({`;

if (!content.includes('const planForm = useForm')) {
    content = content.replace(searchForm, replaceForm);
}

// 2. Change the v-models in the template from companyForm to planForm and add the button
const searchTemplate = `<InputLabel value="Cantidad de Folios (Plan)" />
                                                        <TextInput v-model="companyForm.plan_documents" type="number" class="w-full text-sm" placeholder="Ej: 1500" />
                                                    </div>
                                                    <div>
                                                        <InputLabel value="Fecha Inicio del Plan" />
                                                        <TextInput v-model="companyForm.plan_start_date" type="datetime-local" step="1" class="w-full text-sm" />
                                                    </div>`;

const replaceTemplate = `<InputLabel value="Cantidad de Folios (Plan)" />
                                                        <TextInput v-model="planForm.plan_documents" type="number" class="w-full text-sm" placeholder="Ej: 1500" />
                                                    </div>
                                                    <div>
                                                        <InputLabel value="Fecha Inicio del Plan" />
                                                        <TextInput v-model="planForm.plan_start_date" type="datetime-local" step="1" class="w-full text-sm" />
                                                    </div>
                                                    <div class="col-span-2 text-right mt-2">
                                                        <PrimaryButton type="button" @click.prevent="submitPlanUpdate" :class="{ 'opacity-25': planForm.processing }" :disabled="planForm.processing">
                                                            Actualizar Plan
                                                        </PrimaryButton>
                                                    </div>`;

content = content.replace(searchTemplate, replaceTemplate);

fs.writeFileSync('resources/js/Pages/Clients/Show.vue', content);
console.log('Updated Show.vue with planForm and submit button');

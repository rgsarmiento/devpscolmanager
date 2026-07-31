const fs = require('fs');

let content = fs.readFileSync('resources/js/Pages/Clients/Show.vue', 'utf8');

// 1. Replace fetchResolutions and add reactive state
const fetchSearch = `const fetchResolutions = () => {`;
const fetchReplace = `const fetchedResolutions = ref([]);
const showResolutionSelector = ref(false);

const selectResolution = (res) => {
    resolutionForm.prefix = res.Prefix || '';
    resolutionForm.resolution = res.ResolutionNumber || '';
    resolutionForm.resolution_date = res.ValidDateFrom || '';
    resolutionForm.date_from = res.ValidDateFrom || '';
    resolutionForm.date_to = res.ValidDateTo || '';
    resolutionForm.from = res.FromNumber || '';
    resolutionForm.to = res.ToNumber || '';
    
    // Sometimes TechnicalKey is an object when it's nil
    if (res.TechnicalKey && typeof res.TechnicalKey === 'object') {
        resolutionForm.technical_key = '';
    } else {
        resolutionForm.technical_key = res.TechnicalKey || '';
    }
    
    showResolutionSelector.value = false;
    usePage().props.flash.flash = { bannerStyle: 'success', banner: 'Resolución cargada correctamente.' };
};

const fetchResolutions = () => {
    if (!props.client.invoicing_info?.api_token) {
        usePage().props.flash.flash = { bannerStyle: 'danger', banner: 'Falta Token API' };
        return;
    }
    
    axios.post(route('invoicing.numbering-range', props.client.id))
        .then(response => {
            const data = response.data;
            try {
                // Navigate the nested JSON structure
                const result = data.ResponseDian.Envelope.Body.GetNumberingRangeResponse.GetNumberingRangeResult;
                
                if (result.OperationCode !== "100") {
                    usePage().props.flash.flash = { bannerStyle: 'danger', banner: result.OperationDescription || 'Error en la respuesta de la DIAN' };
                    return;
                }
                
                let ranges = result.ResponseList?.NumberRangeResponse;
                
                if (!ranges) {
                    usePage().props.flash.flash = { bannerStyle: 'warning', banner: 'No se encontraron resoluciones para este software ID.' };
                    return;
                }
                
                // Normalize to array (if single object returned by XML parser)
                if (!Array.isArray(ranges)) {
                    ranges = [ranges];
                }
                
                if (ranges.length === 1) {
                    selectResolution(ranges[0]);
                } else if (ranges.length > 1) {
                    fetchedResolutions.value = ranges;
                    showResolutionSelector.value = true;
                    usePage().props.flash.flash = { bannerStyle: 'success', banner: 'Se encontraron múltiples resoluciones.' };
                } else {
                    usePage().props.flash.flash = { bannerStyle: 'warning', banner: 'No se encontraron resoluciones.' };
                }
                
            } catch (e) {
                console.error("Parse error:", e);
                usePage().props.flash.flash = { bannerStyle: 'danger', banner: 'La respuesta de la DIAN no tiene el formato esperado.' };
            }
        })
        .catch(error => {
            console.error(error);
            usePage().props.flash.flash = { bannerStyle: 'danger', banner: error.response?.data?.error || 'Error de conexión al consultar la DIAN.' };
        });
};`;

// We need to replace the entire old fetchResolutions block.
// It ends right before `const certificateForm = useForm({`
const fetchRegex = /const fetchResolutions = \(\) => \{[\s\S]*?\};\n*(?=const certificateForm = useForm\()/;
content = content.replace(fetchRegex, fetchReplace + "\n\n");

// 2. Add DialogModal before <!-- Modal for Computer -->
const modalSearch = `<!-- Modal for Computer -->`;
const modalReplace = `<!-- Modal for Resolutions Selection -->
        <DialogModal :show="showResolutionSelector" @close="showResolutionSelector = false">
            <template #title>
                Seleccionar Resolución
            </template>
            <template #content>
                <div class="space-y-4 mt-4">
                    <p class="text-sm text-gray-600">Se encontraron múltiples resoluciones asociadas a este Software ID. Selecciona una para autocompletar el formulario:</p>
                    <div class="grid grid-cols-1 gap-3 max-h-96 overflow-y-auto pr-2">
                        <button 
                            v-for="(res, index) in fetchedResolutions" 
                            :key="index"
                            @click="selectResolution(res)"
                            class="flex flex-col text-left p-4 rounded-xl border border-gray-200 hover:border-indigo-500 hover:bg-indigo-50 transition-all focus:outline-none"
                        >
                            <div class="flex justify-between items-center w-full">
                                <span class="font-bold text-slate-800">{{ res.Prefix || 'SIN PREFIJO' }} - {{ res.ResolutionNumber }}</span>
                                <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-1 rounded-md font-mono">{{ res.ValidDateFrom }}</span>
                            </div>
                            <div class="text-xs text-gray-500 mt-2 flex justify-between">
                                <span>Rango: {{ res.FromNumber }} a {{ res.ToNumber }}</span>
                                <span>Vence: {{ res.ValidDateTo }}</span>
                            </div>
                        </button>
                    </div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="showResolutionSelector = false">
                    Cancelar
                </SecondaryButton>
            </template>
        </DialogModal>

        <!-- Modal for Computer -->`;

content = content.replace(modalSearch, modalReplace);

fs.writeFileSync('resources/js/Pages/Clients/Show.vue', content);
console.log('Show.vue updated with multi-resolution support');

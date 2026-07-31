const fs = require('fs');

let content = fs.readFileSync('resources/js/Pages/Clients/Show.vue', 'utf8');

// 1. Add searchResolution ref and filteredResolutions computed property
const searchStr = `const fetchedResolutions = ref([]);
const showResolutionSelector = ref(false);`;

const replaceStr = `const fetchedResolutions = ref([]);
const showResolutionSelector = ref(false);
const searchResolution = ref('');

const filteredResolutions = computed(() => {
    if (!props.client.resolutions) return [];
    if (!searchResolution.value) return props.client.resolutions;
    
    const query = searchResolution.value.toLowerCase();
    return props.client.resolutions.filter(res => {
        const prefix = res.prefix ? res.prefix.toLowerCase() : '';
        const resolution = res.resolution ? res.resolution.toLowerCase() : '';
        return prefix.includes(query) || resolution.includes(query);
    });
});`;

content = content.replace(searchStr, replaceStr);

// 2. Update the Resolutions List UI
const searchListStr = `<h5 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4">Resoluciones Guardadas Localmente</h5>
                                            <div class="space-y-3 max-h-64 overflow-y-auto pr-2">
                                                <div v-for="res in client.resolutions" :key="res.id" class="bg-white border border-gray-200 rounded-lg p-4 flex justify-between items-center shadow-sm">`;

const replaceListStr = `<div class="flex justify-between items-center mb-4">
                                                <h5 class="text-xs font-bold text-gray-500 uppercase tracking-widest">Resoluciones Guardadas Localmente</h5>
                                                <div class="relative w-64">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                        </svg>
                                                    </div>
                                                    <input 
                                                        v-model="searchResolution"
                                                        type="text" 
                                                        placeholder="Buscar prefijo o número..." 
                                                        class="pl-9 w-full border-gray-200 rounded-lg text-sm focus:ring-indigo-500 shadow-sm"
                                                    />
                                                </div>
                                            </div>
                                            
                                            <div v-if="filteredResolutions.length === 0" class="text-center py-6 text-sm text-gray-500 bg-gray-50 rounded-lg">
                                                No se encontraron resoluciones con esa búsqueda.
                                            </div>
                                            
                                            <div v-else class="space-y-3 max-h-[500px] overflow-y-auto pr-2 custom-scrollbar">
                                                <div v-for="res in filteredResolutions" :key="res.id" class="bg-white border border-gray-200 hover:border-indigo-300 transition-colors rounded-lg p-4 flex justify-between items-center shadow-sm">`;

content = content.replace(searchListStr, replaceListStr);

fs.writeFileSync('resources/js/Pages/Clients/Show.vue', content);
console.log('Update applied');

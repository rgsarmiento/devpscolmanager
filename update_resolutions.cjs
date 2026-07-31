const fs = require('fs');

let content = fs.readFileSync('resources/js/Pages/Clients/Show.vue', 'utf8');

// 1. Add setEnvPruebas function
const refPruebasStr = `const resolutionEnv = ref('pruebas');`;
const setEnvPruebasFunc = `const resolutionEnv = ref('pruebas');

const setEnvPruebas = () => {
    resolutionEnv.value = 'pruebas';
    resolutionForm.prefix = "SETP";
    resolutionForm.resolution = "18760000001";
    resolutionForm.resolution_date = "2019-01-19";
    resolutionForm.technical_key = "fc8eac422eba16e22ffd8c6f94b3f40a6e38162c";
    resolutionForm.from = "990000000";
    resolutionForm.to = "995000000";
    resolutionForm.date_from = "2019-01-19";
    resolutionForm.date_to = "2030-01-19";
};`;

if (!content.includes('const setEnvPruebas = () => {')) {
    content = content.replace(refPruebasStr, setEnvPruebasFunc);
}

// 2. Modify Entorno Switch and Consultar DIAN button
const switchOld = `<div class="flex items-center justify-center gap-4 mb-6 pb-6 border-b border-gray-200">
                                            <button 
                                                type="button"
                                                @click="resolutionEnv = 'pruebas'"
                                                class="px-6 py-2 rounded-full text-sm font-bold transition-all"
                                                :class="resolutionEnv === 'pruebas' ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-200 text-gray-500 hover:bg-gray-300'"
                                            >
                                                Pruebas (SETI)
                                            </button>
                                            <button 
                                                type="button"
                                                @click="resolutionEnv = 'produccion'"
                                                class="px-6 py-2 rounded-full text-sm font-bold transition-all"
                                                :class="resolutionEnv === 'produccion' ? 'bg-emerald-600 text-white shadow-md' : 'bg-gray-200 text-gray-500 hover:bg-gray-300'"
                                            >
                                                Producción
                                            </button>
                                        </div>`;
                                        
const switchNew = `<div class="flex items-center justify-center gap-4 mb-6 pb-6 border-b border-gray-200">
                                            <button 
                                                type="button"
                                                @click="setEnvPruebas"
                                                class="px-6 py-2 rounded-full text-sm font-bold transition-all"
                                                :class="resolutionEnv === 'pruebas' ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-200 text-gray-500 hover:bg-gray-300'"
                                            >
                                                Pruebas (SETI)
                                            </button>
                                            <button 
                                                type="button"
                                                @click="resolutionEnv = 'produccion'"
                                                class="px-6 py-2 rounded-full text-sm font-bold transition-all"
                                                :class="resolutionEnv === 'produccion' ? 'bg-emerald-600 text-white shadow-md' : 'bg-gray-200 text-gray-500 hover:bg-gray-300'"
                                            >
                                                Producción
                                            </button>
                                            <button 
                                                v-if="resolutionEnv === 'produccion'"
                                                type="button" 
                                                @click="fetchResolutions" 
                                                class="px-4 py-2 rounded-full text-sm font-bold bg-indigo-100 text-indigo-700 hover:bg-indigo-200 transition-all flex items-center"
                                                title="Consultar resoluciones en la DIAN"
                                            >
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                Consultar DIAN
                                            </button>
                                        </div>`;

content = content.replace(switchOld, switchNew);

// 3. Update all v-if="resolutionEnv === 'produccion'" in the grid to be visible always but disabled when pruebas
// I will use regex to replace all `v-if="resolutionEnv === 'produccion'"` with `:disabled="resolutionEnv === 'pruebas'"` on the TextInputs.
// But wait, the v-if is on the wrapper div, so if I remove v-if on the div, I need to add `:disabled` on the `<TextInput>`.

// Remove v-if from the divs
content = content.replace(/<div v-if="resolutionEnv === 'produccion'"/g, '<div');
content = content.replace(/:required="resolutionEnv === 'produccion'"/g, ':disabled="resolutionEnv === \'pruebas\'" :required="resolutionEnv === \'produccion\'"');

// 4. Remove the old Consultar DIAN button from the bottom
const oldConsultarDian = `<button type="button" @click="fetchResolutions" class="text-sm text-indigo-600 hover:text-indigo-800 font-bold flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                    Consultar DIAN
                                                </button>`;
                                                
content = content.replace(oldConsultarDian, '<div></div>');

fs.writeFileSync('resources/js/Pages/Clients/Show.vue', content);
console.log('Update applied');

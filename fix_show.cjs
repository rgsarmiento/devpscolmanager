const fs = require('fs');

let content = fs.readFileSync('resources/js/Pages/Clients/Show.vue', 'utf8');

// Find where STEP 4 header ends
const searchStr = `<h4 class="text-sm font-bold text-slate-800 uppercase tracking-widest">Firma Digital (.p12)</h4>
                                    </div>`;

const index = content.indexOf(searchStr);
if (index === -1) {
    console.error("Could not find STEP 4 header");
    process.exit(1);
}

const replacement = `<h4 class="text-sm font-bold text-slate-800 uppercase tracking-widest">Firma Digital (.p12)</h4>
                                    </div>
                                    <form @submit.prevent="submitCertificateConfig" class="bg-gray-50 p-6 rounded-2xl border border-gray-100 space-y-4">
                                        <div>
                                            <InputLabel value="Archivo .p12" />
                                            <input type="file" @input="certificateForm.certificate = $event.target.files[0]" accept=".p12" class="w-full text-sm mt-1 block border border-gray-300 rounded p-1" />
                                        </div>
                                        <div>
                                            <InputLabel value="Contraseña del Certificado" />
                                            <TextInput v-model="certificateForm.password" type="password" class="w-full text-sm" />
                                        </div>
                                        <PrimaryButton :disabled="certificateForm.processing || !client.invoicing_info?.api_token" class="w-full !justify-center">Cargar Certificado</PrimaryButton>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- STEP 3 (Resolutions) -->
                            <div class="space-y-12" :class="{ 'opacity-40 select-none grayscale': !client.invoicing_info?.api_token }">
                                <div>
                                    <div class="flex items-center mb-6">
                                        <span class="bg-indigo-600 text-white w-8 h-8 rounded-full flex items-center justify-center font-bold mr-3">4</span>
                                        <h4 class="text-sm font-bold text-slate-800 uppercase tracking-widest">Resolución Autorizada</h4>
                                    </div>
                                    
                                    <div class="bg-gray-50 p-6 rounded-2xl border border-gray-100 space-y-4">
                                        <!-- Entorno Switch -->
                                        <div class="flex items-center justify-center gap-4 mb-6 pb-6 border-b border-gray-200">
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
                                        </div>

                                        <form @submit.prevent="submitResolutionConfig" class="space-y-4">
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="col-span-2">
                                                    <InputLabel value="Tipo de Documento" />
                                                    <select v-model="resolutionForm.type_document_id" class="w-full border-gray-200 rounded-lg text-sm focus:ring-indigo-500 mt-1">
                                                        <option v-for="item in catalogs.document_types" :key="item.id" :value="item.id">{{ item.name }}</option>
                                                    </select>
                                                </div>
                                                
                                                <div v-if="resolutionEnv === 'produccion'" class="col-span-2 md:col-span-1">
                                                    <InputLabel value="Prefijo" />
                                                    <TextInput v-model="resolutionForm.prefix" class="w-full text-sm" :required="resolutionEnv === 'produccion'" />
                                                </div>
                                                <div v-if="resolutionEnv === 'produccion'" class="col-span-2 md:col-span-1">
                                                    <InputLabel value="Número de Resolución" />
                                                    <TextInput v-model="resolutionForm.resolution" class="w-full text-sm" :required="resolutionEnv === 'produccion'" />
                                                </div>
                                                
                                                <div v-if="resolutionEnv === 'produccion'" class="col-span-2 md:col-span-1">
                                                    <InputLabel value="Fecha Resolución" />
                                                    <TextInput v-model="resolutionForm.resolution_date" type="date" class="w-full text-sm" :required="resolutionEnv === 'produccion'" />
                                                </div>
                                                
                                                <div v-if="resolutionEnv === 'produccion'" class="col-span-2 md:col-span-1">
                                                    <InputLabel value="Válido Desde" />
                                                    <TextInput v-model="resolutionForm.date_from" type="date" class="w-full text-sm" :required="resolutionEnv === 'produccion'" />
                                                </div>
                                                <div v-if="resolutionEnv === 'produccion'" class="col-span-2 md:col-span-1">
                                                    <InputLabel value="Válido Hasta" />
                                                    <TextInput v-model="resolutionForm.date_to" type="date" class="w-full text-sm" :required="resolutionEnv === 'produccion'" />
                                                </div>
                                                
                                                <div v-if="resolutionEnv === 'produccion'" class="col-span-2 md:col-span-1">
                                                    <InputLabel value="Rango Desde" />
                                                    <TextInput v-model="resolutionForm.from" type="number" class="w-full text-sm" :required="resolutionEnv === 'produccion'" />
                                                </div>
                                                <div v-if="resolutionEnv === 'produccion'" class="col-span-2 md:col-span-1">
                                                    <InputLabel value="Rango Hasta" />
                                                    <TextInput v-model="resolutionForm.to" type="number" class="w-full text-sm" :required="resolutionEnv === 'produccion'" />
                                                </div>
                                                
                                                <div class="col-span-2">
                                                    <InputLabel value="Clave Técnica (Technical Key)" />
                                                    <TextInput v-model="resolutionForm.technical_key" class="w-full text-sm font-mono text-xs" :required="resolutionEnv === 'produccion'" />
                                                </div>
                                            </div>
                                            
                                            <div class="pt-4 border-t border-gray-200 flex justify-between items-center">
                                                <button type="button" @click="fetchResolutions" class="text-sm text-indigo-600 hover:text-indigo-800 font-bold flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                    Consultar DIAN
                                                </button>
                                                <PrimaryButton :disabled="resolutionForm.processing || !client.invoicing_info?.api_token" class="!bg-indigo-600">
                                                    Guardar Resolución
                                                </PrimaryButton>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50 border-t border-gray-100 px-6 py-4">
                             <a href="#config-facturacion" class="text-indigo-600 font-bold text-xs uppercase tracking-widest hover:text-indigo-800 transition block text-center">Ir a Configuración →</a>
                        </div>
                    </div>
                </div>`;

const endIndex = index + searchStr.length;
// We need to remove the garbage `                </div>                </div>\n\n            </div>\n        </div>` 
// that is currently at the end of the step 4 block.
// Let's find the `<!-- Modal for Computer -->` string
const modalIndex = content.indexOf('<!-- Modal for Computer -->');

if (modalIndex === -1) {
    console.error("Could not find Modal for Computer");
    process.exit(1);
}

// Replace everything between the end of searchStr and modalIndex with `replacement` and double newline.
const newContent = content.substring(0, index) + replacement + "\n\n        " + content.substring(modalIndex);

fs.writeFileSync('resources/js/Pages/Clients/Show.vue', newContent);
console.log("Fixed Show.vue successfully.");

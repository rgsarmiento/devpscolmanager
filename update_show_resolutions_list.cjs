const fs = require('fs');

let content = fs.readFileSync('resources/js/Pages/Clients/Show.vue', 'utf8');

// 1. Update the sorting and comparison logic in fetchResolutions
const searchFetch = `                if (!Array.isArray(ranges)) {
                    ranges = [ranges];
                }`;
const replaceFetch = `                if (!Array.isArray(ranges)) {
                    ranges = [ranges];
                }
                
                // Compare with local resolutions and sort
                ranges = ranges.map(res => {
                    const isConfigured = props.client.resolutions?.some(localRes => 
                        localRes.prefix === (res.Prefix || null) && 
                        localRes.resolution === res.ResolutionNumber
                    );
                    return {
                        ...res,
                        isConfigured
                    };
                });
                
                ranges.sort((a, b) => {
                    if (a.isConfigured === b.isConfigured) return 0;
                    return a.isConfigured ? 1 : -1;
                });`;

content = content.replace(searchFetch, replaceFetch);

// 2. Update DialogModal to show the badge
const searchModal = `<span class="font-bold text-slate-800">{{ res.Prefix || 'SIN PREFIJO' }} - {{ res.ResolutionNumber }}</span>`;
const replaceModal = `<span class="font-bold text-slate-800">{{ res.Prefix || 'SIN PREFIJO' }} - {{ res.ResolutionNumber }}</span>
                                <span v-if="res.isConfigured" class="text-[10px] bg-emerald-100 text-emerald-700 px-2 py-1 rounded-full font-bold uppercase ml-2">Ya Configurada</span>`;

content = content.replace(searchModal, replaceModal);

// 3. Add the list of configured resolutions below the form
const searchFormEnd = `</form>
                                    </div>
                                </div>
                            </div>
                        </div>`;
const replaceFormEnd = `</form>

                                        <!-- Resoluciones Guardadas Localmente -->
                                        <div class="mt-8 border-t border-gray-200 pt-6" v-if="client.resolutions && client.resolutions.length > 0">
                                            <h5 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4">Resoluciones Guardadas Localmente</h5>
                                            <div class="space-y-3 max-h-64 overflow-y-auto pr-2">
                                                <div v-for="res in client.resolutions" :key="res.id" class="bg-white border border-gray-200 rounded-lg p-4 flex justify-between items-center shadow-sm">
                                                    <div>
                                                        <div class="flex items-center gap-2">
                                                            <span class="font-bold text-slate-800">{{ res.prefix || 'SIN PREFIJO' }} - {{ res.resolution }}</span>
                                                            <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded-full" :class="res.environment === 'produccion' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600'">{{ res.environment }}</span>
                                                        </div>
                                                        <div class="text-xs text-gray-500 mt-1">
                                                            Vigencia: {{ res.date_from }} a {{ res.date_to }} &bull; Rango: {{ res.from }} a {{ res.to }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>`;

content = content.replace(searchFormEnd, replaceFormEnd);

fs.writeFileSync('resources/js/Pages/Clients/Show.vue', content);
console.log('Updated Show.vue with local resolutions view and sorting');

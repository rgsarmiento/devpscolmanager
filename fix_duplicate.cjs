const fs = require('fs');

let content = fs.readFileSync('resources/js/Pages/Dashboard.vue', 'utf8');

// 1. Remove the first Recent Clients List (the one inside the grid/space-y-8 container)
// It starts with `<!-- Recent Clients List -->` at line 373
const firstRecentRegex = /<!-- Recent Clients List -->\s*<div class="lg:col-span-1 bg-white overflow-hidden shadow-xl sm:rounded-lg h-fit">[\s\S]*?<\/div>\s*<\/div>\s*<\/div>\s*<!-- Consumo Global de Folios \(Top 5 Críticos\) -->/;

content = content.replace(firstRecentRegex, '</div>\n\n            <!-- Consumo Global de Folios (Top 5 Críticos) -->');

// 2. Fix the second Recent Clients List (at the bottom)
// The user said "con el estilo de la q aparece por encima de Consumo Crítico de Folios"
// Actually, they probably meant the nice gradient headers like Consumo Crítico, or just full width?
// "con el estilo de la q aparece por encima de Consumo Crítico de Folios"
// Above Consumo Critico is "Servicios Adicionales Próximos a Vencer" which has:
// <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mt-6">
// <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center"> ...
// Both recent clients look identical right now (except one is wrapped in mt-8).
// Maybe I just need to remove the first one, and the bottom one is already fine.
// But let's make sure the bottom one doesn't have `<!-- Recent Clients List -->` twice.

const secondRecentRegex = /<!-- Recent Clients List -->\s*<div class="mt-8">\s*<!-- Recent Clients List -->\s*<div class="bg-white overflow-hidden shadow-xl sm:rounded-lg h-fit">([\s\S]*?)<\/div>\s*<\/div>\s*<\/div>\s*<\/AppLayout>/;

content = content.replace(secondRecentRegex, `<!-- Recent Clients List -->
            <div class="mt-8 bg-white overflow-hidden shadow-xl sm:rounded-lg h-fit">
$1
            </div>

        </div>
    </AppLayout>`);


fs.writeFileSync('resources/js/Pages/Dashboard.vue', content);
console.log('Removed duplicate and fixed layout');

const fs = require('fs');

let content = fs.readFileSync('resources/js/Pages/Clients/Show.vue', 'utf8');

const searchStr = `const setEnvPruebas = () => {`;
const replaceStr = `const setEnvProduccion = () => {
    resolutionEnv.value = 'produccion';
    resolutionForm.prefix = "";
    resolutionForm.resolution = "";
    resolutionForm.resolution_date = "";
    resolutionForm.technical_key = "";
    resolutionForm.from = "";
    resolutionForm.to = "";
    resolutionForm.date_from = "";
    resolutionForm.date_to = "";
};

const setEnvPruebas = () => {`;

if (!content.includes('const setEnvProduccion')) {
    content = content.replace(searchStr, replaceStr);
}

const oldButton = `@click="resolutionEnv = 'produccion'"`;
const newButton = `@click="setEnvProduccion"`;

content = content.replace(oldButton, newButton);

fs.writeFileSync('resources/js/Pages/Clients/Show.vue', content);
console.log('Update applied');

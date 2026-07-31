const fs = require('fs');

let content = fs.readFileSync('resources/js/Pages/Clients/Show.vue', 'utf8');

// Add the showToast function right after page = usePage() or at the top of the script
const searchToast = `const clientServicesForm = useForm({`;
const replaceToast = `
const showToast = (message, style = 'success') => {
    if (!usePage().props.jetstream) usePage().props.jetstream = {};
    usePage().props.jetstream.flash = { banner: message, bannerStyle: style };
};

const clientServicesForm = useForm({`;

if (!content.includes('const showToast = (message, style')) {
    content = content.replace(searchToast, replaceToast);
}

// Replace all alerts
content = content.replace(/alert\('Por favor ingrese el PIN y la Fecha de Vencimiento primero\.'\);/g, "showToast('Por favor ingrese el PIN y la Fecha de Vencimiento primero.', 'warning');");
content = content.replace(/alert\('Error al generar la licencia\. Verifique los datos\.'\);/g, "showToast('Error al generar la licencia. Verifique los datos.', 'danger');");
content = content.replace(/alert\('¡Datos de licencia copiados!'\);/g, "showToast('¡Datos de licencia copiados!', 'success');");
content = content.replace(/alert\('Error al copiar'\);/g, "showToast('Error al copiar', 'danger');");
content = content.replace(/alert\('El cliente no tiene un teléfono registrado\.'\);/g, "showToast('El cliente no tiene un teléfono registrado.', 'warning');");
content = content.replace(/if\(successful\) alert\('Token copiado al portapapeles'\);/g, "if(successful) showToast('Token copiado al portapapeles', 'success');");
content = content.replace(/alert\('Token copiado al portapapeles'\);/g, "showToast('Token copiado al portapapeles', 'success');");

// Replace the manual usePage().props.flash.flash
content = content.replace(/usePage\(\)\.props\.flash\.flash = \{ bannerStyle: '(.*?)', banner: '(.*?)' \};/g, "showToast('$2', '$1');");

fs.writeFileSync('resources/js/Pages/Clients/Show.vue', content);
console.log('Replaced alerts with toasts');

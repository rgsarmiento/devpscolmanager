const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'resources', 'js', 'Pages', 'Clients', 'Show.vue');
let content = fs.readFileSync(filePath, 'utf8');

const startMarker = 'const generateLicense = async () => {';
const endMarker = '// --- Invoicing Config ---';

const startIdx = content.indexOf(startMarker);
const endIdx = content.lastIndexOf(endMarker);

if (startIdx === -1) {
    console.error('Start marker not found');
    process.exit(1);
}

if (endIdx === -1) {
    console.error('End marker not found');
    process.exit(1);
}

if (startIdx >= endIdx) {
    console.error('Start index is after end index');
    process.exit(1);
}

const correctCode = `const generateLicense = async () => {
    if (!computerForm.pin || !computerForm.expiration_date) {
        showToast('Por favor ingrese el PIN y la Fecha de Vencimiento primero.', 'warning');
        return;
    }
    try {
        const response = await axios.post(route('computers.generate-license'), {
            pin: computerForm.pin,
            expiration_date: computerForm.expiration_date
        });
        if (response.data && response.data.license_key) {
            computerForm.license_key = response.data.license_key;
        }
    } catch (error) {
        console.error(error);
        showToast('Error al generar la licencia. Verifique los datos.', 'danger');
    }
};

const setNextYear = () => {
    let baseDate = new Date();
    if (computerForm.expiration_date) {
        const parts = computerForm.expiration_date.split('-');
        if (parts.length === 3) {
            baseDate = new Date(parts[0], parts[1] - 1, parts[2]);
        }
    }
    baseDate.setFullYear(baseDate.getFullYear() + 1);
    const y = baseDate.getFullYear();
    const m = String(baseDate.getMonth() + 1).padStart(2, '0');
    const d = String(baseDate.getDate()).padStart(2, '0');
    computerForm.expiration_date = \`\${y}-\${m}-\${d}\`;
};

const getLicenseMessage = (pc) => {
    const clientName = props.client?.name || 'Cliente';
    return \`👋 Hola *\${clientName}*,

Aquí tienes los detalles de tu licencia:

💻 *Estación:* \${pc.box_number} - \${pc.name || 'N/A'}
📅 *Vence el:* \${formatDate(pc.expiration_date)}

🔑 *Clave de Activación:*
*\${pc.license_key}*

¡Gracias por tu confianza! 🚀\`;
};

const copyLicenseData = (pc) => {
    const text = getLicenseMessage(pc);
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text);
        showToast('¡Datos de licencia copiados!', 'success');
    } else {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";
        textArea.style.opacity = "0";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            showToast('¡Datos de licencia copiados!', 'success');
        } catch (err) {
            showToast('Error al copiar', 'danger');
        }
        document.body.removeChild(textArea);
    }
};

const sendWhatsApp = (pc) => {
    if (!props.client.phone) {
        showToast('El cliente no tiene un teléfono registrado.', 'warning');
        return;
    }
    const message = \`Hola, te recordamos que la licencia de tu equipo *\${pc.name}* vence el \${new Date(pc.expiration_date).toLocaleDateString()}.\`;
    const url = \`https://api.whatsapp.com/send?phone=57\${props.client.phone}&text=\${encodeURIComponent(message)}\`;
    window.open(url, '_blank');
};

const sendServiceWhatsApp = (service) => {
    if (!props.client.phone) {
        showToast('El cliente no tiene un teléfono registrado.', 'warning');
        return;
    }
    const message = \`Hola, te recordamos que tu servicio *\${service.name}* vence el \${new Date(service.expiration_date).toLocaleDateString()}.\`;
    const url = \`https://api.whatsapp.com/send?phone=57\${props.client.phone}&text=\${encodeURIComponent(message)}\`;
    window.open(url, '_blank');
};

const deleteComputer = (pc) => {
    if (confirm('¿Está seguro de eliminar esta licencia?')) {
        computerForm.delete(route('computers.destroy', pc.id), {
            preserveScroll: true,
        });
    }
};

`;

const newContent = content.substring(0, startIdx) + correctCode + content.substring(endIdx);

fs.writeFileSync(filePath, newContent, 'utf8');

console.log('Successfully repaired Show.vue!');

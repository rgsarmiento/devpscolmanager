const fs = require('fs');

function updateFile(filePath) {
    let content = fs.readFileSync(filePath, 'utf8');

    const searchMethods = `const generateFolioMessage = (info) => {
    const clientName = info.client?.name || 'Cliente';
    const total = new Intl.NumberFormat('de-DE').format(info.folios_total);
    const startDate = info.plan_start_date ? info.plan_start_date.split('T')[0] : 'N/A';
    const dias = Math.round(info.dias_transcurridos || info.sql_dias || 0); 
    const promedio = info.promedio_folios_usados_por_dia || (Math.round((info.sql_promedio || 0) * 100) / 100);
    const remaining = new Intl.NumberFormat('de-DE').format(info.folios_remaining);
    
    return \`🚨 *¡ALERTA DE CONSUMO!* 🚨
Hola *\${clientName}*, le informamos que su plan de folios está próximo a agotarse. 📉

Inició su plan de *\${total}* folios el día *\${startDate}*.
Han transcurrido *\${dias}* días. A su ritmo de consumo de *\${promedio}* folios diarios, le restan únicamente *\${remaining}* folios disponibles.

⚠️ *Por favor renueve su plan pronto para evitar interrupciones en su servicio de facturación electrónica.* ⚠️\`;
}

const copyFolioMessage = (info) => {
    const msg = generateFolioMessage(info);
    navigator.clipboard.writeText(msg).then(() => {
        Swal.fire({
            icon: 'success',
            title: '¡Mensaje Copiado!',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    });
}`;

    const replaceMethods = `const generateFolioMessage = (info) => {
    const clientName = info.client?.name || 'Cliente';
    const total = new Intl.NumberFormat('de-DE').format(info.folios_total);
    const startDate = info.plan_start_date ? info.plan_start_date.split('T')[0] : 'N/A';
    const dias = Math.round(info.dias_transcurridos || info.sql_dias || 0); 
    const promedio = info.promedio_folios_usados_por_dia || (Math.round((info.sql_promedio || 0) * 100) / 100);
    const remaining = new Intl.NumberFormat('de-DE').format(info.folios_remaining);
    
    let diasEstimados = info.dias_estimados_para_terminar || info.sql_estimados || 0;
    diasEstimados = Math.round(diasEstimados);
    const estimacion = diasEstimados < 999999 ? \`*¡Se estima que su plan terminará en \${diasEstimados} días!*\` : '';
    
    return \`🚨 *¡ALERTA DE CONSUMO!* 🚨
Hola *\${clientName}*, le informamos que su plan de folios está próximo a agotarse. \${estimacion} 📉

Inició su plan de *\${total}* folios el día *\${startDate}*.
Han transcurrido *\${dias}* días. A su ritmo de consumo de *\${promedio}* folios diarios, le restan únicamente *\${remaining}* folios disponibles.

⚠️ *Por favor renueve su plan pronto para evitar interrupciones en su servicio de facturación electrónica.* ⚠️\`;
}

const copyFolioMessage = (info) => {
    const msg = generateFolioMessage(info);
    
    const fallbackCopyTextToClipboard = (text) => {
        var textArea = document.createElement("textarea");
        textArea.value = text;
        
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";

        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            document.execCommand('copy');
            Swal.fire({
                icon: 'success',
                title: '¡Mensaje Copiado!',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        } catch (err) {
            Swal.fire('Error', 'No se pudo copiar el mensaje', 'error');
        }

        document.body.removeChild(textArea);
    }

    if (!navigator.clipboard) {
        fallbackCopyTextToClipboard(msg);
        return;
    }
    
    navigator.clipboard.writeText(msg).then(() => {
        Swal.fire({
            icon: 'success',
            title: '¡Mensaje Copiado!',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    }).catch(err => {
        // Fallback in case clipboard API fails (e.g. not HTTPS)
        fallbackCopyTextToClipboard(msg);
    });
}`;

    if (content.includes('navigator.clipboard.writeText(msg).then(() => {') && !content.includes('fallbackCopyTextToClipboard')) {
        content = content.replace(searchMethods, replaceMethods);
        fs.writeFileSync(filePath, content);
        console.log('Updated ' + filePath);
    }
}

updateFile('resources/js/Pages/Dashboard.vue');
updateFile('resources/js/Pages/FolioConsumption/Index.vue');

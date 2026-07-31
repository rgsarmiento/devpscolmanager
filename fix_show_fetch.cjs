const fs = require('fs');

let content = fs.readFileSync('resources/js/Pages/Clients/Show.vue', 'utf8');

const searchStr = `const submitResolutionConfig = () => {
    resolutionForm.post(route('invoicing.resolution', props.client.id), {
        preserveScroll: true,
    });
};`;

const replacement = `const submitResolutionConfig = () => {
    resolutionForm.post(route('invoicing.resolution', props.client.id), {
        preserveScroll: true,
    });
};

const fetchResolutions = () => {
    if (!props.client.invoicing_info?.api_token) {
        usePage().props.flash.flash = { bannerStyle: 'danger', banner: 'Falta Token API' };
        return;
    }
    
    axios.post(route('invoicing.numbering-range', props.client.id))
        .then(response => {
            if (response.data && response.data.ResponseDian && response.data.ResponseDian.Envelope && response.data.ResponseDian.Envelope.Body) {
                // The structure from DIAN is complex, but we try to extract what we can
                // We'll map standard fields if available
                const data = response.data;
                // Since DIAN returns raw XML/Array response, you usually map the properties in the backend, but we'll try to handle it.
                // Or if it's already structured:
                resolutionForm.prefix = data.Prefix || resolutionForm.prefix;
                resolutionForm.resolution = data.ResolutionNumber || resolutionForm.resolution;
                resolutionForm.resolution_date = data.ValidDateFrom || resolutionForm.resolution_date;
                resolutionForm.date_from = data.ValidDateFrom || resolutionForm.date_from;
                resolutionForm.date_to = data.ValidDateTo || resolutionForm.date_to;
                resolutionForm.from = data.FromNumber || resolutionForm.from;
                resolutionForm.to = data.ToNumber || resolutionForm.to;
                resolutionForm.technical_key = data.TechnicalKey || resolutionForm.technical_key;
                
                usePage().props.flash.flash = { bannerStyle: 'success', banner: 'Resolución obtenida correctamente.' };
            } else if (response.data && response.data.ResponseDian) {
                usePage().props.flash.flash = { bannerStyle: 'success', banner: 'Consulta realizada, revise los datos.' };
            } else {
                usePage().props.flash.flash = { bannerStyle: 'danger', banner: 'Respuesta inesperada de la DIAN.' };
            }
        })
        .catch(error => {
            console.error(error);
            usePage().props.flash.flash = { bannerStyle: 'danger', banner: error.response?.data?.error || 'Error de conexión al consultar la DIAN.' };
        });
};`;

if (!content.includes('const fetchResolutions')) {
    content = content.replace(searchStr, replacement);
    fs.writeFileSync('resources/js/Pages/Clients/Show.vue', content);
    console.log("Added fetchResolutions to Show.vue");
} else {
    console.log("fetchResolutions already exists.");
}

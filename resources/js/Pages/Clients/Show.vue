<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Link, usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Pagination from '@/Components/Pagination.vue';
import InputError from '@/Components/InputError.vue';
import Swal from 'sweetalert2';

const showToast = (message, type = 'success') => {
    Swal.fire({
        icon: type === 'danger' ? 'error' : type,
        title: message,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000
    });
};

const isAdmin = computed(() => usePage().props.auth.user.role === 'admin');

const props = defineProps({
    client: Object,
    computers: Object, // Paginated results
    planInfo: Object, // API Folio Info
    catalogs: Object,
    distributors: Array,
    serviceRates: Array,
    folioRates: Array,
});

const editClientForm = useForm({
    name: props.client.name || '',
    distributor_id: props.client.distributor_id || '',
});
const isEditClientModalOpen = ref(false);

const openEditClientModal = () => {
    editClientForm.name = props.client.name;
    editClientForm.distributor_id = props.client.distributor_id || '';
    isEditClientModalOpen.value = true;
};

const closeEditClientModal = () => {
    isEditClientModalOpen.value = false;
};

const saveClient = () => {
    editClientForm.put(route('clients.update', props.client.id), {
        onSuccess: () => closeEditClientModal(),
    });
};

const envForm = useForm({
    environment_status: props.client.environment_status || 'pruebas',
});

const toggleEnvironment = () => {
    const newStatus = envForm.environment_status === 'produccion' ? 'pruebas' : 'produccion';
    if (confirm(`¿Está seguro de cambiar el entorno a ${newStatus.toUpperCase()}? Esto se sincronizará con la DIAN.`)) {
        envForm.environment_status = newStatus;
        envForm.patch(route('clients.toggle-environment', props.client.id), {
            preserveScroll: true,
            onSuccess: () => {
                // Toast will be shown by flash messages natively or we can rely on standard inertia handling
            },
            onError: () => {
                // Revert
                envForm.environment_status = newStatus === 'produccion' ? 'pruebas' : 'produccion';
            }
        });
    }
};

const whatsappForm = useForm({
    whatsapp_contact: props.client.whatsapp_contact || '',
});

const updateWhatsapp = () => {
    whatsappForm.patch(route('clients.whatsapp-contact', props.client.id), {
        preserveScroll: true,
    });
};

// Helper for Municipality Name
const getMunicipalityName = (id) => {
    if (!props.catalogs?.municipalities) return '';
    const muni = props.catalogs.municipalities.find(m => m.id == id);
    return muni ? muni.name : '';
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    // If it's an ISO string or similar, take only the date part to avoid TZ shifts
    return dateString.split('T')[0];
};

const confirmingComputerManagement = ref(false);
const editingComputer = ref(null);

const serviceModalOpen = ref(false);
const editingService = ref(null);

const serviceForm = useForm({
    name: '',
    expiration_date: '',
    price: 0,
});

const openServiceModal = (service = null) => {
    editingService.value = service;
    if (service) {
        serviceForm.name = service.name;
        serviceForm.expiration_date = formatDate(service.expiration_date);
        serviceForm.price = service.price || 0;
    } else {
        serviceForm.reset();
    }
    serviceModalOpen.value = true;
};

const closeServiceModal = () => {
    serviceModalOpen.value = false;
    serviceForm.reset();
};

const saveService = () => {
    if (editingService.value) {
        serviceForm.put(route('client-services.update', { client: props.client.id, clientService: editingService.value.id }), {
            preserveScroll: true,
            onSuccess: () => closeServiceModal(),
        });
    } else {
        serviceForm.post(route('client-services.store', props.client.id), {
            preserveScroll: true,
            onSuccess: () => closeServiceModal(),
        });
    }
};

const deleteService = (service) => {
    if (confirm('¿Está seguro de eliminar este servicio? Se eliminarán los cobros pendientes asociados.')) {
        router.delete(route('client-services.destroy', { client: props.client.id, clientService: service.id }), {
            preserveScroll: true,
        });
    }
};

const computerForm = useForm({
    box_number: '',
    name: '',
    pin: '',
    license_key: '',
    license_type: 'normal',
    expiration_date: '',
    is_active: true,
    observation: '',
    generate_charge: true,
});

const openCreateModal = () => {
    editingComputer.value = null;
    computerForm.reset();
    computerForm.generate_charge = true;
    confirmingComputerManagement.value = true;
};

const openEditModal = (pc) => {
    editingComputer.value = pc;
    computerForm.box_number = pc.box_number;
    computerForm.name = pc.name;
    computerForm.pin = pc.pin || '';
    computerForm.license_key = pc.license_key;
    computerForm.license_type = pc.license_type;
    // Format date for <input type="date"> (YYYY-MM-DD)
    computerForm.expiration_date = formatDate(pc.expiration_date);
    computerForm.is_active = !!pc.is_active;
    computerForm.observation = pc.observation;
    computerForm.generate_charge = true;
    confirmingComputerManagement.value = true;
};

const closeModal = () => {
    confirmingComputerManagement.value = false;
    computerForm.reset();
};

const saveComputer = () => {
    if (editingComputer.value) {
        computerForm.put(route('computers.update', editingComputer.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        computerForm.post(route('computers.store', { client_id: props.client.id }), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const generateLicense = async () => {
    if (!computerForm.pin || !computerForm.expiration_date) {
        showToast('Por favor ingrese el PIN y la Fecha de Vencimiento primero.', 'warning');
        return;
    }
    try {
        const response = await axios.post(route('computers.generate-license'), {
            pin: computerForm.pin,
            expiration_date: computerForm.expiration_date,
            client_id: props.client.id
        });
        if (response.data && response.data.license_key) {
            computerForm.license_key = response.data.license_key;
            if (response.data.extracted_data) {
                if (!computerForm.name && response.data.extracted_data.pc_name) {
                    computerForm.name = response.data.extracted_data.pc_name;
                }
                if (!computerForm.box_number && response.data.extracted_data.box_number) {
                    computerForm.box_number = response.data.extracted_data.box_number;
                }
            }
        }
    } catch (error) {
        console.error(error);
        if (error.response && error.response.status === 422 && error.response.data.decoded_data) {
            const data = error.response.data.decoded_data;
            Swal.fire({
                icon: 'error',
                title: 'Error de coincidencia de NIT',
                html: `<div class="text-sm text-left">
                    <p class="mb-2">El NIT del PIN no coincide con el cliente actual.</p>
                    <ul class="list-disc pl-5">
                        <li><strong>NIT PIN:</strong> ${data.nit}</li>
                        <li><strong>Cliente PIN:</strong> ${data.client_name}</li>
                        <li><strong>Serial:</strong> ${data.serial}</li>
                        <li><strong>PC:</strong> ${data.pc_name}</li>
                        <li><strong>Caja:</strong> ${data.box_number}</li>
                    </ul>
                </div>`,
                confirmButtonText: 'Entendido'
            });
        } else if (error.response && error.response.data && error.response.data.message) {
            showToast(error.response.data.message, 'danger');
        } else {
            showToast('Error al generar la licencia. Verifique los datos.', 'danger');
        }
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
    computerForm.expiration_date = `${y}-${m}-${d}`;
};

const getLicenseMessage = (pc) => {
    const clientName = props.client?.name || 'Cliente';
    return `👋 Hola *${clientName}*,

Aquí tienes los detalles de tu licencia:

💻 *Estación:* ${pc.box_number} - ${pc.name || 'N/A'}
📅 *Vence el:* ${formatDate(pc.expiration_date)}

🔑 *Clave de Activación:*
*${pc.license_key}*

¡Gracias por tu confianza! 🚀`;
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
    const wContact = props.client.whatsapp_contact;
    const message = getLicenseMessage(pc);
    const url = wContact
        ? `https://api.whatsapp.com/send?phone=57${wContact}&text=${encodeURIComponent(message)}`
        : `https://api.whatsapp.com/send?text=${encodeURIComponent(message)}`;
    window.open(url, '_blank');
};

const sendServiceWhatsApp = (service) => {
    const wContact = props.client.whatsapp_contact;
    const message = `Hola, te recordamos que tu servicio *${service.name}* vence el ${new Date(service.expiration_date).toLocaleDateString()}.`;
    const url = wContact
        ? `https://api.whatsapp.com/send?phone=57${wContact}&text=${encodeURIComponent(message)}`
        : `https://api.whatsapp.com/send?text=${encodeURIComponent(message)}`;
    window.open(url, '_blank');
};

const deleteComputer = (pc) => {
    if (confirm('¿Está seguro de eliminar esta licencia?')) {
        computerForm.delete(route('computers.destroy', pc.id), {
            preserveScroll: true,
        });
    }
};

// --- Invoicing Config ---
const planForm = useForm({
    plan_documents: props.client.invoicing_info?.plan_documents || '',
    plan_start_date: props.client.invoicing_info?.plan_start_date ? props.client.invoicing_info.plan_start_date.split('.')[0].replace(' ', 'T') : '',
});

const submitPlanUpdate = () => {
    planForm.post(route('invoicing.update-plan', props.client.id), {
        preserveScroll: true,
        onSuccess: () => {
            showToast('Plan actualizado directamente con éxito', 'success');
        },
        onError: () => {
            showToast('Error al actualizar el plan', 'danger');
        }
    });
};

const companyForm = useForm({
    nit: props.client.nit || '',
    dv: props.client.dv || '', 
    business_name: props.client.name || '',
    merchant_registration: props.client.merchant_registration || '0000000-00',
    type_document_identification_id: props.client.type_document_identification_id || 3, 
    type_organization_id: props.client.type_organization_id || 2,
    type_regime_id: props.client.type_regime_id || 2,
    type_liability_id: props.client.type_liability_id || 14,
    municipality_id: props.client.municipality_id || 820,
    address: props.client.address || '',
    phone: props.client.phone || '',
    email: props.client.email || '',
    // Mail Config from DB
    mail_host: props.client.invoicing_info?.mail_host || 'smtp.gmail.com',
    mail_port: props.client.invoicing_info?.mail_port || '587',
    mail_username: props.client.invoicing_info?.mail_username || props.client.email || '',
    mail_password: props.client.invoicing_info?.mail_password || '',
    mail_encryption: props.client.invoicing_info?.mail_encryption || 'tls',
    mail_from_address: props.client.invoicing_info?.mail_from_address || props.client.email || '',
    mail_from_name: props.client.invoicing_info?.mail_from_name || 'facturacion-nodo@devpscol.com',
    // IMAP Config from DB
    imap_server: props.client.invoicing_info?.imap_server || 'imap.gmail.com',
    imap_port: props.client.invoicing_info?.imap_port || '993',
    generate_pending_folios: false,
    imap_user: props.client.invoicing_info?.imap_user || props.client.email || '',
    imap_password: props.client.invoicing_info?.imap_password || '',
    imap_encryption: props.client.invoicing_info?.imap_encryption || 'ssl',
    plan_documents: props.client.invoicing_info?.plan_documents || '',
    plan_start_date: props.client.invoicing_info?.plan_start_date ? props.client.invoicing_info.plan_start_date.split('.')[0].replace(' ', 'T') : '',
    generate_pending_folios: false,
});

const softwareForm = useForm({
    id: props.client.invoicing_info?.software_identifier || '',
    pin: props.client.invoicing_info?.software_pin || '',
});

const folioConfirmationModalOpen = ref(false);
const estimatedFolioCost = ref(0);

const submitCompanyConfig = () => {
    const oldFolios = parseInt(props.client.invoicing_info?.plan_documents) || 0;
    const newFolios = parseInt(planForm.plan_documents) || 0;
    const oldDate = props.client.invoicing_info?.plan_start_date ? props.client.invoicing_info.plan_start_date.split('.')[0].replace(' ', 'T') : '';
    const newDate = planForm.plan_start_date || '';
    
    if ((newFolios > 0 && oldFolios !== newFolios) || (newFolios > 0 && oldDate !== newDate)) {
        if (newFolios >= 1000000) {
            const unl = props.folioRates?.find(r => r.max_folios === null);
            estimatedFolioCost.value = unl ? unl.price : 0;
        } else {
            const rate = props.folioRates?.find(r => r.min_folios <= newFolios && r.max_folios >= newFolios);
            estimatedFolioCost.value = rate ? rate.price * newFolios : 0;
        }
        folioConfirmationModalOpen.value = true;
    } else {
        executeCompanySync();
    }
};

const confirmFolioSync = (generatePending) => {
    companyForm.generate_pending_folios = generatePending;
    folioConfirmationModalOpen.value = false;
    executeCompanySync();
};

const executeCompanySync = () => {
    companyForm.transform((data) => ({
        ...data,
        plan_documents: planForm.plan_documents,
        plan_start_date: planForm.plan_start_date,
    })).post(route('invoicing.company', props.client.id), {
        preserveScroll: true,
    });
};
const submitSoftwareConfig = () => {
    softwareForm.post(route('invoicing.software', props.client.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const resolutionEnv = ref('pruebas');

const setEnvProduccion = () => {
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
};

const resolutionForm = useForm({
    type_document_id: 1,
    prefix: '',
    resolution: '',
    resolution_date: '',
    date_from: '',
    date_to: '',
    from: '',
    to: '',
    technical_key: '',
});


watch(() => resolutionForm.type_document_id, (newVal) => {
    // If it's not a standard invoice type, auto-suggest the prefix and clear fields
    const prefixes = {
        '4': 'NC',
        '5': 'ND',
        '9': 'NI',
        '10': 'NIA',
        '13': 'NADSE'
    };
    
    if (prefixes[newVal]) {
        resolutionForm.prefix = prefixes[newVal];
        resolutionForm.resolution = '';
        resolutionForm.resolution_date = '';
        resolutionForm.date_from = '';
        resolutionForm.date_to = '';
    }
    
    // Solo la Factura Electrónica (1) mantiene la clave técnica
    if (newVal != 1) {
        resolutionForm.technical_key = '';
    }
});

const isDocumentTypeWithoutResolution = computed(() => {
    return ['4', '5', '9', '10', '13'].includes(String(resolutionForm.type_document_id));
});

const submitResolutionConfig = () => {
    resolutionForm.post(route('invoicing.resolution', props.client.id), {
        preserveScroll: true,
    });
};

const fetchedResolutions = ref([]);
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
});

const selectResolution = (res) => {
    // Determinar si tiene Clave Técnica real o es nula (objeto)
    const hasTechnicalKey = res.TechnicalKey && typeof res.TechnicalKey === 'string' && res.TechnicalKey.trim() !== '';
    resolutionForm.type_document_id = hasTechnicalKey ? '1' : '11';

    resolutionForm.prefix = res.Prefix || '';
    resolutionForm.resolution = res.ResolutionNumber || '';
    resolutionForm.resolution_date = res.ValidDateFrom || '';
    resolutionForm.date_from = res.ValidDateFrom || '';
    resolutionForm.date_to = res.ValidDateTo || '';
    resolutionForm.from = res.FromNumber || '';
    resolutionForm.to = res.ToNumber || '';
    
    // Sometimes TechnicalKey is an object when it's nil
    if (res.TechnicalKey && typeof res.TechnicalKey === 'object') {
        resolutionForm.technical_key = '';
    } else {
        resolutionForm.technical_key = res.TechnicalKey || '';
    }
    
    showResolutionSelector.value = false;
    showToast('Resolución cargada correctamente.', 'success');
};

const fetchResolutions = () => {
    if (!props.client.invoicing_info?.api_token) {
        showToast('Falta Token API', 'danger');
        return;
    }
    
    axios.post(route('invoicing.numbering-range', props.client.id))
        .then(response => {
            const data = response.data;
            try {
                // Navigate the nested JSON structure
                const result = data.ResponseDian.Envelope.Body.GetNumberingRangeResponse.GetNumberingRangeResult;
                
                if (result.OperationCode !== "100") {
                    usePage().props.flash.flash = { bannerStyle: 'danger', banner: result.OperationDescription || 'Error en la respuesta de la DIAN' };
                    return;
                }
                
                let ranges = result.ResponseList?.NumberRangeResponse;
                
                if (!ranges) {
                    showToast('No se encontraron resoluciones para este software ID.', 'warning');
                    return;
                }
                
                // Normalize to array (if single object returned by XML parser)
                if (!Array.isArray(ranges)) {
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
                });
                
                if (ranges.length === 1) {
                    selectResolution(ranges[0]);
                } else if (ranges.length > 1) {
                    fetchedResolutions.value = ranges;
                    showResolutionSelector.value = true;
                    showToast('Se encontraron múltiples resoluciones.', 'success');
                } else {
                    showToast('No se encontraron resoluciones.', 'warning');
                }
                
            } catch (e) {
                console.error("Parse error:", e);
                showToast('La respuesta de la DIAN no tiene el formato esperado.', 'danger');
            }
        })
        .catch(error => {
            console.error(error);
            usePage().props.flash.flash = { bannerStyle: 'danger', banner: error.response?.data?.error || 'Error de conexión al consultar la DIAN.' };
        });
};

const certificateForm = useForm({
    certificate: null,
    password: props.client.invoicing_info?.certificate_password || '',
});

const submitCertificateConfig = () => {
    certificateForm.post(route('invoicing.certificate', props.client.id), {
        preserveScroll: true,
        forceFormData: true, // Important for file upload
    });
};

const testSetForm = useForm({
    test_set_id: props.client.invoicing_info?.test_set_id || '',
    test_set_consecutive: props.client.invoicing_info?.test_set_consecutive || 990000001,
});

const testSetResultData = ref(null);

const submitTestSet = async () => {
    testSetForm.processing = true;
    testSetResultData.value = null;
    try {
        const response = await axios.post(`/invoicing/${props.client.id}/test-set`, {
            test_set_id: testSetForm.test_set_id,
            test_set_consecutive: testSetForm.test_set_consecutive
        });
        testSetResultData.value = response.data;
    } catch (error) {
        testSetResultData.value = error.response?.data || { success: false, status_description: "Error de conexión o validación" };
    } finally {
        testSetForm.processing = false;
    }
};

const copyToClipboard = (text) => {
    if (!text) return;
    
    // Fallback function using textarea
    const fallbackCopyTextToClipboard = (text) => {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        
        // Avoid scrolling to bottom and showing the text area
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";
        textArea.style.opacity = "0";
        
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            const successful = document.execCommand('copy');
            if(successful) showToast('Token copiado al portapapeles', 'success');
        } catch (err) {
            console.error('Fallback: Oops, unable to copy', err);
        }
        
        document.body.removeChild(textArea);
    }
    
    if (!navigator.clipboard) {
        fallbackCopyTextToClipboard(text);
        return;
    }
    
    navigator.clipboard.writeText(text).then(function() {
        showToast('Token copiado al portapapeles', 'success');
    }, function(err) {
        console.error('Async: Could not copy text: ', err);
        fallbackCopyTextToClipboard(text);
    });
};

const formatNumber = (num, decimals = 0) => {
    if (num == null) return '0';
    return new Intl.NumberFormat('de-DE', { maximumFractionDigits: decimals }).format(num);
};
</script>

<template>
    <AppLayout :title="client.name">
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h2 class="font-bold text-3xl text-gray-900 tracking-tight flex items-center gap-3 flex-wrap">
                        {{ client.name }}
                        <button v-if="isAdmin" @click="openEditClientModal" class="text-sm bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-1 rounded-full flex items-center gap-1 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            Editar
                        </button>
                        <button @click="toggleEnvironment" class="text-xs px-3 py-1 rounded-full flex items-center gap-1 font-bold shadow-sm transition" :class="envForm.environment_status === 'produccion' ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-amber-100 text-amber-800 hover:bg-amber-200'">
                            <span class="w-2 h-2 rounded-full" :class="envForm.environment_status === 'produccion' ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                            {{ envForm.environment_status === 'produccion' ? 'PRODUCCIÓN' : 'PRUEBAS' }}
                        </button>
                    </h2>
                    <p class="text-indigo-600 font-medium flex items-center mt-1">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5l-2-2z"></path></svg>
                        Expediente de Empresa #{{ client.id }}
                    </p>
                </div>
                <div class="flex gap-2 w-full md:w-auto">
                    <Link :href="route('clients.index')" class="flex-1 md:flex-none text-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Volver al Listado
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- Dashboard-style Summary -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- General Info -->
                    <div class="md:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden group hover:shadow-md transition-shadow">
                        <div class="bg-slate-900 px-6 py-4 flex justify-between items-center">
                            <h3 class="text-white font-bold uppercase tracking-wider text-sm">Información Corporativa</h3>
                            <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        </div>
                        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-8">
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Identificación (NIT)</span>
                                <span class="text-lg font-bold text-slate-800">{{ client.nit }}<span v-if="client.dv" class="text-indigo-600 font-mono">-{{ client.dv }}</span></span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Correo Electrónico</span>
                                <span class="text-gray-700 font-medium">{{ client.email }}</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Contacto Telefónico</span>
                                <span class="text-gray-700 font-medium">{{ client.phone || 'Sin registro' }}</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Distribuidor</span>
                                <span class="text-gray-700 font-medium">
                                    <span v-if="client.distributor" class="bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded text-xs font-bold border border-indigo-200">
                                        {{ client.distributor.name }}
                                    </span>
                                    <span v-else class="text-emerald-600 font-bold text-xs bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                        Cliente Directo
                                    </span>
                                </span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Dirección Principal</span>
                                <span class="text-gray-700 font-medium truncate" :title="client.address">{{ client.address }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Invoicing Status Panel -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden flex flex-col justify-between">
                        <div class="p-6">
                            <h3 class="text-slate-900 font-bold uppercase tracking-wider text-sm mb-4">Estado Facturación</h3>
                            <div v-if="client.invoicing_info?.api_token" class="space-y-4">
                                <div class="flex items-center text-green-600 bg-green-50 p-3 rounded-xl border border-green-100">
                                    <div class="bg-green-500 rounded-full p-1 mr-3">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    </div>
                                    <span class="font-bold text-sm">Token Activo</span>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-xl border border-gray-100 space-y-3">
                                    <div class="flex justify-between items-end text-[10px] font-bold text-gray-400 uppercase tracking-tighter">
                                        <div class="flex flex-col w-full">
                                            <template v-if="planInfo && planInfo.absolut_plan_documents >= 1000000">
                                                <span>Consumo Folios</span>
                                                <div class="flex items-center justify-between w-full mt-1">
                                                    <span class="text-2xl text-slate-900">{{ formatNumber(planInfo.absolut_plan_documents - planInfo.docs_left_absolut) }} <small class="text-[10px] text-indigo-500 uppercase">usados</small></span>
                                                    <span class="bg-indigo-100 text-indigo-800 text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider">Plan Ilimitado</span>
                                                </div>
                                            </template>
                                            <template v-else>
                                                <span>Consumo Folios</span>
                                                <span class="text-2xl text-slate-900 mt-1" v-if="planInfo">{{ formatNumber(planInfo.docs_left_absolut) }} <small class="text-[10px] text-indigo-500">disp.</small></span>
                                            </template>
                                        </div>
                                    </div>
                                    
                                    <div class="flex flex-col gap-1" v-if="planInfo">
                                        <template v-if="planInfo.absolut_plan_documents < 1000000">
                                            <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden shadow-inner">
                                                <div class="bg-indigo-600 h-2 transition-all duration-700 ease-out" :style="{ width: Math.max(0, Math.min(100, (1 - (planInfo.docs_left_absolut / planInfo.absolut_plan_documents)) * 100)) + '%' }"></div>
                                            </div>
                                            <div class="text-[11px] font-bold text-slate-700">
                                                {{ formatNumber(planInfo.absolut_plan_documents - planInfo.docs_left_absolut) }} / {{ formatNumber(planInfo.absolut_plan_documents) }} <span class="text-gray-400 font-normal">utilizados</span>
                                            </div>
                                        </template>
                                        <template v-else-if="!planInfo">
                                            <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden shadow-inner">
                                                <div class="bg-indigo-600 h-2 w-0"></div>
                                            </div>
                                        </template>
                                        <div class="grid grid-cols-2 gap-2 mt-2 pt-2 border-t border-gray-100">
                                            <div class="flex flex-col">
                                                <span class="text-[9px] text-gray-400 uppercase font-bold">Días Transc.</span>
                                                <span class="text-xs font-bold text-slate-700">{{ formatNumber(planInfo.dias_transcurridos) }}</span>
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-[9px] text-gray-400 uppercase font-bold">Promedio diario</span>
                                                <span class="text-xs font-bold text-slate-700">{{ formatNumber(planInfo.promedio_folios_usados_por_dia, 2) }} f/d</span>
                                            </div>
                                            <div v-if="planInfo.absolut_plan_documents >= 1000000" class="flex flex-col col-span-2 mt-1 p-2 bg-indigo-50 rounded-lg items-center justify-center">
                                                <span class="text-xs font-black text-indigo-600 uppercase tracking-widest">Ilimitado</span>
                                            </div>
                                            <div v-else class="flex flex-col col-span-2 mt-1 p-2 bg-indigo-50 rounded-lg">
                                                <span class="text-[9px] text-indigo-400 uppercase font-bold">Proyección Restante</span>
                                                <span class="text-sm font-black text-indigo-700">{{ formatNumber(planInfo.dias_estimados_para_terminar) }} días estimados</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center text-[9px] text-gray-500 font-medium mt-2">
                                            <svg class="w-3 h-3 mr-1 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            Inicio Plan: {{ formatDate(planInfo.absolut_start_plan_date) }}
                                        </div>
                                    </div>
                                    <div v-else class="italic text-[10px] text-gray-400 animate-pulse">Consultando estado...</div>
                                </div>
                            </div>
                            <div v-else class="text-center py-4">
                                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-amber-50 text-amber-500 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                </div>
                                <p class="text-xs text-gray-400 font-medium">Pendiente por Configurar</p>
                            </div>
                        </div>
                        <div class="bg-slate-50 border-t border-gray-100 px-6 py-4">
                             <a href="#config-facturacion" class="text-indigo-600 font-bold text-xs uppercase tracking-widest hover:text-indigo-800 transition block text-center">Ir a Configuración →</a>
                        </div>
                    </div>
                </div>

                <!-- Licenses / Computers Card -->
                <div class="bg-white rounded-2xl shadow-xl border border-indigo-100 overflow-hidden">
                    <div class="px-8 py-6 bg-indigo-50 border-b border-indigo-100 flex justify-between items-center">
                        <div class="flex items-center">
                            <div class="bg-indigo-600 rounded-lg p-2 mr-4 shadow-lg shadow-indigo-200">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            </div>
                            <div class="flex flex-col">
                                <h3 class="text-xl font-bold text-slate-900 uppercase tracking-tight">Estaciones y Licencias</h3>
                                <div class="text-[10px] text-indigo-600 font-bold uppercase tracking-widest mt-0.5">
                                    {{ computers.total }} Registradas en total
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <Link :href="route('computers.index', { client_id: client.id })" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-false transition border-b-2">
                                Ver Listado Independiente
                            </Link>
                            <PrimaryButton v-if="isAdmin" @click="openCreateModal" class="!bg-indigo-600 !hover:bg-indigo-700 !shadow-none ring-offset-2 ring-indigo-500">
                                + Nueva Licencia
                            </PrimaryButton>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-white">
                                <tr>
                                    <th class="px-8 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">Caja / ID</th>
                                    <th class="px-8 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">Estación</th>
                                    <th class="px-8 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">Licencia Key</th>
                                    <th class="px-8 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">Vencimiento</th>
                                    <th class="px-8 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">Estado</th>
                                    <th class="px-8 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-widest">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="pc in computers.data" :key="pc.id" class="hover:bg-indigo-50/20 transition group">
                                    <td class="px-8 py-5 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <span class="w-8 h-8 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-sm mr-3 border border-slate-200">{{ pc.box_number }}</span>
                                            <span class="text-xs font-mono text-gray-400">#{{ pc.id }}</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5 whitespace-nowrap">
                                        <div class="text-sm font-bold text-slate-800">{{ pc.name }}</div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <div class="text-[10px] text-gray-400 uppercase tracking-tighter">{{ pc.license_type }}</div>
                                            <span v-if="pc.license_transactions && pc.license_transactions.length > 0" class="text-[10px] bg-yellow-100 text-yellow-800 font-bold px-2 py-0.5 rounded border border-yellow-200">
                                                Pendiente
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <code class="text-xs bg-slate-50 text-indigo-700 border border-indigo-100 px-2 py-1 rounded select-all font-bold tracking-widest">
                                                {{ pc.license_key }}
                                            </code>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5 whitespace-nowrap">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium" :class="new Date(pc.expiration_date) < new Date() ? 'text-red-500' : 'text-slate-700'">
                                                {{ formatDate(pc.expiration_date) }}
                                            </span>
                                            <span class="text-[10px] uppercase font-bold text-gray-300">Expira</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5 whitespace-nowrap">
                                        <span v-if="pc.is_active" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-700">
                                            <span class="w-2 h-2 rounded-full bg-green-500 mr-1.5 animate-pulse"></span>
                                            Activo
                                        </span>
                                        <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">
                                            Inactivo
                                        </span>
                                    </td>
                                    <td class="px-8 py-5 whitespace-nowrap text-right text-sm flex justify-end items-center gap-3">
                                        <button v-if="isAdmin" @click="openEditModal(pc)" class="text-indigo-600 hover:text-indigo-900 font-bold border-b-2 border-transparent hover:border-indigo-600 transition">Editar</button>
                                        
                                        <button @click="sendWhatsApp(pc)" class="text-green-500 hover:text-green-700 transition" title="Enviar Recordatorio WhatsApp">
                                            <svg class="w-5 h-5 inline" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"></path></svg>
                                        </button>

                                        <button @click="copyLicenseData(pc)" class="text-slate-500 hover:text-slate-700 transition" title="Copiar Datos">
                                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                        </button>

                                        <button v-if="isAdmin" @click="deleteComputer(pc)" class="text-red-400 hover:text-red-600 transition" title="Eliminar">
                                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </td>
                                </tr>
                                 <tr v-if="!computers.data.length">
                                    <td colspan="6" class="px-8 py-12 text-center text-gray-400 italic">
                                        No se han registrado estaciones de trabajo aún.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Paginación de Licencias -->
                    <div v-if="computers.data.length" class="px-8 py-4 bg-gray-50 border-t border-gray-100 flex justify-center">
                        <Pagination :links="computers.links" />
                    </div>
                </div>

                <!-- Servicios Adicionales -->
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden mt-8 mb-8">
                    <div class="px-8 py-6 border-b border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
                        <div class="flex items-center">
                            <h3 class="text-xl font-bold text-slate-800">Servicios Adicionales</h3>
                        </div>
                        <div class="flex gap-2">
                            <PrimaryButton v-if="isAdmin" @click="openServiceModal()" class="!bg-emerald-600 !hover:bg-emerald-700 !shadow-none ring-offset-2 ring-emerald-500">
                                + Nuevo Servicio
                            </PrimaryButton>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-white">
                                <tr>
                                    <th class="px-8 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">Servicio</th>
                                    <th class="px-8 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">Vencimiento</th>
                                    <th class="px-8 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-widest">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="service in client.client_services" :key="'srv-'+service.id" class="hover:bg-emerald-50/20 transition group">
                                    <td class="px-8 py-5 whitespace-nowrap">
                                        <div class="text-sm font-bold text-slate-800">{{ service.name }}</div>
                                    </td>
                                    <td class="px-8 py-5 whitespace-nowrap">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium" :class="new Date(service.expiration_date) < new Date() ? 'text-red-500' : 'text-slate-700'">
                                                {{ formatDate(service.expiration_date) }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5 whitespace-nowrap text-right text-sm flex justify-end items-center gap-3">
                                        <button v-if="isAdmin" @click="openServiceModal(service)" class="text-emerald-600 hover:text-emerald-900 font-bold border-b-2 border-transparent hover:border-emerald-600 transition">Editar</button>
                                        <button v-if="isAdmin" @click="deleteService(service)" class="text-red-400 hover:text-red-600 transition" title="Eliminar">
                                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </td>
                                </tr>
                                 <tr v-if="!client.client_services || !client.client_services.length">
                                    <td colspan="3" class="px-8 py-12 text-center text-gray-400 italic">
                                        No se han registrado servicios adicionales.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Facturación Electrónica Configuration (The "Big" Card) -->
                <div id="config-facturacion" class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
                    <div class="px-8 py-6 bg-slate-900 flex justify-between items-center">
                        <div class="flex items-center">
                            <div class="bg-indigo-600 rounded-lg p-2 mr-4 shadow-lg shadow-indigo-500/20">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <h3 class="text-xl font-bold text-white uppercase tracking-tight">Ecosistema de Facturación Electrónica</h3>
                        </div>
                        <div v-if="client.invoicing_info?.api_token" class="hidden md:flex items-center gap-6">
                            <div class="text-right">
                                <div class="text-[10px] text-gray-400 uppercase font-bold tracking-widest">Company ID</div>
                                <div class="text-white font-mono text-sm">{{ client.invoicing_info.company_id }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-[10px] text-gray-400 uppercase font-bold tracking-widest">API Token</div>
                                <div class="flex items-center gap-2">
                                    <div class="text-white font-mono text-[10px] bg-slate-800 px-2 py-1 rounded border border-slate-700 max-w-[200px] truncate">
                                        {{ client.invoicing_info.api_token }}
                                    </div>
                                    <button type="button" @click="copyToClipboard(client.invoicing_info.api_token)" class="bg-indigo-600 p-1.5 rounded text-white hover:bg-indigo-700 transition" title="Copiar Token">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-12">
                            
                            <!-- WHATSAPP CONTACT SECTION -->
                            <div class="md:col-span-2 bg-white p-6 rounded-xl border border-emerald-200 bg-emerald-50/50 shadow-sm mb-[-1rem]">
                                <div class="flex items-center mb-4">
                                    <svg class="w-6 h-6 text-emerald-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                    <h4 class="text-lg font-bold text-emerald-800 uppercase tracking-wide">Notificaciones de WhatsApp</h4>
                                </div>
                                <p class="text-sm text-emerald-700 mb-4 max-w-2xl">
                                    Define el número de contacto que recibirá las alertas de vencimientos de licencias, servicios y folios. (Este campo es independiente y puede ser actualizado por distribuidores).
                                </p>
                                <form @submit.prevent="updateWhatsapp" class="flex gap-4 items-end">
                                    <div class="flex-1 max-w-sm">
                                        <InputLabel value="Número (10 dígitos)" />
                                        <TextInput v-model="whatsappForm.whatsapp_contact" type="text" class="w-full mt-1 border-emerald-300 focus:border-emerald-500 focus:ring-emerald-500" placeholder="Ej. 3001234567" />
                                        <InputError :message="whatsappForm.errors.whatsapp_contact" class="mt-2" />
                                    </div>
                                    <PrimaryButton type="submit" :class="{ 'opacity-25': whatsappForm.processing }" :disabled="whatsappForm.processing" class="!bg-emerald-600 hover:!bg-emerald-700 h-[42px]">
                                        Guardar WhatsApp
                                    </PrimaryButton>
                                </form>
                            </div>

                            <!-- STEP 1: COMPANY & MAIL -->
                            <div class="md:col-span-2">
                                <div class="flex items-center mb-6">
                                    <span class="bg-indigo-600 text-white w-8 h-8 rounded-full flex items-center justify-center font-bold mr-3 shadow-lg shadow-indigo-100">1</span>
                                    <h4 class="text-lg font-bold text-slate-800 uppercase tracking-wide">Configuración de Compañía y Envíos</h4>
                                </div>
                                
                                <form @submit.prevent="submitCompanyConfig" class="space-y-8">
                                    <fieldset :disabled="!isAdmin" class="space-y-8">
                                    <!-- DATOS EMPRESA -->
                                    <div class="bg-indigo-50/30 p-6 rounded-2xl border border-indigo-100">
                                        <p class="text-[10px] font-bold text-indigo-600 uppercase tracking-[0.2em] mb-4">Información Corporativa y DIAN</p>
                                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                            <div class="md:col-span-3">
                                                <InputLabel value="Razón Social / Nombre" />
                                                <TextInput v-model="companyForm.business_name" class="w-full text-sm" required />
                                            </div>
                                            <div>
                                                <InputLabel value="NIT" />
                                                <div class="flex gap-1">
                                                    <TextInput v-model="companyForm.nit" class="w-full text-sm" required />
                                                    <TextInput v-model="companyForm.dv" class="w-12 text-sm text-center" placeholder="DV" />
                                                </div>
                                            </div>
                                            
                                            <div class="md:col-span-2">
                                                <InputLabel value="Dirección" />
                                                <TextInput v-model="companyForm.address" class="w-full text-sm" />
                                            </div>
                                            <div>
                                                <InputLabel value="Teléfono" />
                                                <TextInput v-model="companyForm.phone" class="w-full text-sm" />
                                            </div>
                                            <div>
                                                <InputLabel value="Email" />
                                                <TextInput v-model="companyForm.email" type="email" class="w-full text-sm" />
                                            </div>

                                            <div>
                                                <InputLabel value="Matrícula Mercantil" />
                                                <TextInput v-model="companyForm.merchant_registration" class="w-full text-sm" />
                                            </div>
                                            <div>
                                                <InputLabel value="Tipo Identificación" />
                                                <select v-model="companyForm.type_document_identification_id" class="w-full border-gray-200 rounded-lg text-sm focus:ring-indigo-500 mt-1">
                                                    <option v-for="item in catalogs.document_types" :key="item.id" :value="item.id">{{ item.name }}</option>
                                                </select>
                                            </div>
                                            <div>
                                                <InputLabel value="Organización" />
                                                <select v-model="companyForm.type_organization_id" class="w-full border-gray-200 rounded-lg text-sm focus:ring-indigo-500 mt-1">
                                                    <option v-for="item in catalogs.organizations" :key="item.id" :value="item.id">{{ item.name }}</option>
                                                </select>
                                            </div>
                                            <div>
                                                <InputLabel value="Régimen" />
                                                <select v-model="companyForm.type_regime_id" class="w-full border-gray-200 rounded-lg text-sm focus:ring-indigo-500 mt-1">
                                                    <option v-for="item in catalogs.regimes" :key="item.id" :value="item.id">{{ item.name }}</option>
                                                </select>
                                            </div>
                                            
                                            <div class="md:col-span-2">
                                                <InputLabel value="Municipio" />
                                                <select v-model="companyForm.municipality_id" class="w-full border-gray-200 rounded-lg text-sm focus:ring-indigo-500 mt-1">
                                                    <option v-for="item in catalogs.municipalities" :key="item.id" :value="item.id">{{ item.name }}</option>
                                                </select>
                                            </div>
                                            <div class="md:col-span-2">
                                                <InputLabel value="Responsabilidad (Tipo)" />
                                                <select v-model="companyForm.type_liability_id" class="w-full border-gray-200 rounded-lg text-sm focus:ring-indigo-500 mt-1">
                                                    <option v-for="item in catalogs.liabilities" :key="item.id" :value="item.id">{{ item.name }}</option>
                                                </select>
                                            </div>

                                            <!-- PLAN DE FOLIOS -->
                                            <div class="md:col-span-4 mt-4 pt-4 border-t border-indigo-100/50">
                                                <p class="text-[10px] font-bold text-indigo-600 uppercase tracking-[0.2em] mb-4">Plan de Folios</p>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <div>
                                                        <InputLabel value="Cantidad de Folios (Plan)" />
                                                        <TextInput v-model="planForm.plan_documents" type="number" class="w-full text-sm" placeholder="Ej: 1500" />
                                                    </div>
                                                    <div>
                                                        <InputLabel value="Fecha Inicio del Plan" />
                                                        <TextInput v-model="planForm.plan_start_date" type="datetime-local" step="1" class="w-full text-sm" />
                                                        <p class="text-[10px] text-gray-400 mt-1">Formato: AAAA-MM-DD HH:MM:SS</p>
                                                    </div>
                                                    <div class="md:col-span-2 text-right">
                                                        <PrimaryButton v-if="isAdmin" type="button" @click.prevent="submitPlanUpdate" :class="{ 'opacity-25': planForm.processing }" :disabled="planForm.processing" class="!bg-indigo-600 !hover:bg-indigo-700">
                                                            Actualizar Plan
                                                        </PrimaryButton>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- CORREOS -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                        <div class="space-y-4">
                                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-4">Servidor de Salida (SMTP)</p>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="col-span-2">
                                                    <InputLabel value="Host SMTP" />
                                                    <TextInput v-model="companyForm.mail_host" class="w-full text-sm" placeholder="smtp.gmail.com" />
                                                </div>
                                                <div>
                                                    <InputLabel value="Puerto" />
                                                    <TextInput v-model="companyForm.mail_port" class="w-full text-sm" placeholder="587" />
                                                </div>
                                                <div>
                                                    <InputLabel value="Encriptación" />
                                                    <select v-model="companyForm.mail_encryption" class="w-full border-gray-200 rounded-lg text-sm mt-1">
                                                        <option value="tls">TLS</option>
                                                        <option value="ssl">SSL</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel value="Usuario Correo (Username)" />
                                                    <TextInput v-model="companyForm.mail_username" class="w-full text-sm" placeholder="usuario@gmail.com" />
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel value="Contraseña Correo" />
                                                    <TextInput v-model="companyForm.mail_password" type="text" class="w-full text-sm" />
                                                </div>
                                                <div>
                                                    <InputLabel value="Email Remitente (From Address)" />
                                                    <TextInput v-model="companyForm.mail_from_address" class="w-full text-sm" placeholder="usuario@gmail.com" />
                                                </div>
                                                <div>
                                                    <InputLabel value="Nombre Remitente (From Name)" />
                                                    <TextInput v-model="companyForm.mail_from_name" class="w-full text-sm" placeholder="Facturación Nodo" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="space-y-4">
                                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-4">Recepción (IMAP)</p>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="col-span-2">
                                                    <InputLabel value="Servidor IMAP" />
                                                    <TextInput v-model="companyForm.imap_server" class="w-full text-sm" placeholder="imap.gmail.com" />
                                                </div>
                                                <div>
                                                    <InputLabel value="Puerto" />
                                                    <TextInput v-model="companyForm.imap_port" class="w-full text-sm" placeholder="993" />
                                                </div>
                                                <div>
                                                    <InputLabel value="Encriptación" />
                                                    <select v-model="companyForm.imap_encryption" class="w-full border-gray-200 rounded-lg text-sm mt-1">
                                                        <option value="ssl">SSL</option>
                                                        <option value="tls">TLS</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel value="Usuario IMAP" />
                                                    <TextInput v-model="companyForm.imap_user" class="w-full text-sm" placeholder="usuario@gmail.com" />
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel value="Contraseña IMAP" />
                                                    <TextInput v-model="companyForm.imap_password" type="text" class="w-full text-sm" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="pt-6 border-t border-gray-100 text-right">
                                        <PrimaryButton v-if="isAdmin" :disabled="companyForm.processing" class="!bg-slate-900 !hover:bg-slate-800">
                                            {{ client.invoicing_info?.api_token ? 'Sincronizar Datos corporativos' : 'Generar Token y Registro' }}
                                        </PrimaryButton>
                                    </div>
                                    </fieldset>
                                </form>
                            </div>

                            <hr class="md:col-span-2 border-slate-100">

                            <!-- STEP 2 & 4 (Software & Cert) -->
                            <div class="md:col-span-2 space-y-12" :class="{ 'opacity-40 select-none grayscale': !client.invoicing_info?.api_token }">
                                <!-- STEP 2 -->
                                <div>
                                    <div class="flex items-center mb-6">
                                        <span class="bg-indigo-600 text-white w-8 h-8 rounded-full flex items-center justify-center font-bold mr-3">2</span>
                                        <h4 class="text-sm font-bold text-slate-800 uppercase tracking-widest">Infraestructura de Software</h4>
                                    </div>
                                    <form @submit.prevent="submitSoftwareConfig" class="bg-gray-50 p-6 rounded-2xl border border-gray-100 space-y-4">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <InputLabel value="Software ID" />
                                                <div class="flex gap-2">
                                                    <TextInput v-model="softwareForm.id" class="w-full text-xs font-mono" required />
                                                    <PrimaryButton type="button" @click="syncSoftware" :disabled="isSyncingSoftware" class="!bg-emerald-600 px-3 shrink-0" title="Sincronizar con DB Global">
                                                        <svg v-if="isSyncingSoftware" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                    </PrimaryButton>
                                                </div>
                                            </div>
                                            <div>
                                                <InputLabel value="PIN de Seguridad" />
                                                <TextInput v-model="softwareForm.pin" class="w-full text-xs" required />
                                            </div>
                                        </div>
                                        <PrimaryButton :disabled="softwareForm.processing || !client.invoicing_info?.api_token" class="w-full !justify-center">Asociar Software</PrimaryButton>
                                    </form>
                                </div>

                                <!-- STEP 4 -->
                                <div>
                                    <div class="flex items-center justify-between mb-6">
                                        <div class="flex items-center">
                                            <span class="bg-indigo-600 text-white w-8 h-8 rounded-full flex items-center justify-center font-bold mr-3">3</span>
                                            <h4 class="text-sm font-bold text-slate-800 uppercase tracking-widest">Firma Digital (.p12)</h4>
                                        </div>
                                        <div v-if="client.invoicing_info?.certificate_expiration_date" class="px-3 py-1 rounded-full text-xs font-bold" :class="new Date(client.invoicing_info.certificate_expiration_date) < new Date() ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'">
                                            Vence: {{ client.invoicing_info.certificate_expiration_date.split('T')[0] }}
                                        </div>
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

                                <!-- HABILITACION DIAN -->
                                <div class="mt-12">
                                    <div class="flex items-center mb-6">
                                        <span class="bg-indigo-600 text-white w-8 h-8 rounded-full flex items-center justify-center font-bold mr-3">H</span>
                                        <h4 class="text-sm font-bold text-slate-800 uppercase tracking-widest">Habilitación DIAN (Set de Pruebas)</h4>
                                    </div>
                                    
                                    <div class="bg-gray-50 p-6 rounded-2xl border border-gray-100 space-y-4">
                                        <form @submit.prevent="submitTestSet" class="space-y-4">
                                            <div>
                                                <InputLabel value="TestSetId" />
                                                <TextInput v-model="testSetForm.test_set_id" class="w-full text-sm font-mono text-xs" placeholder="Ej: cef754c6-42c4-4494-9fd8-116a0d9fd353" required />
                                            </div>
                                            <div>
                                                <InputLabel value="Consecutivo" />
                                                <TextInput v-model="testSetForm.test_set_consecutive" type="number" class="w-full text-sm font-mono text-xs" required />
                                            </div>
                                            <PrimaryButton type="submit" :disabled="testSetForm.processing || !client.invoicing_info?.api_token" class="w-full !justify-center !bg-indigo-600">
                                                Habilitar
                                            </PrimaryButton>
                                        </form>

                                        <!-- Resultado -->
                                        <div v-if="testSetResultData" class="mt-4 p-4 rounded-lg text-sm" :class="testSetResultData.success ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-red-50 text-red-800 border border-red-200'">
                                            <div class="flex flex-col mb-3">
                                                <strong class="font-bold text-lg mb-1">{{ testSetResultData.success ? '¡Set de Prueba Aceptado!' : 'Atención / Rechazado' }}</strong>
                                                <span class="text-[10px] font-mono text-gray-500 bg-white/50 p-1 rounded">ZipKey: {{ testSetResultData.zip_key }}</span>
                                            </div>
                                            <p v-if="testSetResultData.status_description" class="mb-2 font-medium">{{ testSetResultData.status_description }}</p>
                                            <div v-if="testSetResultData.messages && testSetResultData.messages.length > 0" class="mt-3 pt-3 border-t border-black/10">
                                                <p class="text-xs font-bold mb-1 uppercase tracking-wider">Notificaciones / Errores:</p>
                                                <ul class="list-disc pl-5 space-y-1 text-[11px] font-mono">
                                                    <li v-for="(msg, index) in testSetResultData.messages" :key="index">{{ msg }}</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- STEP 3 (Resolutions) -->
                            <div class="md:col-span-2 space-y-12" :class="{ 'opacity-40 select-none grayscale': !client.invoicing_info?.api_token }">
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
                                                @click="setEnvPruebas"
                                                class="px-6 py-2 rounded-full text-sm font-bold transition-all"
                                                :class="resolutionEnv === 'pruebas' ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-200 text-gray-500 hover:bg-gray-300'"
                                            >
                                                Pruebas (SETI)
                                            </button>
                                            <button 
                                                type="button"
                                                @click="setEnvProduccion"
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
                                        </div>

                                        <form @submit.prevent="submitResolutionConfig" class="space-y-4">
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="col-span-2">
                                                    <InputLabel value="Tipo de Documento" />
                                                    <select v-model="resolutionForm.type_document_id" class="w-full border-gray-200 rounded-lg text-sm focus:ring-indigo-500 mt-1">
                                                        <option value="1">Factura Electrónica de Venta</option>
                                                        <option value="3">Factura de Contingencia</option>
                                                        <option value="4">Nota Crédito</option>
                                                        <option value="5">Nota Débito</option>
                                                        <option value="9">Nomina Individual</option>
                                                        <option value="10">Nomina Individual de Ajuste</option>
                                                        <option value="11">Documento Soporte Electrónico</option>
                                                        <option value="13">Nota de Ajuste al Documento Soporte Electrónico</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="col-span-2 md:col-span-1">
                                                    <InputLabel value="Prefijo" />
                                                    <TextInput v-model="resolutionForm.prefix" class="w-full text-sm" :disabled="resolutionEnv === 'pruebas'" :required="resolutionEnv === 'produccion'" />
                                                </div>
                                                <div class="col-span-2 md:col-span-1">
                                                    <InputLabel value="Número de Resolución" />
                                                    <TextInput v-model="resolutionForm.resolution" class="w-full text-sm" :disabled="resolutionEnv === 'pruebas' || isDocumentTypeWithoutResolution" :required="resolutionEnv === 'produccion' && !isDocumentTypeWithoutResolution" />
                                                </div>
                                                
                                                <div class="col-span-2 md:col-span-1">
                                                    <InputLabel value="Fecha Resolución" />
                                                    <TextInput v-model="resolutionForm.resolution_date" type="date" class="w-full text-sm" :disabled="resolutionEnv === 'pruebas' || isDocumentTypeWithoutResolution" :required="resolutionEnv === 'produccion' && !isDocumentTypeWithoutResolution" />
                                                </div>
                                                
                                                <div class="col-span-2 md:col-span-1">
                                                    <InputLabel value="Válido Desde" />
                                                    <TextInput v-model="resolutionForm.date_from" type="date" class="w-full text-sm" :disabled="resolutionEnv === 'pruebas' || isDocumentTypeWithoutResolution" :required="resolutionEnv === 'produccion' && !isDocumentTypeWithoutResolution" />
                                                </div>
                                                <div class="col-span-2 md:col-span-1">
                                                    <InputLabel value="Válido Hasta" />
                                                    <TextInput v-model="resolutionForm.date_to" type="date" class="w-full text-sm" :disabled="resolutionEnv === 'pruebas' || isDocumentTypeWithoutResolution" :required="resolutionEnv === 'produccion' && !isDocumentTypeWithoutResolution" />
                                                </div>
                                                
                                                <div class="col-span-2 md:col-span-1">
                                                    <InputLabel value="Rango Desde" />
                                                    <TextInput v-model="resolutionForm.from" type="number" class="w-full text-sm" :disabled="resolutionEnv === 'pruebas'" :required="resolutionEnv === 'produccion'" />
                                                </div>
                                                <div class="col-span-2 md:col-span-1">
                                                    <InputLabel value="Rango Hasta" />
                                                    <TextInput v-model="resolutionForm.to" type="number" class="w-full text-sm" :disabled="resolutionEnv === 'pruebas'" :required="resolutionEnv === 'produccion'" />
                                                </div>
                                                
                                                <div class="col-span-2">
                                                    <InputLabel value="Clave Técnica (Technical Key)" />
                                                    <TextInput v-model="resolutionForm.technical_key" class="w-full text-sm font-mono text-xs" :disabled="resolutionEnv === 'pruebas' || resolutionForm.type_document_id != 1" :required="resolutionEnv === 'produccion' && resolutionForm.type_document_id == 1" />
                                                </div>
                                            </div>
                                            
                                            <div class="pt-4 border-t border-gray-200 flex justify-between items-center">
                                                <div></div>
                                                <PrimaryButton :disabled="resolutionForm.processing || !client.invoicing_info?.api_token" class="!bg-indigo-600">
                                                    Guardar Resolución
                                                </PrimaryButton>
                                            </div>
                                        </form>

                                        <!-- Resoluciones Guardadas Localmente -->
                                        <div class="mt-8 border-t border-gray-200 pt-6" v-if="client.resolutions && client.resolutions.length > 0">
                                            <div class="flex justify-between items-center mb-4">
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
                                                <div v-for="res in filteredResolutions" :key="res.id" class="bg-white border border-gray-200 hover:border-indigo-300 transition-colors rounded-lg p-4 flex justify-between items-center shadow-sm">
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
                        </div>
                        <div class="bg-slate-50 border-t border-gray-100 px-6 py-4">
                             <a href="#config-facturacion" class="text-indigo-600 font-bold text-xs uppercase tracking-widest hover:text-indigo-800 transition block text-center">Ir a Configuración →</a>
                        </div>
                    </div>
                </div>

                    </div>
        </div>

        <!-- Modal for Resolutions Selection -->
        <DialogModal :show="showResolutionSelector" @close="showResolutionSelector = false">
            <template #title>
                Seleccionar Resolución
            </template>
            <template #content>
                <div class="space-y-4 mt-4">
                    <p class="text-sm text-gray-600">Se encontraron múltiples resoluciones asociadas a este Software ID. Selecciona una para autocompletar el formulario:</p>
                    <div class="grid grid-cols-1 gap-3 max-h-96 overflow-y-auto pr-2">
                        <button 
                            v-for="(res, index) in fetchedResolutions" 
                            :key="index"
                            @click="selectResolution(res)"
                            class="flex flex-col text-left p-4 rounded-xl border border-gray-200 hover:border-indigo-500 hover:bg-indigo-50 transition-all focus:outline-none"
                        >
                            <div class="flex justify-between items-center w-full">
                                <span class="font-bold text-slate-800">{{ res.Prefix || 'SIN PREFIJO' }} - {{ res.ResolutionNumber }}</span>
                                <span v-if="res.isConfigured" class="text-[10px] bg-emerald-100 text-emerald-700 px-2 py-1 rounded-full font-bold uppercase ml-2">Ya Configurada</span>
                                <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-1 rounded-md font-mono">{{ res.ValidDateFrom }}</span>
                            </div>
                            <div class="text-xs text-gray-500 mt-2 flex justify-between">
                                <span>Rango: {{ res.FromNumber }} a {{ res.ToNumber }}</span>
                                <span>Vence: {{ res.ValidDateTo }}</span>
                            </div>
                        </button>
                    </div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="showResolutionSelector = false">
                    Cancelar
                </SecondaryButton>
            </template>
        </DialogModal>

        <!-- Modal for Computer -->
        <DialogModal :show="confirmingComputerManagement" @close="closeModal">
            <template #title>
                <div class="flex items-center text-slate-900 font-bold">
                    <div class="p-2 bg-indigo-100 rounded-lg mr-3 text-indigo-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
                    </div>
                    {{ editingComputer ? 'Actualizar Licencia' : 'Añadir Nueva Licencia' }}
                </div>
            </template>

            <template #content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                    <div class="space-y-4">
                         <div>
                            <InputLabel for="box_number" value="Puesto de Trabajo / Caja #" />
                            <div class="relative mt-1">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 font-bold">#</span>
                                <TextInput id="box_number" v-model="computerForm.box_number" type="text" class="pl-8 block w-full !rounded-xl" autofocus />
                            </div>
                            <InputError :message="computerForm.errors.box_number" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="name" value="Identificador Estación (Nombre PC)" />
                            <TextInput id="name" v-model="computerForm.name" type="text" class="mt-1 block w-full !rounded-xl" placeholder="Ej: CAJA-PRINCIPAL-01" />
                            <InputError :message="computerForm.errors.name" class="mt-2" />
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <InputLabel for="license_type" value="Modalidad de Licencia" />
                            <select id="license_type" v-model="computerForm.license_type" class="border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl mt-1 block w-full shadow-sm text-sm">
                                <option value="normal">Licencia Estándar</option>
                                <option value="vinculado">Licencia Vinculada</option>
                            </select>
                        </div>

                        <div>
                            <InputLabel for="expiration_date" value="Fecha de Vencimiento" />
                            <TextInput id="expiration_date" v-model="computerForm.expiration_date" type="date" class="mt-1 block w-full !rounded-xl" />
                            <InputError :message="computerForm.errors.expiration_date" class="mt-2" />
                        </div>
                    </div>

                    <div class="col-span-1 md:col-span-2">
                        <InputLabel for="pin" value="PIN de Activación" />
                        <TextInput id="pin" v-model="computerForm.pin" type="text" class="mt-1 block w-full !rounded-xl" />
                        <InputError :message="computerForm.errors.pin" class="mt-2" />
                    </div>

                    <div class="col-span-1 md:col-span-2">
                        <InputLabel for="license_key" value="Clave de Activación (License Key)" />
                        <div class="flex items-end gap-2 mt-1">
                            <div class="flex-grow">
                                <TextInput id="license_key" v-model="computerForm.license_key" type="text" class="block w-full font-mono !rounded-xl text-center !text-lg !font-bold !text-indigo-600 border-indigo-200" />
                            </div>
                            <button type="button" @click="generateLicense" class="px-4 py-2 bg-indigo-600 text-white !rounded-xl hover:bg-indigo-700 transition font-bold whitespace-nowrap h-[46px]" title="Generar nueva clave">
                                Generar Clave
                            </button>
                            <button type="button" @click="setNextYear" class="px-4 py-2 bg-slate-600 text-white !rounded-xl hover:bg-slate-700 transition font-bold whitespace-nowrap h-[46px]" title="Sugerir 1 Año">
                                +1 Año
                            </button>
                        </div>
                        <InputError :message="computerForm.errors.license_key" class="mt-2" />
                    </div>

                    <div class="col-span-1 md:col-span-2">
                        <InputLabel for="observation" value="Notas Internas" />
                        <TextInput id="observation" v-model="computerForm.observation" type="text" class="mt-1 block w-full !rounded-xl" />
                    </div>
                    
                    <div class="col-span-1 md:col-span-2 bg-gray-50 p-3 rounded-xl border border-gray-200 flex items-center">
                        <input id="is_active" type="checkbox" v-model="computerForm.is_active" class="rounded-lg border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 w-5 h-5">
                        <label for="is_active" class="ml-3 block text-sm font-bold text-slate-700">Estado: Habilitada para uso</label>
                    </div>

                    <div class="col-span-1 md:col-span-2 bg-indigo-50 p-3 rounded-xl border border-indigo-200 flex items-center">
                        <input id="generate_charge" type="checkbox" v-model="computerForm.generate_charge" class="rounded-lg border-indigo-300 text-indigo-600 shadow-sm focus:ring-indigo-500 w-5 h-5">
                        <label for="generate_charge" class="ml-3 block text-sm font-bold text-indigo-900">Generar cobro para esta licencia/estación automáticamente</label>
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeModal" class="!rounded-xl">
                    Cancelar
                </SecondaryButton>

                <PrimaryButton class="ms-3 !bg-indigo-600 !hover:bg-indigo-700 !rounded-xl !px-8" :class="{ 'opacity-25': computerForm.processing }" :disabled="computerForm.processing" @click="saveComputer">
                    {{ editingComputer ? 'Actualizar Cambios' : 'Confirmar y Guardar' }}
                </PrimaryButton>
            </template>
        </DialogModal>

        <!-- Edit Client Modal -->
        <DialogModal :show="isEditClientModalOpen" @close="closeEditClientModal">
            <template #title>Editar Datos del Cliente</template>
            <template #content>
                <div class="space-y-4">
                    <div>
                        <InputLabel for="client_name" value="Nombre o Razón Social" />
                        <TextInput id="client_name" v-model="editClientForm.name" type="text" class="mt-1 block w-full" autofocus />
                        <InputError :message="editClientForm.errors.name" class="mt-2" />
                    </div>
                    <div v-if="distributors && distributors.length > 0">
                        <InputLabel value="Asignar a Distribuidor (Opcional)" class="font-bold text-gray-700" />
                        <select v-model="editClientForm.distributor_id" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1">
                            <option v-for="dist in distributors" :key="dist.id" :value="dist.id">
                                {{ dist.name }}
                            </option>
                        </select>
                        <InputError :message="editClientForm.errors.distributor_id" class="mt-2" />
                    </div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="closeEditClientModal" class="mr-3">Cancelar</SecondaryButton>
                <PrimaryButton class="!bg-indigo-600 !hover:bg-indigo-700" :class="{ 'opacity-25': editClientForm.processing }" :disabled="editClientForm.processing" @click="saveClient">
                    Guardar Cambios
                </PrimaryButton>
            </template>
        </DialogModal>

        <!-- Modal for Computer -->
        <DialogModal :show="serviceModalOpen" @close="closeServiceModal">
            <template #title>
                {{ editingService ? 'Editar Servicio Adicional' : 'Nuevo Servicio Adicional' }}
            </template>

            <template #content>
                <div class="space-y-4">
                    <div>
                        <InputLabel for="service_name" value="Servicio Adicional" />
                        <select id="service_name" v-model="serviceForm.name" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" :disabled="!!editingService">
                            <option value="">-- Seleccionar --</option>
                            <option v-for="rate in serviceRates" :key="'opt-'+rate.id" :value="rate.name">
                                {{ rate.name }}
                            </option>
                        </select>
                        <InputError :message="serviceForm.errors.name" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="service_price" value="Precio Acordado" />
                        <TextInput id="service_price" v-model="serviceForm.price" type="number" step="0.01" class="mt-1 block w-full" />
                        <InputError :message="serviceForm.errors.price" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="service_expiration" value="Fecha de Vencimiento" />
                        <TextInput id="service_expiration" v-model="serviceForm.expiration_date" type="date" class="mt-1 block w-full" />
                        <InputError :message="serviceForm.errors.expiration_date" class="mt-2" />
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeServiceModal" class="mr-3">
                    Cancelar
                </SecondaryButton>
                <PrimaryButton @click="saveService" :disabled="serviceForm.processing">
                    Guardar
                </PrimaryButton>
            </template>
        </DialogModal>
        <!-- Folio Confirmation Modal -->
        <DialogModal :show="folioConfirmationModalOpen" @close="folioConfirmationModalOpen = false">
            <template #title>
                Confirmar Asignación de Folios
            </template>
            <template #content>
                <div class="text-sm text-gray-600 mb-4">
                    Estás asignando o modificando el plan a <strong>{{ companyForm.plan_documents }} folios</strong> para el cliente <strong>{{ client.name }}</strong>.
                </div>
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-4">
                    <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Distribuidor Asignado</p>
                    <p class="font-bold text-gray-900">{{ client.distributor ? client.distributor.name : 'Cliente Directo' }}</p>
                </div>
                <div class="bg-indigo-50 p-4 rounded-lg border border-indigo-100">
                    <p class="text-xs text-indigo-500 uppercase font-bold tracking-wider">Valor Estimado del Paquete</p>
                    <p class="text-xl font-bold text-indigo-700">${{ formatNumber(estimatedFolioCost) }} COP</p>
                </div>
                <p class="mt-4 text-sm text-gray-600">
                    ¿Deseas generar una cuenta por cobrar pendiente por este valor, o solo asignar los folios (ej. si ya te los pagaron por adelantado)?
                </p>
            </template>
            <template #footer>
                <SecondaryButton @click="confirmFolioSync(false)" class="mr-3">
                    Solo Asignar (No cobrar)
                </SecondaryButton>
                <PrimaryButton @click="confirmFolioSync(true)" class="!bg-indigo-600 !hover:bg-indigo-700" :disabled="companyForm.processing">
                    Generar Cobro Pendiente
                </PrimaryButton>
            </template>
        </DialogModal>
    </AppLayout>
</template>


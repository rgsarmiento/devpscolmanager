<script setup>
import { watchEffect } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const page = usePage();

const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 4000,
  timerProgressBar: true,
  customClass: {
    popup: 'rounded-xl shadow-lg border border-gray-100',
    title: 'text-sm font-medium text-slate-700'
  },
  didOpen: (toast) => {
    toast.onmouseenter = Swal.stopTimer;
    toast.onmouseleave = Swal.resumeTimer;
  }
});

watchEffect(() => {
    if (page.props.jetstream.flash?.banner) {
        const style = page.props.jetstream.flash?.bannerStyle || 'success';
        const message = page.props.jetstream.flash?.banner || '';
        
        let icon = 'success';
        if (style === 'danger') icon = 'error';
        if (style === 'warning') icon = 'warning';
        if (style === 'info') icon = 'info';
        
        Toast.fire({
            icon: icon,
            title: message
        });
        
        // Limpiar el flash para evitar que se repita si Vue reactiva la vista
        page.props.jetstream.flash.banner = null;
    }
});
</script>

<template>
    <div style="display: none;"></div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    modelValue: [String, Number],
    options: {
        type: Array,
        default: () => [],
    },
    label: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Seleccione...',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    error: String,
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const query = ref('');
const inputRef = ref(null);

// Initialize query based on initial modelValue
onMounted(() => {
    initializeQuery();
});

watch(() => props.modelValue, () => {
    initializeQuery();
});

const initializeQuery = () => {
    const selected = props.options.find(option => option.id == props.modelValue);
    if (selected) {
        query.value = selected.name;
    } else {
        query.value = '';
    }
};

const filteredOptions = computed(() => {
    if (query.value === '') {
        return props.options;
    }
    return props.options.filter((option) => {
        return option.name.toLowerCase().includes(query.value.toLowerCase());
    });
});

const selectOption = (option) => {
    query.value = option.name;
    emit('update:modelValue', option.id);
    isOpen.value = false;
};

const toggleDropdown = () => {
    if (props.disabled) return;
    if (isOpen.value) {
        // If closing, ensure query matches selection or clear it
        initializeQuery();
    } else {
        // If opening, maybe clear query to show all? Or keep it? 
        // For verify, usually we want to see what's there. 
        // Let's keep it, but select all text for easy replacement
        if(!props.modelValue) query.value = '';
    }
    isOpen.value = !isOpen.value;
};

const handleInput = (e) => {
    isOpen.value = true;
    // If user clears input, clear selection
    if (e.target.value === '') {
        emit('update:modelValue', '');
    }
};

const closeDropdown = () => {
    // Small timeout to allow click event on option to fire first
    setTimeout(() => {
        isOpen.value = false;
        initializeQuery();
    }, 200);
};

</script>

<template>
    <div class="relative">
        <InputLabel v-if="label" :value="label" class="mb-1" />
        
        <div class="relative">
            <input
                ref="inputRef"
                type="text"
                :value="query"
                @input="query = $event.target.value; handleInput($event)"
                @focus="isOpen = true"
                @blur="closeDropdown"
                :placeholder="placeholder"
                :disabled="disabled"
                class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm disabled:bg-gray-100 disabled:cursor-not-allowed"
                :class="{ 'border-red-500': error }"
            />
            
            <!-- Chevron Icon -->
            <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                    <path d="M7 7l3-3 3 3m0 6l-3 3-3-3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>

        <!-- Dropdown -->
        <div v-if="isOpen && !disabled" class="absolute z-50 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
            <ul v-if="filteredOptions.length > 0">
                <li
                    v-for="option in filteredOptions"
                    :key="option.id"
                    @click="selectOption(option)"
                    class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-indigo-600 hover:text-white text-gray-900"
                >
                    <span class="block truncate" :class="{ 'font-semibold': modelValue === option.id }">
                        {{ option.name }}
                    </span>
                    
                    <span v-if="modelValue === option.id" class="absolute inset-y-0 right-0 flex items-center pr-4 text-indigo-600 hover:text-white">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </li>
            </ul>
            <div v-else class="py-2 px-3 text-gray-500 text-sm">
                No se encontraron resultados
            </div>
        </div>

        <p v-if="error" class="text-sm text-red-600 mt-2">{{ error }}</p>
    </div>
</template>

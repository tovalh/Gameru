<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

// Usamos el helper 'useForm' de Inertia. Es perfecto para esto.
// Maneja el estado, los errores de validación y el progreso de subida automáticamente.
const form = useForm({
    game_name: '',
    rulebook_pdf: null,
});

// Referencia para saber si el usuario está arrastrando un archivo sobre la zona.
const isDragging = ref(false);

// Referencia para el input de archivo (para poder abrirlo programáticamente).
const fileInput = ref(null);

// Propiedad computada para obtener el nombre del archivo seleccionado.
const fileName = computed(() => form.rulebook_pdf?.name);

// Propiedad computada para obtener el tamaño del archivo en un formato legible.
const fileSize = computed(() => {
    if (!form.rulebook_pdf) return '';
    const sizeInMB = form.rulebook_pdf.size / (1024 * 1024);
    return `${sizeInMB.toFixed(2)} MB`;
});

// Función para manejar la selección de archivos desde el botón.
function handleFileChange(event) {
    const file = event.target.files[0];
    if (file) {
        form.rulebook_pdf = file;
    }
}

// Función para manejar los archivos soltados en la zona.
function handleDrop(event) {
    isDragging.value = false;
    const file = event.dataTransfer.files[0];
    if (file && file.type === 'application/pdf') {
        form.rulebook_pdf = file;
    } else {
        alert('Por favor, sube solo archivos PDF.');
    }
}

// Función para limpiar la selección de archivo.
function clearFile() {
    form.rulebook_pdf = null;
    form.clearErrors('rulebook_pdf');
    // Reseteamos el input para poder seleccionar el mismo archivo de nuevo si es necesario.
    if(fileInput.value) {
        fileInput.value.value = '';
    }
}

// Función que se ejecuta al enviar el formulario.
function submit() {
    // Aquí defines la ruta del backend a la que se enviarán los datos.
    // Asegúrate de crear esta ruta en tu archivo `routes/web.php`.
    form.post(route('rulebooks.store'), {
        onSuccess: () => {
            // Limpiamos el formulario si la subida es exitosa.
            form.reset();
            clearFile();
            // Aquí podrías mostrar una notificación de éxito.
        },
        onError: () => {
            // Los errores de validación de Laravel se mostrarán automáticamente.
        }
    });
}
</script>

<template>
    <Head title="Subir Manual" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Subir Nuevo Manual de Juego
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 md:p-8 text-gray-900 dark:text-gray-100">

                        <form @submit.prevent="submit" class="space-y-6">

                            <!-- Campo para el Nombre del Juego -->
                            <div>
                                <label for="game_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Nombre del Juego
                                </label>
                                <input
                                    type="text"
                                    id="game_name"
                                    v-model="form.game_name"
                                    class="w-full bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Ej: Gloomhaven"
                                    required
                                >
                                <div v-if="form.errors.game_name" class="text-sm text-red-500 mt-1">
                                    {{ form.errors.game_name }}
                                </div>
                            </div>

                            <!-- Zona para Subir Archivo -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Manual (PDF)
                                </label>

                                <!-- Vista cuando no hay archivo seleccionado -->
                                <div v-if="!form.rulebook_pdf"
                                     @dragenter.prevent="isDragging = true"
                                     @dragleave.prevent="isDragging = false"
                                     @dragover.prevent
                                     @drop.prevent="handleDrop"
                                     :class="[
                                        'mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-md transition-colors duration-300',
                                        isDragging ? 'border-indigo-500 bg-indigo-50 dark:bg-gray-700' : 'hover:border-gray-400 dark:hover:border-gray-500'
                                    ]"
                                >
                                    <div class="space-y-1 text-center">
                                        <!-- Icono SVG -->
                                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div class="flex text-sm text-gray-600 dark:text-gray-400">
                                            <label for="file-upload" class="relative cursor-pointer bg-white dark:bg-gray-800 rounded-md font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                                <span>Selecciona un archivo</span>
                                                <input id="file-upload" name="file-upload" type="file" class="sr-only" @change="handleFileChange" accept=".pdf" ref="fileInput">
                                            </label>
                                            <p class="pl-1">o arrástralo aquí</p>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-500">
                                            Solo archivos PDF
                                        </p>
                                    </div>
                                </div>

                                <!-- Vista cuando ya hay un archivo seleccionado -->
                                <div v-else class="mt-2 p-4 border border-gray-200 dark:border-gray-700 rounded-md bg-gray-50 dark:bg-gray-900/20">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <div class="text-sm">
                                                <p class="font-medium text-gray-800 dark:text-gray-200">{{ fileName }}</p>
                                                <p class="text-gray-500">{{ fileSize }}</p>
                                            </div>
                                        </div>
                                        <button @click="clearFile" type="button" class="text-gray-500 hover:text-red-600 dark:hover:text-red-400 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Barra de Progreso -->
                                    <div v-if="form.progress" class="mt-3">
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                            <div class="bg-blue-600 h-2.5 rounded-full" :style="{ width: form.progress.percentage + '%' }"></div>
                                        </div>
                                        <p class="text-xs text-right text-gray-500 mt-1">{{ form.progress.percentage }}%</p>
                                    </div>
                                </div>
                                <div v-if="form.errors.rulebook_pdf" class="text-sm text-red-500 mt-1">
                                    {{ form.errors.rulebook_pdf }}
                                </div>
                            </div>

                            <!-- Botón de Envío -->
                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    :class="[
                                        'inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring focus:ring-indigo-300 disabled:opacity-25 transition',
                                        form.processing ? 'cursor-not-allowed' : ''
                                    ]"
                                >
                                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ form.processing ? 'Subiendo...' : 'Subir Manual' }}
                                </button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

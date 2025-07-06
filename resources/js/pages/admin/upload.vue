<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue'; // <-- ¡CAMBIO IMPORTANTE! Usamos tu layout principal.
import { Button } from '@/components/ui/button';  // <-- Usamos tu componente de botón.

// El helper 'useForm' de Inertia es perfecto para esto.
const form = useForm({
    game_name: '',
    rulebook_pdf: null,
});

const isDragging = ref(false);
const fileInput = ref(null);

const fileName = computed(() => form.rulebook_pdf?.name);
const fileSize = computed(() => {
    if (!form.rulebook_pdf) return '';
    const sizeInMB = form.rulebook_pdf.size / (1024 * 1024);
    return `${sizeInMB.toFixed(2)} MB`;
});

function handleFileChange(event) {
    const file = event.target.files[0];
    if (file && file.type === 'application/pdf') {
        form.rulebook_pdf = file;
    } else {
        form.setError('rulebook_pdf', 'Por favor, sube solo archivos PDF.');
    }
}

function handleDrop(event) {
    isDragging.value = false;
    const file = event.dataTransfer.files[0];
    if (file && file.type === 'application/pdf') {
        form.rulebook_pdf = file;
    } else {
        alert('Por favor, sube solo archivos PDF.');
    }
}

function clearFile() {
    form.rulebook_pdf = null;
    form.clearErrors('rulebook_pdf');
    if(fileInput.value) {
        fileInput.value.value = '';
    }
}

function submit() {
    form.post(route('rulebooks.store'), {
        onSuccess: () => {
            form.reset();
            clearFile();
            // Aquí puedes añadir una notificación de éxito con tu sistema de UI
        },
    });
}
</script>

<template>
    <Head title="Subir Manual" />

    <AppLayout>
        <div class="px-4 py-6 sm:px-6 lg:px-8">
            <header class="mb-8">
                <h2 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">
                    Subir Nuevo Manual de Juego
                </h2>
                <p class="text-sm text-muted-foreground">
                    Añade un nuevo juego y su manual en formato PDF a la base de datos.
                </p>
            </header>

            <div class="max-w-3xl">
                <div class="overflow-hidden rounded-lg border bg-card text-card-foreground shadow-sm">
                    <div class="p-6 md:p-8">

                        <form @submit.prevent="submit" class="space-y-8">

                            <div>
                                <label for="game_name" class="block text-sm font-medium mb-2">
                                    Nombre del Juego
                                </label>
                                <input
                                    type="text"
                                    id="game_name"
                                    v-model="form.game_name"
                                    class="border-input flex h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                    placeholder="Ej: Gloomhaven"
                                    required
                                >
                                <div v-if="form.errors.game_name" class="text-sm text-destructive mt-2">
                                    {{ form.errors.game_name }}
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">
                                    Manual (PDF)
                                </label>

                                <div v-if="!form.rulebook_pdf"
                                     @dragenter.prevent="isDragging = true"
                                     @dragleave.prevent="isDragging = false"
                                     @dragover.prevent
                                     @drop.prevent="handleDrop"
                                     :class="[
                                        'mt-1 flex justify-center rounded-lg border-2 border-dashed border-border px-6 pt-5 pb-6 transition-colors duration-300',
                                        isDragging ? 'border-primary bg-accent' : 'hover:border-primary/50'
                                    ]"
                                >
                                    <div class="space-y-1 text-center">
                                        <svg class="mx-auto h-12 w-12 text-muted-foreground" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div class="flex text-sm text-muted-foreground">
                                            <label for="file-upload" class="relative cursor-pointer rounded-md font-medium text-primary hover:text-primary/80 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary">
                                                <span>Selecciona un archivo</span>
                                                <input id="file-upload" name="file-upload" type="file" class="sr-only" @change="handleFileChange" accept=".pdf" ref="fileInput">
                                            </label>
                                            <p class="pl-1">o arrástralo aquí</p>
                                        </div>
                                        <p class="text-xs text-muted-foreground/80">
                                            Solo archivos PDF
                                        </p>
                                    </div>
                                </div>

                                <div v-else class="mt-2 rounded-lg border bg-secondary/50 p-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-destructive" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <div class="text-sm">
                                                <p class="font-medium text-foreground">{{ fileName }}</p>
                                                <p class="text-muted-foreground">{{ fileSize }}</p>
                                            </div>
                                        </div>
                                        <button @click="clearFile" type="button" class="text-muted-foreground hover:text-destructive transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>

                                    <div v-if="form.progress" class="mt-3">
                                        <div class="w-full bg-muted rounded-full h-2.5">
                                            <div class="bg-primary h-2.5 rounded-full" :style="{ width: form.progress.percentage + '%' }"></div>
                                        </div>
                                        <p class="text-xs text-right text-muted-foreground mt-1">{{ form.progress.percentage }}%</p>
                                    </div>
                                </div>
                                <div v-if="form.errors.rulebook_pdf" class="text-sm text-destructive mt-2">
                                    {{ form.errors.rulebook_pdf }}
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <Button type="submit" :disabled="form.processing">
                                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ form.processing ? 'Subiendo...' : 'Guardar Manual' }}
                                </Button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

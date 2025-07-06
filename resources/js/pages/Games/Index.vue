<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineProps({
    games: Array,
});
</script>

<template>
    <Head title="Mis Juegos" />

    <AppLayout>
        <div class="px-4 py-6 sm:px-6 lg:px-8">
            <header class="mb-8">
                <h2 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">
                    Mis Juegos
                </h2>
                <p class="text-sm text-muted-foreground">
                    Selecciona un juego para empezar a preguntar sobre sus reglas.
                </p>
            </header>

            <div v-if="games.length > 0" class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                <Link v-for="game in games" :key="game.id" :href="route('chat.show', game.id)" class="overflow-hidden rounded-xl border bg-card text-card-foreground shadow-sm transition-all hover:-translate-y-1 hover:shadow-lg">
                    <div class="p-6">
                        <h3 class="font-semibold">{{ game.name }}</h3>
                    </div>
                </Link>
            </div>
            <div v-else class="text-center py-12 border-2 border-dashed rounded-lg">
                <p class="text-muted-foreground">No tienes manuales procesados todavía.</p>
                <p class="text-sm text-muted-foreground/80">Sube un nuevo manual para empezar.</p>
            </div>
        </div>
    </AppLayout>
</template>


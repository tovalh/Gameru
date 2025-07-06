<script setup>
import { ref, nextTick, onMounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { SendHorizonal } from 'lucide-vue-next';

const props = defineProps({
    game: Object,
    conversation: Object,
});

const messages = ref(props.conversation.messages);
const chatContainer = ref(null);

const form = useForm({
    prompt: '',
});

const scrollToBottom = () => {
    nextTick(() => {
        if (chatContainer.value) {
            chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
        }
    });
};

onMounted(() => {
    scrollToBottom();
});

const submit = () => {
    if (form.processing || !form.prompt.trim()) return;

    const userMessage = {
        id: Date.now(),
        role: 'user',
        content: form.prompt,
        created_at: new Date().toISOString(),
    };
    messages.value.push(userMessage);
    scrollToBottom();

    const currentPrompt = form.prompt;
    form.reset();

    axios.post(route('chat.ask', props.game.id), { prompt: currentPrompt })
        .then(response => {
            messages.value.push(response.data);
            scrollToBottom();
        })
        .catch(error => {
            console.error("Error al preguntar a la IA:", error);
            const errorMessage = {
                id: Date.now(),
                role: 'ai',
                content: 'Lo siento, ha ocurrido un error. Por favor, inténtalo de nuevo.',
                created_at: new Date().toISOString(),
            };
            messages.value.push(errorMessage);
            scrollToBottom();
        });
};
</script>

<template>
    <Head :title="`Chat - ${game.name}`" />

    <AppLayout>
        <div class="flex h-[calc(100vh-8rem)] flex-col">
            <header class="border-b p-4">
                <h2 class="text-lg font-semibold">{{ game.name }}</h2>
                <p class="text-sm text-muted-foreground">Pregúntale cualquier cosa sobre las reglas.</p>
            </header>

            <div ref="chatContainer" class="flex-1 overflow-y-auto p-4 space-y-6">
                <div v-for="message in messages" :key="message.id" class="flex gap-3" :class="{'justify-end': message.role === 'user'}">
                    <div class="max-w-xl rounded-lg p-3" :class="{
                            'bg-primary text-primary-foreground': message.role === 'user',
                            'bg-muted': message.role === 'ai'
                        }">
                        <p class="whitespace-pre-wrap">{{ message.content }}</p>
                    </div>
                </div>
            </div>

            <div class="border-t p-4">
                <form @submit.prevent="submit" class="flex items-center gap-2">
                    <Input v-model="form.prompt" placeholder="Escribe tu pregunta sobre las reglas..." autocomplete="off" />
                    <Button type="submit" size="icon" :disabled="form.processing">
                        <SendHorizonal class="h-4 w-4" />
                    </Button>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

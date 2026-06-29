<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

interface TaskItem {
    id: number;
    title: string;
    notes: string | null;
    bucket: string;
    status: string;
    priority: number;
    due_date: string | null;
    completed_at: string | null;
    position: number;
}

defineProps<{
    important: TaskItem[];
    eventual: TaskItem[];
}>();
</script>

<template>
    <AppLayout>
        <div class="p-4">
            <h1 class="text-xl font-semibold">Tasks</h1>
            <pre>{{ { important, eventual } }}</pre>
        </div>
    </AppLayout>
</template>

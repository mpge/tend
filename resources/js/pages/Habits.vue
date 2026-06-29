<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

interface HabitItem {
    id: number;
    name: string;
    cadence: string;
    target_per_period: number;
    color: string;
    icon: string | null;
    position: number;
    current_streak: number;
    longest_streak: number;
    done_today: boolean;
    entries: string[];
}

defineProps<{
    habits: HabitItem[];
    today: string;
}>();
</script>

<template>
    <AppLayout>
        <div class="p-4">
            <h1 class="text-xl font-semibold">Habits</h1>
            <pre>{{ { today, habits } }}</pre>
        </div>
    </AppLayout>
</template>

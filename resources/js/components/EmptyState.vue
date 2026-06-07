<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    icon?: Component;
    title: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex flex-col items-center gap-3 py-12 text-center">
        <div v-if="icon" class="flex size-12 items-center justify-center rounded-full bg-muted">
            <component :is="icon" class="size-5 text-muted-foreground" />
        </div>
        <div>
            <p class="text-sm font-medium">{{ title }}</p>
            <p v-if="description" class="mt-1 text-xs text-muted-foreground">{{ description }}</p>
        </div>
        <slot />
    </div>
</template>

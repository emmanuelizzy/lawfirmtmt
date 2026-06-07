<script setup lang="ts">
import { Check, ChevronsUpDown, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    Combobox,
    ComboboxAnchor,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxList,
    ComboboxViewport,
} from '@/components/ui/combobox';
import { cn } from '@/lib/utils';

interface User {
    id: string;
    name: string;
}

const props = withDefaults(defineProps<{
    users: User[];
    modelValue: string | null;
    placeholder?: string;
    name?: string;
}>(), {
    placeholder: 'Search for a user…',
    name: 'assigned_to',
});

const emit = defineEmits<{
    'update:modelValue': [value: string | null];
}>();

const searchQuery = ref('');

const selectedUser = computed(() =>
    props.modelValue ? props.users.find((u) => u.id === props.modelValue) ?? null : null,
);

const filteredUsers = computed(() => {
    const q = searchQuery.value.toLowerCase().trim();
    if (!q) return props.users;
    return props.users.filter((u) => u.name.toLowerCase().includes(q));
});

function getInitials(name: string) {
    return name
        .split(' ')
        .map((n) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
}

function onSelect(userId: string | undefined) {
    if (!userId) return;
    emit('update:modelValue', userId === props.modelValue ? null : userId);
    searchQuery.value = '';
}

function clear(e: Event) {
    e.stopPropagation();
    emit('update:modelValue', null);
    searchQuery.value = '';
}
</script>

<template>
    <!-- Hidden input to carry the value in a native form submission -->
    <input :name="name" type="hidden" :value="modelValue ?? ''" />

    <Combobox
        :model-value="modelValue ?? undefined"
        :filter-function="() => filteredUsers.map((u) => u.id)"
        @update:model-value="onSelect"
    >
        <ComboboxAnchor class="w-full">
            <div
                :class="cn(
                    'border-input flex h-9 w-full items-center justify-between rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs transition-colors',
                    'focus-within:ring-ring/50 focus-within:ring-[3px]',
                )"
            >
                <div v-if="selectedUser" class="flex items-center gap-2">
                    <Avatar class="size-5">
                        <AvatarFallback class="text-[10px]">
                            {{ getInitials(selectedUser.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <span class="text-sm">{{ selectedUser.name }}</span>
                </div>
                <ComboboxInput
                    v-else
                    v-model="searchQuery"
                    :placeholder="placeholder"
                    class="h-full flex-1 bg-transparent outline-none placeholder:text-muted-foreground"
                    autocomplete="off"
                />
                <div class="flex items-center gap-0.5">
                    <button
                        v-if="selectedUser"
                        type="button"
                        class="text-muted-foreground hover:text-foreground"
                        tabindex="-1"
                        @click="clear"
                    >
                        <X class="size-3.5" />
                    </button>
                    <ChevronsUpDown class="size-4 shrink-0 text-muted-foreground" />
                </div>
            </div>
        </ComboboxAnchor>

        <ComboboxList class="w-[--reka-combobox-trigger-width] min-w-52">
            <ComboboxInput
                v-if="selectedUser"
                v-model="searchQuery"
                placeholder="Search…"
                class="border-b px-3 py-2 text-sm outline-none placeholder:text-muted-foreground"
            />
            <ComboboxEmpty class="py-6 text-center text-sm text-muted-foreground">
                No users found.
            </ComboboxEmpty>
            <ComboboxViewport class="p-1">
                <ComboboxGroup>
                    <ComboboxItem
                        v-for="user in filteredUsers"
                        :key="user.id"
                        :value="user.id"
                        class="flex cursor-pointer items-center gap-2 px-2 py-1.5"
                    >
                        <Avatar class="size-6">
                            <AvatarFallback class="text-[10px]">
                                {{ getInitials(user.name) }}
                            </AvatarFallback>
                        </Avatar>
                        <span class="flex-1 text-sm">{{ user.name }}</span>
                        <ComboboxItemIndicator>
                            <Check class="size-4 text-primary" />
                        </ComboboxItemIndicator>
                    </ComboboxItem>
                </ComboboxGroup>
            </ComboboxViewport>
        </ComboboxList>
    </Combobox>
</template>

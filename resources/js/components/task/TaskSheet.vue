<script setup lang="ts">
import { Form, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import * as TaskController from '@/actions/App/Http/Controllers/Task/TaskController';
import UserSearchCombobox from '@/components/UserSearchCombobox.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Textarea } from '@/components/ui/textarea';
import type { Task } from '@/types';

interface Props {
    open: boolean;
    projectId: string;
    task?: Task | null;
    users: Array<{ id: string; name: string }>;
    statuses: Array<{ value: string; label: string }>;
    priorities: Array<{ value: string; label: string }>;
}

const props = withDefaults(defineProps<Props>(), {
    task: null,
});

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const isEditing = computed(() => !!props.task);

// Assignee is managed via v-model, not a native input, so we track it here
// and inject a hidden input inside the Form via UserSearchCombobox
const assigneeId = ref<string | null>(null);

watch(
    () => props.task,
    (task) => {
        assigneeId.value = task?.assignee?.id ?? null;
    },
    { immediate: true },
);

function close() {
    emit('update:open', false);
}

function onSuccess() {
    close();
}
</script>

<template>
    <Sheet :open="open" @update:open="emit('update:open', $event)">
        <SheetContent class="flex w-full flex-col gap-0 p-0 sm:max-w-lg">
            <SheetHeader class="px-6 pt-6 pb-4">
                <SheetTitle>{{ isEditing ? 'Edit task' : 'New task' }}</SheetTitle>
                <SheetDescription>
                    <template v-if="isEditing">
                        Update the details for this task.
                    </template>
                    <template v-else>
                        Add a new task to this project.
                    </template>
                </SheetDescription>
            </SheetHeader>

            <Separator />

            <div class="flex-1 overflow-y-auto">
                <Form
                    v-bind="isEditing
                        ? TaskController.update.form(task!.id)
                        : TaskController.store.form(projectId)"
                    class="flex flex-col gap-5 px-6 py-5"
                    v-slot="{ errors, processing }"
                    @success="onSuccess"
                >
                    <!-- Title -->
                    <div class="grid gap-2">
                        <Label for="task-title">
                            Title <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            id="task-title"
                            name="title"
                            :default-value="task?.title"
                            placeholder="e.g. Review discovery documents"
                            required
                            autofocus
                        />
                        <InputError :message="errors.title" />
                    </div>

                    <!-- Description -->
                    <div class="grid gap-2">
                        <Label for="task-description">Description</Label>
                        <Textarea
                            id="task-description"
                            name="description"
                            :default-value="task?.description ?? undefined"
                            placeholder="Additional context or notes…"
                            class="min-h-20 resize-none"
                        />
                        <InputError :message="errors.description" />
                    </div>

                    <!-- Status + Priority -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="task-status">
                                Status <span class="text-destructive">*</span>
                            </Label>
                            <Select
                                name="status"
                                :default-value="task?.status.value ?? 'TODO'"
                                required
                            >
                                <SelectTrigger id="task-status">
                                    <SelectValue placeholder="Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem
                                            v-for="s in statuses"
                                            :key="s.value"
                                            :value="s.value"
                                        >
                                            {{ s.label }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.status" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="task-priority">
                                Priority <span class="text-destructive">*</span>
                            </Label>
                            <Select
                                name="priority"
                                :default-value="task?.priority.value ?? 'MEDIUM'"
                                required
                            >
                                <SelectTrigger id="task-priority">
                                    <SelectValue placeholder="Priority" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem
                                            v-for="p in priorities"
                                            :key="p.value"
                                            :value="p.value"
                                        >
                                            {{ p.label }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.priority" />
                        </div>
                    </div>

                    <!-- Due date -->
                    <div class="grid gap-2">
                        <Label for="task-due-date">Due date</Label>
                        <Input
                            id="task-due-date"
                            name="due_date"
                            type="date"
                            :default-value="task?.due_date ?? undefined"
                        />
                        <InputError :message="errors.due_date" />
                    </div>

                    <!-- Assignee -->
                    <div class="grid gap-2">
                        <Label>Assignee</Label>
                        <UserSearchCombobox
                            v-model="assigneeId"
                            name="assigned_to"
                            :users="users"
                        />
                        <InputError :message="errors.assigned_to" />
                    </div>

                    <Separator />

                    <div class="flex items-center justify-end gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            :disabled="processing"
                            @click="close"
                        >
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="processing">
                            <span v-if="processing">
                                {{ isEditing ? 'Saving…' : 'Creating…' }}
                            </span>
                            <span v-else>
                                {{ isEditing ? 'Save changes' : 'Create task' }}
                            </span>
                        </Button>
                    </div>
                </Form>
            </div>
        </SheetContent>
    </Sheet>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { CalendarDays, Clock, Pencil, Trash2, User } from '@lucide/vue';
import { ref } from 'vue';
import * as TaskController from '@/actions/App/Http/Controllers/Task/TaskController';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import type { Task } from '@/types';

const props = defineProps<{
    open: boolean;
    task: Task | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    edit: [task: Task];
}>();

const showDeleteDialog = ref(false);
const isDeleting = ref(false);

type StatusValue = Task['status']['value'];
type PriorityValue = Task['priority']['value'];

function getStatusVariant(status: StatusValue) {
    const map: Record<StatusValue, 'default' | 'secondary' | 'outline' | 'destructive'> = {
        TODO:        'secondary',
        IN_PROGRESS: 'default',
        REVIEW:      'outline',
        DONE:        'outline',
    };
    return map[status] ?? 'secondary';
}

function getPriorityVariant(priority: PriorityValue) {
    const map: Record<PriorityValue, 'default' | 'secondary' | 'outline' | 'destructive'> = {
        LOW:    'secondary',
        MEDIUM: 'outline',
        HIGH:   'default',
        URGENT: 'destructive',
    };
    return map[priority] ?? 'secondary';
}

function getInitials(name: string) {
    return name
        .split(' ')
        .map((n) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
}

function formatDate(dateStr: string) {
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(dateStr.replace(' ', 'T')));
}

function executeDelete() {
    if (!props.task) return;
    isDeleting.value = true;
    router.delete(TaskController.destroy.url(props.task.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteDialog.value = false;
            emit('update:open', false);
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
}
</script>

<template>
    <Sheet :open="open" @update:open="emit('update:open', $event)">
        <SheetContent class="flex w-full flex-col gap-0 p-0 sm:max-w-lg" v-if="task">
            <SheetHeader class="px-6 pt-6 pb-4">
                <div class="flex items-start justify-between gap-3">
                    <SheetTitle class="text-base leading-snug">{{ task.title }}</SheetTitle>
                    <div class="flex shrink-0 items-center gap-1.5">
                        <Button
                            variant="ghost"
                            size="icon"
                            class="size-8"
                            @click="emit('edit', task)"
                        >
                            <Pencil class="size-4" />
                            <span class="sr-only">Edit task</span>
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="size-8 text-destructive hover:text-destructive"
                            @click="showDeleteDialog = true"
                        >
                            <Trash2 class="size-4" />
                            <span class="sr-only">Delete task</span>
                        </Button>
                    </div>
                </div>
                <div class="mt-2 flex flex-wrap gap-2">
                    <Badge :variant="getStatusVariant(task.status.value)">
                        {{ task.status.label }}
                    </Badge>
                    <Badge :variant="getPriorityVariant(task.priority.value)">
                        {{ task.priority.label }}
                    </Badge>
                    <Badge v-if="task.is_overdue" variant="destructive">
                        Overdue
                    </Badge>
                </div>
            </SheetHeader>

            <Separator />

            <div class="flex-1 space-y-5 overflow-y-auto px-6 py-5">
                <!-- Description -->
                <div v-if="task.description" class="space-y-1">
                    <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        Description
                    </p>
                    <p class="text-sm leading-relaxed">{{ task.description }}</p>
                </div>

                <!-- Meta grid -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Assignee -->
                    <div class="space-y-1.5">
                        <p class="flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            <User class="size-3.5" />
                            Assignee
                        </p>
                        <div v-if="task.assignee" class="flex items-center gap-2">
                            <Avatar class="size-6">
                                <AvatarFallback class="text-[10px]">
                                    {{ getInitials(task.assignee.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <span class="text-sm">{{ task.assignee.name }}</span>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">Unassigned</p>
                    </div>

                    <!-- Due date -->
                    <div class="space-y-1.5">
                        <p class="flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            <CalendarDays class="size-3.5" />
                            Due date
                        </p>
                        <p
                            class="text-sm"
                            :class="task.is_overdue ? 'font-medium text-destructive' : ''"
                        >
                            {{ task.due_date ? formatDate(task.due_date) : '—' }}
                        </p>
                    </div>

                    <!-- Created -->
                    <div class="space-y-1.5">
                        <p class="flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            <Clock class="size-3.5" />
                            Created
                        </p>
                        <p class="text-sm">{{ formatDate(task.created_at) }}</p>
                    </div>
                </div>
            </div>
        </SheetContent>
    </Sheet>

    <!-- Delete confirmation -->
    <Dialog :open="showDeleteDialog" @update:open="(v) => !v && (showDeleteDialog = false)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Delete task</DialogTitle>
                <DialogDescription>
                    Are you sure you want to delete
                    <span class="font-medium text-foreground">{{ task?.title }}</span>?
                    This cannot be undone.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" :disabled="isDeleting" @click="showDeleteDialog = false">
                    Cancel
                </Button>
                <Button variant="destructive" :disabled="isDeleting" @click="executeDelete">
                    <span v-if="isDeleting">Deleting…</span>
                    <span v-else>Delete task</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

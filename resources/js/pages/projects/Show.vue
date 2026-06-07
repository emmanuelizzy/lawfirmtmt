<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CalendarDays,
    ClipboardList,
    Pencil,
    Trash2,
    User,
} from '@lucide/vue';
import { ref } from 'vue';
import * as ProjectController from '@/actions/App/Http/Controllers/Project/ProjectController';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import type { Project } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Projects',
                href: ProjectController.index.url(),
            },
        ],
    },
});

const { project } = defineProps<{
    project: Project;
}>();

const showDeleteDialog = ref(false);
const isDeleting = ref(false);

function getStatusVariant(status: Project['status']['value']) {
    const map: Record<Project['status']['value'], 'default' | 'secondary' | 'outline' | 'destructive'> = {
        ACTIVE:    'default',
        ON_HOLD:   'secondary',
        COMPLETED: 'outline',
        ARCHIVED:  'destructive',
    };
    return map[status] ?? 'secondary';
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
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(dateStr.replace(' ', 'T')));
}

function executeDelete() {
    isDeleting.value = true;
    router.delete(ProjectController.destroy.url(project.id), {
        preserveScroll: false,
        onFinish: () => {
            isDeleting.value = false;
            showDeleteDialog.value = false;
        },
    });
}
</script>

<template>
    <Head :title="project.title" />

    <div class="flex flex-1 flex-col gap-6 p-6">
        <!-- Back + Actions header -->
        <div class="flex items-center justify-between">
            <Button variant="ghost" size="sm" as-child class="-ml-2 text-muted-foreground">
                <Link :href="ProjectController.index.url()">
                    <ArrowLeft data-icon="inline-start" />
                    All projects
                </Link>
            </Button>

            <div class="flex items-center gap-2">
                <Button variant="outline" size="sm" as-child>
                    <Link :href="ProjectController.edit.url(project.id)">
                        <Pencil data-icon="inline-start" />
                        Edit
                    </Link>
                </Button>
                <Button
                    variant="destructive"
                    size="sm"
                    @click="showDeleteDialog = true"
                >
                    <Trash2 data-icon="inline-start" />
                    Delete
                </Button>
            </div>
        </div>

        <!-- Title + Badge -->
        <div class="flex flex-col gap-2">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-semibold tracking-tight">{{ project.title }}</h1>
                <Badge :variant="getStatusVariant(project.status.value)">
                    {{ project.status.label }}
                </Badge>
            </div>
            <p v-if="project.description" class="max-w-2xl text-sm text-muted-foreground">
                {{ project.description }}
            </p>
        </div>

        <!-- Meta cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <Card>
                <CardHeader class="flex flex-row items-center gap-3 space-y-0 pb-2">
                    <div class="flex size-8 items-center justify-center rounded-md bg-muted">
                        <User class="size-4 text-muted-foreground" />
                    </div>
                    <CardTitle class="text-sm font-medium text-muted-foreground">Lead attorney</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="project.lead" class="flex items-center gap-2">
                        <Avatar class="size-7">
                            <AvatarFallback class="text-xs">
                                {{ getInitials(project.lead.name) }}
                            </AvatarFallback>
                        </Avatar>
                        <span class="text-sm font-medium">{{ project.lead.name }}</span>
                    </div>
                    <span v-else class="text-sm text-muted-foreground">Unassigned</span>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center gap-3 space-y-0 pb-2">
                    <div class="flex size-8 items-center justify-center rounded-md bg-muted">
                        <ClipboardList class="size-4 text-muted-foreground" />
                    </div>
                    <CardTitle class="text-sm font-medium text-muted-foreground">Tasks</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold tabular-nums">
                        {{ project.tasks_count ?? 0 }}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center gap-3 space-y-0 pb-2">
                    <div class="flex size-8 items-center justify-center rounded-md bg-muted">
                        <CalendarDays class="size-4 text-muted-foreground" />
                    </div>
                    <CardTitle class="text-sm font-medium text-muted-foreground">Created</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-sm font-medium">{{ formatDate(project.created_at) }}</p>
                </CardContent>
            </Card>
        </div>

        <!-- Placeholder for tasks section (coming soon) -->
        <Card>
            <CardHeader>
                <CardTitle class="text-base">Tasks</CardTitle>
            </CardHeader>
            <Separator />
            <CardContent class="flex flex-col items-center justify-center gap-3 py-16">
                <div class="flex size-12 items-center justify-center rounded-full bg-muted">
                    <ClipboardList class="size-5 text-muted-foreground" />
                </div>
                <div class="space-y-1 text-center">
                    <p class="text-sm font-medium">No tasks yet</p>
                    <p class="text-sm text-muted-foreground">
                        Tasks for this project will appear here.
                    </p>
                </div>
            </CardContent>
        </Card>
    </div>

    <!-- Delete confirmation dialog -->
    <Dialog :open="showDeleteDialog" @update:open="(v) => !v && (showDeleteDialog = false)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Delete project</DialogTitle>
                <DialogDescription>
                    Are you sure you want to delete
                    <span class="font-medium text-foreground">{{ project.title }}</span>?
                    This action cannot be undone and will permanently remove the project and all its tasks.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" :disabled="isDeleting" @click="showDeleteDialog = false">
                    Cancel
                </Button>
                <Button variant="destructive" :disabled="isDeleting" @click="executeDelete">
                    <span v-if="isDeleting">Deleting…</span>
                    <span v-else>Delete project</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

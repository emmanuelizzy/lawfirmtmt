<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { FolderOpen, MoreHorizontal, Pencil, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import * as ProjectController from '@/actions/App/Http/Controllers/Project/ProjectController';
import type { PaginatedResponse } from '@/types';
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

const { projects } = defineProps<{
    projects: PaginatedResponse<Project>;
}>();

const projectToDelete = ref<Project | null>(null);
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

function confirmDelete(project: Project) {
    projectToDelete.value = project;
}

function cancelDelete() {
    projectToDelete.value = null;
}

function executeDelete() {
    if (!projectToDelete.value) return;

    isDeleting.value = true;
    router.delete(ProjectController.destroy.url(projectToDelete.value.id), {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
            projectToDelete.value = null;
        },
    });
}

function formatDate(dateStr: string) {
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(dateStr.replace(' ', 'T')));
}
</script>

<template>
    <Head title="Projects" />

    <div class="flex flex-1 flex-col gap-6 p-6">
        <Card>
            <CardHeader class="flex flex-row items-start justify-between gap-4 space-y-0">
                <div class="space-y-1">
                    <CardTitle class="text-xl">Projects</CardTitle>
                    <CardDescription>
                        Manage your law firm's matters and projects across all practice areas.
                    </CardDescription>
                </div>
                <Button as-child size="sm" class="shrink-0">
                    <Link :href="ProjectController.create.url()">
                        <Plus data-icon="inline-start" />
                        New Project
                    </Link>
                </Button>
            </CardHeader>

            <Separator />

            <CardContent class="p-0">
                <Table v-if="projects.data.length > 0">
                    <TableHeader>
                        <TableRow class="hover:bg-transparent">
                            <TableHead class="pl-6">Project</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Lead</TableHead>
                            <TableHead class="text-center">Tasks</TableHead>
                            <TableHead>Created</TableHead>
                            <TableHead class="w-12 pr-6" />
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <TableRow
                            v-for="project in projects.data"
                            :key="project.id"
                            class="group cursor-pointer"
                            @click="router.visit(ProjectController.show.url(project.id))"
                        >
                            <TableCell class="py-4 pl-6">
                                <div class="flex flex-col gap-0.5">
                                    <span class="font-medium leading-none text-foreground">
                                        {{ project.title }}
                                    </span>
                                    <span
                                        v-if="project.description"
                                        class="line-clamp-1 max-w-xs text-xs text-muted-foreground"
                                    >
                                        {{ project.description }}
                                    </span>
                                </div>
                            </TableCell>

                            <TableCell>
                                <Badge :variant="getStatusVariant(project.status.value)">
                                    {{ project.status.label }}
                                </Badge>
                            </TableCell>

                            <TableCell>
                                <div v-if="project.lead" class="flex items-center gap-2">
                                    <Avatar class="size-7">
                                        <AvatarFallback class="text-xs">
                                            {{ getInitials(project.lead.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <span class="text-sm text-muted-foreground">
                                        {{ project.lead.name }}
                                    </span>
                                </div>
                                <span v-else class="text-sm text-muted-foreground">—</span>
                            </TableCell>

                            <TableCell class="text-center">
                                <Badge
                                    variant="secondary"
                                    class="tabular-nums"
                                >
                                    {{ project.tasks_count ?? 0 }}
                                </Badge>
                            </TableCell>

                            <TableCell class="text-sm text-muted-foreground">
                                {{ formatDate(project.created_at) }}
                            </TableCell>

                            <TableCell class="pr-6 text-right" @click.stop>
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="size-8 opacity-0 group-hover:opacity-100"
                                        >
                                            <MoreHorizontal />
                                            <span class="sr-only">Open menu</span>
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" class="w-40">
                                        <DropdownMenuGroup>
                                            <DropdownMenuItem as-child>
                                                <Link :href="ProjectController.edit.url(project.id)">
                                                    <Pencil data-icon="inline-start" />
                                                    Edit
                                                </Link>
                                            </DropdownMenuItem>
                                        </DropdownMenuGroup>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuGroup>
                                            <DropdownMenuItem
                                                variant="destructive"
                                                @click="confirmDelete(project)"
                                            >
                                                <Trash2 data-icon="inline-start" />
                                                Delete
                                            </DropdownMenuItem>
                                        </DropdownMenuGroup>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Empty state -->
                <div v-else class="flex flex-col items-center justify-center gap-4 px-6 py-20">
                    <div class="flex size-16 items-center justify-center rounded-full bg-muted">
                        <FolderOpen class="size-8 text-muted-foreground" />
                    </div>
                    <div class="space-y-1 text-center">
                        <p class="text-sm font-medium">No projects yet</p>
                        <p class="text-sm text-muted-foreground">
                            Get started by creating your first project.
                        </p>
                    </div>
                    <Button as-child size="sm">
                        <Link :href="ProjectController.create.url()">
                            <Plus data-icon="inline-start" />
                            New Project
                        </Link>
                    </Button>
                </div>
            </CardContent>

            <!-- Pagination -->
            <template v-if="projects.data.length > 0 && projects.meta.last_page > 1">
                <Separator />
                <div class="flex items-center justify-between px-6 py-4">
                    <p class="text-sm text-muted-foreground">
                        Showing
                        <span class="font-medium text-foreground">{{ projects.meta.from }}</span>
                        –
                        <span class="font-medium text-foreground">{{ projects.meta.to }}</span>
                        of
                        <span class="font-medium text-foreground">{{ projects.meta.total }}</span>
                        projects
                    </p>
                    <div class="flex items-center gap-1">
                        <Button
                            v-for="link in projects.meta.links"
                            :key="link.label"
                            :variant="link.active ? 'default' : 'ghost'"
                            size="sm"
                            :disabled="!link.url"
                            class="h-8 min-w-8 px-2.5 text-xs"
                            @click="link.url && router.visit(link.url, { preserveScroll: true })"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </template>
        </Card>
    </div>

    <!-- Delete confirmation dialog -->
    <Dialog :open="!!projectToDelete" @update:open="(v) => !v && cancelDelete()">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Delete project</DialogTitle>
                <DialogDescription>
                    Are you sure you want to delete
                    <span class="font-medium text-foreground">{{ projectToDelete?.title }}</span>?
                    This action cannot be undone and will permanently remove the project and all its tasks.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" :disabled="isDeleting" @click="cancelDelete">
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

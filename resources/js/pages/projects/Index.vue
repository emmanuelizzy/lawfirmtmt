<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FolderOpen } from '@lucide/vue';
import type { Project } from '@/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import EmptyState from '@/components/EmptyState.vue';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index, create, show, edit, destroy } from '@/routes/projects';

interface Props {
    projects: {
        data: Project[];
        links: { url: string | null; label: string; active: boolean }[];
        meta: { current_page: number; last_page: number; total: number };
    };
}

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Projects', href: index() }],
    },
});

const statusVariant = (value: string) => {
    const map: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
        ACTIVE: 'default',
        ON_HOLD: 'secondary',
        COMPLETED: 'outline',
        ARCHIVED: 'destructive',
    };
    return map[value] ?? 'secondary';
};
</script>

<template>
    <Head title="Projects" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Projects</h1>
            <Button as-child>
                <Link :href="create()">New Project</Link>
            </Button>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>All Projects ({{ projects.meta.total }})</CardTitle>
            </CardHeader>
            <CardContent class="p-0">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Title</TableHead>
                            <TableHead>Lead</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="text-right">Tasks</TableHead>
                            <TableHead />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableEmpty v-if="projects.data.length === 0" :colspan="5">
                            <EmptyState
                                :icon="FolderOpen"
                                title="No projects yet"
                                description="Create your first project to get started."
                            >
                                <Button as-child size="sm">
                                    <Link :href="create()">New Project</Link>
                                </Button>
                            </EmptyState>
                        </TableEmpty>
                        <TableRow v-for="project in projects.data" :key="project.id">
                            <TableCell class="font-medium">
                                <Link :href="show(project.id)" class="hover:underline">
                                    {{ project.title }}
                                </Link>
                            </TableCell>
                            <TableCell>{{ project.lead.name }}</TableCell>
                            <TableCell>
                                <Badge :variant="statusVariant(project.status.value)">
                                    {{ project.status.label }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-right">{{ project.tasks_count ?? 0 }}</TableCell>
                            <TableCell class="text-right">
                                <Button variant="ghost" size="sm" as-child>
                                    <Link :href="edit(project.id)">Edit</Link>
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>

        <div v-if="projects.meta.last_page > 1" class="flex justify-end gap-2">
            <template v-for="link in projects.links" :key="link.label">
                <Button
                    v-if="link.url"
                    :variant="link.active ? 'default' : 'outline'"
                    size="sm"
                    as-child
                >
                    <Link :href="link.url" v-html="link.label" />
                </Button>
            </template>
        </div>
    </div>
</template>

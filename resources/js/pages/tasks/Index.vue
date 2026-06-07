<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ClipboardList } from '@lucide/vue';
import type { Task } from '@/types';
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
import { index as projectsIndex, show as projectShow } from '@/routes/projects';
import { index as tasksIndex, create as taskCreate } from '@/routes/projects.tasks';
import { edit as taskEdit, destroy as taskDestroy } from '@/routes/tasks';
import { useForm } from '@inertiajs/vue3';

interface ProjectSummary {
    id: string;
    title: string;
}

interface Props {
    project: ProjectSummary;
    tasks: {
        data: Task[];
        links: { url: string | null; label: string; active: boolean }[];
        meta: { current_page: number; last_page: number; total: number };
    };
}

const { project, tasks } = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Projects', href: projectsIndex() },
            { title: project.title, href: projectShow(project.id) },
            { title: 'Tasks', href: tasksIndex(project.id) },
        ],
    },
});

const priorityVariant = (value: string): 'default' | 'secondary' | 'outline' | 'destructive' => {
    const map: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
        LOW: 'secondary',
        MEDIUM: 'outline',
        HIGH: 'default',
        URGENT: 'destructive',
    };
    return map[value] ?? 'secondary';
};

const deleteForm = useForm({});
const deleteTask = (taskId: string) => {
    if (confirm('Delete this task?')) {
        deleteForm.submit(taskDestroy(taskId));
    }
};
</script>

<template>
    <Head :title="`${project.title} — Tasks`" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Tasks</h1>
            <Button as-child>
                <Link :href="taskCreate(project.id)">New Task</Link>
            </Button>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>{{ project.title }} ({{ tasks.meta.total }})</CardTitle>
            </CardHeader>
            <CardContent class="p-0">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Title</TableHead>
                            <TableHead>Assignee</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Priority</TableHead>
                            <TableHead>Due Date</TableHead>
                            <TableHead />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableEmpty v-if="tasks.data.length === 0" :colspan="6">
                            <EmptyState
                                :icon="ClipboardList"
                                title="No tasks yet"
                                description="Add the first task to this project."
                            >
                                <Button as-child size="sm">
                                    <Link :href="taskCreate(project.id)">New Task</Link>
                                </Button>
                            </EmptyState>
                        </TableEmpty>
                        <TableRow
                            v-for="task in tasks.data"
                            :key="task.id"
                            :class="{ 'bg-destructive/5': task.is_overdue }"
                        >
                            <TableCell class="font-medium">{{ task.title }}</TableCell>
                            <TableCell>{{ task.assignee?.name ?? '—' }}</TableCell>
                            <TableCell>
                                <Badge variant="outline">{{ task.status.label }}</Badge>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="priorityVariant(task.priority.value)">
                                    {{ task.priority.label }}
                                </Badge>
                            </TableCell>
                            <TableCell :class="{ 'text-destructive font-medium': task.is_overdue }">
                                {{ task.due_date ?? '—' }}
                            </TableCell>
                            <TableCell class="text-right">
                                <Button variant="ghost" size="sm" as-child>
                                    <Link :href="taskEdit(task.id)">Edit</Link>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="text-destructive"
                                    :disabled="deleteForm.processing"
                                    @click="deleteTask(task.id)"
                                >
                                    Delete
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>

        <div v-if="tasks.meta.last_page > 1" class="flex justify-end gap-2">
            <template v-for="link in tasks.links" :key="link.label">
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

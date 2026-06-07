<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import type { Project } from '@/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { index, show, edit } from '@/routes/projects';
import { index as tasksIndex } from '@/routes/projects.tasks';
import { destroy } from '@/actions/App/Http/Controllers/Project/ProjectController';
import { useForm } from '@inertiajs/vue3';

interface Props {
    project: Project;
}

const { project } = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Projects', href: index() },
            { title: project.title, href: show(project.id) },
        ],
    },
});

const deleteForm = useForm({});
const deleteProject = () => {
    if (confirm('Are you sure you want to delete this project?')) {
        deleteForm.submit(destroy(project.id));
    }
};
</script>

<template>
    <Head :title="project.title" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-semibold">{{ project.title }}</h1>
                <Badge :variant="project.status.value === 'ACTIVE' ? 'default' : 'secondary'">
                    {{ project.status.label }}
                </Badge>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link :href="tasksIndex(project.id)">View Tasks</Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="edit(project.id)">Edit</Link>
                </Button>
                <Button variant="destructive" @click="deleteProject" :disabled="deleteForm.processing">
                    Delete
                </Button>
            </div>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Project Details</CardTitle>
            </CardHeader>
            <CardContent class="flex flex-col gap-4">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="font-medium text-muted-foreground">Lead</p>
                        <p>{{ project.lead.name }}</p>
                    </div>
                    <div>
                        <p class="font-medium text-muted-foreground">Status</p>
                        <p>{{ project.status.label }}</p>
                    </div>
                    <div>
                        <p class="font-medium text-muted-foreground">Tasks</p>
                        <p>{{ project.tasks_count ?? 0 }}</p>
                    </div>
                </div>
                <template v-if="project.description">
                    <Separator />
                    <div>
                        <p class="mb-1 font-medium text-muted-foreground">Description</p>
                        <p class="whitespace-pre-wrap text-sm">{{ project.description }}</p>
                    </div>
                </template>
            </CardContent>
        </Card>
    </div>
</template>

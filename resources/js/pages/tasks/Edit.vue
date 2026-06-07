<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import type { Task, User } from '@/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/InputError.vue';
import { index as projectsIndex, show as projectShow } from '@/routes/projects';
import { index as tasksIndex } from '@/routes/projects.tasks';
import { edit as taskEdit, update as taskUpdate } from '@/routes/tasks';

interface SelectOption {
    value: string;
    label: string;
}

interface ProjectSummary {
    id: string;
    title: string;
}

interface Props {
    project: ProjectSummary;
    task: Task;
    statuses: SelectOption[];
    priorities: SelectOption[];
    users: Pick<User, 'id' | 'name'>[];
}

const { project, task } = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Projects', href: projectsIndex() },
            { title: project.title, href: projectShow(project.id) },
            { title: 'Tasks', href: tasksIndex(project.id) },
            { title: 'Edit Task', href: taskEdit(task.id) },
        ],
    },
});

const form = useForm({
    title: task.title,
    description: task.description ?? '',
    assigned_to: task.assignee?.id ?? '',
    status: task.status.value,
    priority: task.priority.value,
    due_date: task.due_date ?? '',
});

const submit = () => form.submit(taskUpdate(task.id));
</script>

<template>
    <Head :title="`Edit — ${task.title}`" />

    <div class="flex flex-col gap-6 p-4">
        <h1 class="text-2xl font-semibold">Edit Task</h1>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Task Details</CardTitle>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="flex flex-col gap-5">
                    <div class="grid gap-2">
                        <Label for="title">Title</Label>
                        <Input id="title" v-model="form.title" placeholder="Task title" required />
                        <InputError :message="form.errors.title" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Textarea id="description" v-model="form.description" placeholder="Optional details" rows="3" />
                        <InputError :message="form.errors.description" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="status">Status</Label>
                            <Select v-model="form.status">
                                <SelectTrigger id="status">
                                    <SelectValue placeholder="Select status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="s in statuses" :key="s.value" :value="s.value">
                                        {{ s.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.status" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="priority">Priority</Label>
                            <Select v-model="form.priority">
                                <SelectTrigger id="priority">
                                    <SelectValue placeholder="Select priority" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="p in priorities" :key="p.value" :value="p.value">
                                        {{ p.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.priority" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="assigned_to">Assignee</Label>
                            <Select v-model="form.assigned_to">
                                <SelectTrigger id="assigned_to">
                                    <SelectValue placeholder="Unassigned" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">Unassigned</SelectItem>
                                    <SelectItem v-for="user in users" :key="user.id" :value="user.id">
                                        {{ user.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.assigned_to" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="due_date">Due Date</Label>
                            <Input id="due_date" type="date" v-model="form.due_date" />
                            <InputError :message="form.errors.due_date" />
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <Button type="submit" :disabled="form.processing">Save Changes</Button>
                        <Button type="button" variant="outline" as-child>
                            <a :href="tasksIndex(project.id)">Cancel</a>
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>

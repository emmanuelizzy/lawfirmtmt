<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import type { User } from '@/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/InputError.vue';
import { index } from '@/routes/projects';
import { store } from '@/actions/App/Http/Controllers/Project/ProjectController';

interface StatusOption {
    value: string;
    label: string;
}

interface Props {
    statuses: StatusOption[];
    users: Pick<User, 'id' | 'name'>[];
}

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Projects', href: index() },
            { title: 'New Project', href: '#' },
        ],
    },
});

const form = useForm({
    title: '',
    description: '',
    lead_id: '',
    status: 'ACTIVE',
});

const submit = () => form.submit(store());
</script>

<template>
    <Head title="New Project" />

    <div class="flex flex-col gap-6 p-4">
        <h1 class="text-2xl font-semibold">New Project</h1>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Project Details</CardTitle>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="flex flex-col gap-5">
                    <div class="grid gap-2">
                        <Label for="title">Title</Label>
                        <Input id="title" v-model="form.title" placeholder="Project title" required />
                        <InputError :message="form.errors.title" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Textarea id="description" v-model="form.description" placeholder="Optional description" rows="3" />
                        <InputError :message="form.errors.description" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="lead_id">Lead</Label>
                        <Select v-model="form.lead_id">
                            <SelectTrigger id="lead_id">
                                <SelectValue placeholder="Select a lead" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="user in users" :key="user.id" :value="user.id">
                                    {{ user.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.lead_id" />
                    </div>

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

                    <div class="flex gap-3">
                        <Button type="submit" :disabled="form.processing">Create Project</Button>
                        <Button type="button" variant="outline" as-child>
                            <a :href="index()">Cancel</a>
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>

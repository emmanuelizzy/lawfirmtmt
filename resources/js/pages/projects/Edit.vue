<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import * as ProjectController from '@/actions/App/Http/Controllers/Project/ProjectController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
import { Textarea } from '@/components/ui/textarea';
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

const { project, statuses, users } = defineProps<{
    project: Project;
    statuses: Array<{ value: string; label: string }>;
    users: Array<{ id: string; name: string }>;
}>();
</script>

<template>
    <Head :title="`Edit — ${project.title}`" />

    <div class="flex flex-1 flex-col gap-6 p-6">
        <div>
            <Button variant="ghost" size="sm" as-child class="-ml-2 mb-4 text-muted-foreground">
                <Link :href="ProjectController.show.url(project.id)">
                    <ArrowLeft data-icon="inline-start" />
                    Back to project
                </Link>
            </Button>
        </div>

        <Card class="mx-auto w-full max-w-2xl">
            <CardHeader>
                <CardTitle>Edit project</CardTitle>
                <CardDescription>
                    Update the details for
                    <span class="font-medium text-foreground">{{ project.title }}</span>.
                </CardDescription>
            </CardHeader>

            <Separator />

            <CardContent class="pt-6">
                <Form
                    v-bind="ProjectController.update.form(project.id)"
                    class="flex flex-col gap-5"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="title">Title <span class="text-destructive">*</span></Label>
                        <Input
                            id="title"
                            name="title"
                            :default-value="project.title"
                            placeholder="e.g. Smith v. Acme Corp — Discovery Phase"
                            required
                        />
                        <InputError :message="errors.title" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Textarea
                            id="description"
                            name="description"
                            :default-value="project.description ?? undefined"
                            placeholder="Brief overview of this project's scope and objectives…"
                            class="min-h-24 resize-none"
                        />
                        <InputError :message="errors.description" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="status">Status <span class="text-destructive">*</span></Label>
                            <Select name="status" :default-value="project.status.value" required>
                                <SelectTrigger id="status">
                                    <SelectValue placeholder="Select a status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem
                                            v-for="status in statuses"
                                            :key="status.value"
                                            :value="status.value"
                                        >
                                            {{ status.label }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.status" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="lead_id">Lead attorney <span class="text-destructive">*</span></Label>
                            <Select name="lead_id" :default-value="project.lead?.id" required>
                                <SelectTrigger id="lead_id">
                                    <SelectValue placeholder="Assign a lead" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem
                                            v-for="user in users"
                                            :key="user.id"
                                            :value="user.id"
                                        >
                                            {{ user.name }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.lead_id" />
                        </div>
                    </div>

                    <Separator />

                    <div class="flex items-center justify-end gap-3">
                        <Button variant="outline" as-child>
                            <Link :href="ProjectController.show.url(project.id)">Cancel</Link>
                        </Button>
                        <Button type="submit" :disabled="processing">
                            <span v-if="processing">Saving…</span>
                            <span v-else>Save changes</span>
                        </Button>
                    </div>
                </Form>
            </CardContent>
        </Card>
    </div>
</template>

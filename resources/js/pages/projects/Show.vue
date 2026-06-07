<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CalendarDays,
    ClipboardList,
    Kanban,
    LayoutList,
    MoreHorizontal,
    Pencil,
    Plus,
    Trash2,
    User,
    Eye,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import * as ProjectController from '@/actions/App/Http/Controllers/Project/ProjectController';
import * as TaskController from '@/actions/App/Http/Controllers/Task/TaskController';
import TaskDetailSheet from '@/components/task/TaskDetailSheet.vue';
import TaskSheet from '@/components/task/TaskSheet.vue';
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
import { Input } from '@/components/ui/input';
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Project, Task } from '@/types';

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

const { project, tasks, users, statuses, priorities } = defineProps<{
    project: Project;
    tasks: Task[];
    users: Array<{ id: string; name: string }>;
    statuses: Array<{ value: string; label: string }>;
    priorities: Array<{ value: string; label: string }>;
}>();

// ── Project delete ────────────────────────────────────────────────────────────
const showDeleteProjectDialog = ref(false);
const isDeletingProject = ref(false);

function executeDeleteProject() {
    isDeletingProject.value = true;
    router.delete(ProjectController.destroy.url(project.id), {
        onFinish: () => {
            isDeletingProject.value = false;
            showDeleteProjectDialog.value = false;
        },
    });
}

// ── View toggle ───────────────────────────────────────────────────────────────
type View = 'table' | 'kanban';
const activeView = ref<View>('table');

// ── Task sheets ───────────────────────────────────────────────────────────────
const taskSheetOpen = ref(false);
const taskDetailOpen = ref(false);
const editingTask = ref<Task | null>(null);
const viewingTask = ref<Task | null>(null);

// ── Task delete ───────────────────────────────────────────────────────────────
const deleteTaskDialogOpen = ref(false);
const taskToDelete = ref<Task | null>(null);
const isDeletingTask = ref(false);

function confirmDeleteTask(task: Task) {
    taskToDelete.value = task;
    deleteTaskDialogOpen.value = true;
}

function executeDeleteTask() {
    if (!taskToDelete.value) return;
    isDeletingTask.value = true;
    router.delete(TaskController.destroy.url(taskToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteTaskDialogOpen.value = false;
            taskToDelete.value = null;
        },
        onFinish: () => {
            isDeletingTask.value = false;
        },
    });
}

function openCreateSheet() {
    editingTask.value = null;
    taskSheetOpen.value = true;
}

function openEditSheet(task: Task) {
    editingTask.value = task;
    taskDetailOpen.value = false;
    taskSheetOpen.value = true;
}

function openDetailSheet(task: Task) {
    viewingTask.value = task;
    taskDetailOpen.value = true;
}

// ── Filters ───────────────────────────────────────────────────────────────────
const searchQuery = ref('');
const statusFilter = ref<string>('all');
const priorityFilter = ref<string>('all');

const filteredTasks = computed(() => {
    return tasks.filter((task) => {
        const matchesSearch = !searchQuery.value
            || task.title.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesStatus = statusFilter.value === 'all'
            || task.status.value === statusFilter.value;
        const matchesPriority = priorityFilter.value === 'all'
            || task.priority.value === priorityFilter.value;
        return matchesSearch && matchesStatus && matchesPriority;
    });
});

// ── Helpers ───────────────────────────────────────────────────────────────────
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

function getProjectStatusVariant(status: Project['status']['value']) {
    const map: Record<Project['status']['value'], 'default' | 'secondary' | 'outline' | 'destructive'> = {
        ACTIVE:    'default',
        ON_HOLD:   'secondary',
        COMPLETED: 'outline',
        ARCHIVED:  'destructive',
    };
    return map[status] ?? 'secondary';
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
                    @click="showDeleteProjectDialog = true"
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
                <Badge :variant="getProjectStatusVariant(project.status.value)">
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
                        {{ tasks.length }}
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

        <!-- Tasks section -->
        <Card>
            <CardHeader class="flex flex-row items-start justify-between gap-4 space-y-0">
                <div class="space-y-1">
                    <CardTitle class="text-base">Tasks</CardTitle>
                    <CardDescription>
                        All tasks assigned to this project.
                    </CardDescription>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <!-- View toggle -->
                    <div class="flex items-center rounded-md border p-0.5">
                        <Button
                            variant="ghost"
                            size="icon"
                            class="size-7 rounded-sm"
                            :class="activeView === 'table' ? 'bg-background shadow-xs' : ''"
                            @click="activeView = 'table'"
                        >
                            <LayoutList class="size-4" />
                            <span class="sr-only">Table view</span>
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="size-7 rounded-sm"
                            :class="activeView === 'kanban' ? 'bg-background shadow-xs' : ''"
                            @click="activeView = 'kanban'"
                        >
                            <Kanban class="size-4" />
                            <span class="sr-only">Kanban view</span>
                        </Button>
                    </div>

                    <Button size="sm" @click="openCreateSheet">
                        <Plus data-icon="inline-start" />
                        New task
                    </Button>
                </div>
            </CardHeader>

            <Separator />

            <!-- Filters -->
            <div v-if="tasks.length > 0" class="flex flex-wrap items-center gap-3 px-6 py-3">
                <Input
                    v-model="searchQuery"
                    placeholder="Search tasks…"
                    class="h-9 w-56 text-sm"
                />
                <Select v-model="statusFilter">
                    <SelectTrigger class="h-9 w-36 text-sm">
                        <SelectValue placeholder="All statuses" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectGroup>
                            <SelectItem value="all">All statuses</SelectItem>
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
                <Select v-model="priorityFilter">
                    <SelectTrigger class="h-9 w-36 text-sm">
                        <SelectValue placeholder="All priorities" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectGroup>
                            <SelectItem value="all">All priorities</SelectItem>
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
            </div>

            <Separator v-if="tasks.length > 0" />

            <!-- Table view -->
            <template v-if="activeView === 'table'">
                <Table v-if="filteredTasks.length > 0">
                    <TableHeader>
                        <TableRow class="hover:bg-transparent">
                            <TableHead class="pl-6">Task</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Priority</TableHead>
                            <TableHead>Assignee</TableHead>
                            <TableHead>Due date</TableHead>
                            <TableHead class="w-12 pr-6" />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="task in filteredTasks"
                            :key="task.id"
                            class="group cursor-pointer"
                            @click="openDetailSheet(task)"
                        >
                            <TableCell class="py-3 pl-6">
                                <div class="flex flex-col gap-0.5">
                                    <span
                                        class="font-medium leading-none text-foreground"
                                        :class="task.status.value === 'DONE' ? 'line-through text-muted-foreground' : ''"
                                    >
                                        {{ task.title }}
                                    </span>
                                    <span
                                        v-if="task.description"
                                        class="line-clamp-1 max-w-xs text-xs text-muted-foreground"
                                    >
                                        {{ task.description }}
                                    </span>
                                </div>
                            </TableCell>

                            <TableCell>
                                <Badge :variant="getStatusVariant(task.status.value)">
                                    {{ task.status.label }}
                                </Badge>
                            </TableCell>

                            <TableCell>
                                <Badge :variant="getPriorityVariant(task.priority.value)">
                                    {{ task.priority.label }}
                                </Badge>
                            </TableCell>

                            <TableCell>
                                <div v-if="task.assignee" class="flex items-center gap-2">
                                    <Avatar class="size-6">
                                        <AvatarFallback class="text-[10px]">
                                            {{ getInitials(task.assignee.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <span class="text-sm text-muted-foreground">
                                        {{ task.assignee.name }}
                                    </span>
                                </div>
                                <span v-else class="text-sm text-muted-foreground">—</span>
                            </TableCell>

                            <TableCell>
                                <span
                                    v-if="task.due_date"
                                    class="text-sm"
                                    :class="task.is_overdue ? 'font-medium text-destructive' : 'text-muted-foreground'"
                                >
                                    {{ formatDate(task.due_date) }}
                                </span>
                                <span v-else class="text-sm text-muted-foreground">—</span>
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
                                    <DropdownMenuContent align="end" class="w-36">
                                        <DropdownMenuGroup>
                                            <DropdownMenuItem @click="viewingTask = task; taskDetailOpen = true">
                                                <Eye data-icon="inline-start" />
                                                View
                                            </DropdownMenuItem>
                                        </DropdownMenuGroup>
                                        <DropdownMenuGroup>
                                            <DropdownMenuItem @click="openEditSheet(task)">
                                                <Pencil data-icon="inline-start" />
                                                Edit
                                            </DropdownMenuItem>
                                        </DropdownMenuGroup>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuGroup>
                                            <DropdownMenuItem
                                                variant="destructive"
                                                @click="confirmDeleteTask(task)"
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

                <!-- No results after filtering -->
                <div
                    v-else-if="tasks.length > 0 && filteredTasks.length === 0"
                    class="flex flex-col items-center justify-center gap-3 py-14"
                >
                    <p class="text-sm text-muted-foreground">No tasks match your filters.</p>
                    <Button
                        variant="ghost"
                        size="sm"
                        @click="searchQuery = ''; statusFilter = 'all'; priorityFilter = 'all'"
                    >
                        Clear filters
                    </Button>
                </div>

                <!-- Empty state -->
                <div
                    v-else
                    class="flex flex-col items-center justify-center gap-4 py-20"
                >
                    <div class="flex size-14 items-center justify-center rounded-full bg-muted">
                        <ClipboardList class="size-6 text-muted-foreground" />
                    </div>
                    <div class="space-y-1 text-center">
                        <p class="text-sm font-medium">No tasks yet</p>
                        <p class="text-sm text-muted-foreground">
                            Create the first task for this project.
                        </p>
                    </div>
                    <Button size="sm" @click="openCreateSheet">
                        <Plus data-icon="inline-start" />
                        New task
                    </Button>
                </div>
            </template>

            <!-- Kanban view — placeholder until Slice 3 -->
            <template v-else>
                <div class="flex flex-col items-center justify-center gap-3 py-20">
                    <div class="flex size-14 items-center justify-center rounded-full bg-muted">
                        <Kanban class="size-6 text-muted-foreground" />
                    </div>
                    <div class="space-y-1 text-center">
                        <p class="text-sm font-medium">Kanban coming soon</p>
                        <p class="text-sm text-muted-foreground">
                            Drag-and-drop board will be available here in the next update.
                        </p>
                    </div>
                </div>
            </template>
        </Card>
    </div>

    <!-- Task create / edit sheet -->
    <TaskSheet
        v-model:open="taskSheetOpen"
        :project-id="project.id"
        :task="editingTask"
        :users="users"
        :statuses="statuses"
        :priorities="priorities"
    />

    <!-- Task detail sheet -->
    <TaskDetailSheet
        v-model:open="taskDetailOpen"
        :task="viewingTask"
        @edit="openEditSheet"
    />

    <!-- Delete task confirmation -->
    <Dialog :open="deleteTaskDialogOpen" @update:open="(v) => !v && (deleteTaskDialogOpen = false)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Delete task</DialogTitle>
                <DialogDescription>
                    Are you sure you want to delete
                    <span class="font-medium text-foreground">{{ taskToDelete?.title }}</span>?
                    This cannot be undone.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" :disabled="isDeletingTask" @click="deleteTaskDialogOpen = false">
                    Cancel
                </Button>
                <Button variant="destructive" :disabled="isDeletingTask" @click="executeDeleteTask">
                    <span v-if="isDeletingTask">Deleting…</span>
                    <span v-else>Delete task</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Delete project confirmation -->
    <Dialog :open="showDeleteProjectDialog" @update:open="(v) => !v && (showDeleteProjectDialog = false)">
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
                <Button variant="outline" :disabled="isDeletingProject" @click="showDeleteProjectDialog = false">
                    Cancel
                </Button>
                <Button variant="destructive" :disabled="isDeletingProject" @click="executeDeleteProject">
                    <span v-if="isDeletingProject">Deleting…</span>
                    <span v-else>Delete project</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
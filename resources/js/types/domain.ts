export interface Role {
    name: string;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginationMeta {
    current_page: number;
    from: number;
    last_page: number;
    links: PaginationLink[];
    path: string;
    per_page: number;
    to: number;
    total: number;
}

export interface PaginatedResponse<T> {
    data: T[];
    meta: PaginationMeta;
}

export interface User {
    id: string;
    name: string;
    email: string;
    roles?: string[];
}

export interface ProjectStatus {
    value: 'ACTIVE' | 'ON_HOLD' | 'COMPLETED' | 'ARCHIVED';
    label: string;
}

export interface Project {
    id: string;
    title: string;
    description: string | null;
    status: ProjectStatus;
    lead: User;
    tasks_count?: number;
    created_at: string;
    updated_at: string;
}

export type TaskStatusValue = 'TODO' | 'IN_PROGRESS' | 'REVIEW' | 'DONE';
export type TaskPriorityValue = 'LOW' | 'MEDIUM' | 'HIGH' | 'URGENT';

export interface TaskStatus {
    value: TaskStatusValue;
    label: string;
}

export interface TaskPriority {
    value: TaskPriorityValue;
    label: string;
}

export interface Task {
    id: string;
    title: string;
    description: string | null;
    status: TaskStatus;
    priority: TaskPriority;
    due_date: string | null;
    is_overdue: boolean;
    completed_at: string | null;
    assignee: User | null;
    project: Project | null;
    created_at: string;
    updated_at: string;
}

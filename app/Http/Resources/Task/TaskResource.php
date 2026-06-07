<?php

namespace App\Http\Resources\Task;

use Illuminate\Http\Request;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'description'  => $this->description,
            'status'       => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
            ],
            'priority'     => [
                'value' => $this->priority->value,
                'label' => $this->priority->label(),
            ],
            'due_date'     => $this->due_date?->toDateString(),
            'is_overdue'   => $this->isOverdue(),
            'completed_at' => $this->completed_at?->toDateTimeString(),
            'assignee'     => $this->whenLoaded('assignee', fn () => UserResource::make($this->assignee)->resolve()),
            'project'      => $this->whenLoaded('project', fn () => [
                'id'    => $this->project->id,
                'title' => $this->project->title,
            ]),
            'created_at'   => $this->created_at->toDateTimeString(),
            'updated_at'   => $this->updated_at->toDateTimeString(),
        ];
    }
}

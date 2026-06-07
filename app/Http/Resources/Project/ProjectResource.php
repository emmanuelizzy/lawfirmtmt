<?php

namespace App\Http\Resources\Project;

use Illuminate\Http\Request;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'description' => $this->description,
            'status'      => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
            ],
            'lead'        => $this->whenLoaded('lead', fn () => UserResource::make($this->lead)->resolve()),
            'tasks_count' => $this->whenCounted('tasks'),
            'created_at'  => $this->created_at->toDateTimeString(),
            'updated_at'  => $this->updated_at->toDateTimeString(),
        ];
    }
}

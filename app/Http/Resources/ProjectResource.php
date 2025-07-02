<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'name' => $this->name,
            'description' => $this->description,
            'start_date' => $this->start_date,
            'due_date' => $this->due_date,
            'created_at' => $this->created_at?->toDateTimeString(),
            'workspace' => new WorkspaceResource($this->whenLoaded('workspace')),
            'project_statuses' => ProjectStatusResource::collection($this->whenLoaded('projectStatuses')),
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),
        ];
    }
}

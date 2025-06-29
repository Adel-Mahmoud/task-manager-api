<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'      => $this->id,
            'user'    => new UserResource($this->whenLoaded('user')),
            'task_id' => $this->task_id,
            'content' => $this->content,
            'parent_id' => $this->parent_id,
            'children'  => CommentResource::collection($this->whenLoaded('children')),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}

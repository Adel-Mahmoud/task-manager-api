<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'status_id',
        'user_id',
        'title',
        'description',
        'due_date',
        'is_completed'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function status()
    {
        return $this->belongsTo(ProjectStatus::class, 'status_id');
    }

    public function workspaceMember()
    {
        return $this->belongsTo(WorkspaceMember::class);
    }
}

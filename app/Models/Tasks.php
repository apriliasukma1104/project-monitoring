<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tasks extends Model
{
    use HasFactory;

    protected $fillable = ['id', 'id_project', 'name_member', 'task', 'description', 'status'];

    public function project()
    {
        return $this->belongsTo(Projects::class, 'id_project');
    }
}

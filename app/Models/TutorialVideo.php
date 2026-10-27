<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TutorialVideo extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'video_path',
    ];
}

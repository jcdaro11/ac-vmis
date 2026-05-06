<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingRequirement extends Model
{
    public const CATEGORIES = [
        'Conditioning',
        'Skill Work',
        'Strength',
        'Tactical',
        'Recovery',
    ];

    protected $fillable = [
        'schedule_id',
        'student_id',
        'coach_id',
        'title',
        'category',
        'description',
    ];

    public function schedule()
    {
        return $this->belongsTo(TeamSchedule::class, 'schedule_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function coach()
    {
        return $this->belongsTo(Coach::class, 'coach_id');
    }
}

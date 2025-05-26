<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = [
        'teacher_id',
        'classroom_id',
        'subject_id',
        'group_id',
        'day_id',
        'week_id',
        'division_id',
        'period',
        'start',
        'end',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function day()
    {
        return $this->belongsTo(Day::class);
    }

    public function week()
    {
        return $this->belongsTo(Week::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    protected $fillable = ["name"];

    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }

    public function groups()
    {
        return $this->hasMany(Group::class);
    }
}

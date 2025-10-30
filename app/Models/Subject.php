<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = ["name", "short"];

    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }
}

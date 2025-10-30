<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Week extends Model
{
    protected $fillable = ["name", "number", "start_date"];

    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }
}

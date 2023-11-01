<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Lesson;

use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function index()
    {
        $groups = Group::all();
        $lessons = Lesson::all();
        $result = [];

        foreach ($groups as $group) {
            $result[$group->name] = [];

            foreach ($lessons as $lesson) {
                if ($lesson->group === $group->name) {
                    $day = (int) $lesson->day;

                    if (!isset($result[$group->name][$day - 1])) {
                        $result[$group->name][$day - 1] = [
                            'classes' => [],
                            'teachers' => [],
                        ];
                    }

                    $result[$group->name][$day - 1]['classes'][] = $lesson->lesson;
                    $result[$group->name][$day - 1]['teachers'][] = $lesson->teacher;
                }
            }
        }

        return response()->json($result, 200, [], JSON_PRETTY_PRINT);
    }
}
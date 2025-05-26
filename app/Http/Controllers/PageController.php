<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Lesson;
use App\Models\Week;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PageController extends Controller
{
    public function home(): \Inertia\Response
    {
        return Inertia::render('Home');
    }
    public function lessons(Request $request): \Inertia\Response
    {
        $groupId = $request->input('group_id');
        $group = $groupId ? Group::find($groupId) : Group::where('name', 'IPa22')->first();
        $weekId = $request->input('week_id');
        $week = $weekId ? Week::find($weekId) : Week::where('start_date', '<=', now()->addDays(2))
            ->orderBy('start_date', 'desc')
            ->first();

        $rawLessons = Lesson::where('group_id', $group->id)
            ->with(['day', 'subject', 'teacher', 'classroom', 'week', 'division', 'group'])
            ->orderBy('day_id')
            ->orderBy('period')
            ->get();

        $lessonsByWeek = [];
        foreach ($rawLessons->groupBy('week_id') as $weekId => $weekLessons) {
            $weekData = $weekLessons->first()->week;

            $groupedLessons = $weekLessons->groupBy('day_id');
            $formattedLessons = [];

            foreach ($groupedLessons as $dayId => $dayLessons) {
                if ($dayLessons->isEmpty()) continue;

                $dayName = $dayLessons->first()->day->name;
                $lessonsList = [];

                foreach ($dayLessons as $lesson) {
                    $lessonsList[] = [
                        'id' => $lesson->id,
                        'period' => $lesson->period,
                        'subject' => $lesson->subject->name,
                        'classroom' => $lesson->classroom ? $lesson->classroom->name : null,
                        'teacher' => $lesson->teacher ? $lesson->teacher->name : null,
                        'group' => $lesson->group->name,
                        'division' => ($lesson->division && $lesson->division->name !== 'Visa klase') ? $lesson->division->name : null,
                        'start' => substr($lesson->start, 0, 5),
                        'end' => substr($lesson->end, 0, 5),
                    ];
                }

                $formattedLessons[] = [
                    'id' => $dayId,
                    'day' => $dayName,
                    'lessons' => $lessonsList
                ];
            }

            $lessonsByWeek[$weekId] = [
                'week_id' => $weekId,
                'days' => $formattedLessons
            ];
        }

        $groups = Group::all();
        $weeks = Week::all();

        return Inertia::render('Lessons', [
            'lessonsByWeek' => $lessonsByWeek,
            'groups' => $groups,
            'weeks' => $weeks,
            'selectedWeekId' => $week->id,
            'selectedGroupId' => $group->id,
        ]);
    }
}

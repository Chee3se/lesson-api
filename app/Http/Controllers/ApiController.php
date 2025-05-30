<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ApiController extends Controller
{
    /**
     * Get timetable data from the database for all groups
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function timetable(Request $request)
    {
        $groupName = $request->input('className');

        $groupsQuery = \App\Models\Group::query();
        if ($groupName) {
            $groupsQuery->where('name', $groupName);
        }
        $groups = $groupsQuery->get();

        if ($groups->isEmpty()) {
            return response()->json([]);
        }

        $allDays = [
            'Pirmdiena',
            'Otrdiena',
            'Trešdiena',
            'Ceturtdiena',
            'Piektdiena'
        ];

        $timetableByClass = [];

        foreach ($groups as $group) {
            $targetClassName = $group->name;

            $lessons = \App\Models\Lesson::with(['day', 'subject', 'teacher', 'classroom', 'week', 'division'])
                ->where('group_id', $group->id)
                ->orderBy('day_id')
                ->orderBy('period')
                ->get();

            $timetableByClass[$targetClassName] = [];

            $allWeeks = \App\Models\Week::where('start_date', '>=', now()->subDays(5)->format('Y-m-d'))
            ->orderBy('start_date')
            ->get();

            foreach ($allWeeks as $week) {
                $weekStartDate = $week->start_date;

                $weekLessons = $lessons->where('week_id', $week->id);

                $weekLessonsByDay = $weekLessons->groupBy(function($lesson) {
                    return $lesson->day->name;
                });

                $weeklyTimetable = [];

                foreach ($allDays as $dayName) {
                    $dayData = [
                        'day' => $dayName,
                        'data' => []
                    ];

                    if (isset($weekLessonsByDay[$dayName])) {
                        $dayLessons = $weekLessonsByDay[$dayName];

                        $lessonsByPeriod = $dayLessons->groupBy('period');

                        foreach ($lessonsByPeriod as $period => $periodLessons) {
                            if ($periodLessons->count() == 1) {
                                $lesson = $periodLessons->first();
                                $dayData['data'][] = [
                                    'name' => $lesson->subject ? $lesson->subject->name : null,
                                    'class' => $lesson->classroom ? $lesson->classroom->name : null,
                                    'teacher' => $lesson->teacher ? $lesson->teacher->name : null,
                                    'start' => $lesson->start,
                                    'end' => $lesson->end,
                                ];
                            } else {
                                $divisionData = [];

                                foreach ($periodLessons as $lesson) {
                                    $divisionName = $lesson->division ? $lesson->division->name : 'Main Group';

                                    $divisionData[$divisionName] = [
                                        'name' => $lesson->subject ? $lesson->subject->name : null,
                                        'class' => $lesson->classroom ? $lesson->classroom->name : null,
                                        'teacher' => $lesson->teacher ? $lesson->teacher->name : null,
                                        'start' => $lesson->start,
                                        'end' => $lesson->end,
                                    ];
                                }

                                $dayData['data'][] = $divisionData;
                            }
                        }
                    }

                    $weeklyTimetable[] = $dayData;
                }

                $timetableByClass[$targetClassName][$weekStartDate] = $weeklyTimetable;
            }
        }

        return response()->json($timetableByClass);
    }
    public function lessons(Request $request) {
        $lessons = \App\Models\Lesson::with(['day', 'subject', 'teacher', 'classroom', 'week', 'division'])->get();
        return response()->json($lessons);
    }
    public function groups(Request $request) {
        $groups = \App\Models\Group::all();
        return response()->json($groups);
    }
}

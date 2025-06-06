<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\Teacher;
use App\Models\Week;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PageController extends Controller
{
    public function home(): \Inertia\Response
    {
        return Inertia::render('Home');
    }
    public function group(Request $request, Group $group): \Inertia\Response
    {
        //$group = $group ? $group : Group::where('name', 'IPa22')->first();
        $weekId = $request->input('week_id');

        $today = now()->subDays(5)->format('Y-m-d');

        if ($weekId) {
            $week = Week::find($weekId);
            if (!$request->has('week_id') && $week->start_date < $today) {
                $week = Week::where('start_date', '>=', $today)
                    ->orderBy('start_date')
                    ->first();

                if (!$week) {
                    $week = Week::orderBy('start_date', 'desc')->first();
                }
            }
        } else {
            $week = Week::where('start_date', '>=', $today)
                ->orderBy('start_date')
                ->first();

            if (!$week) {
                $week = Week::orderBy('start_date', 'desc')->first();
            }
        }

        $currentAndFutureWeeks = Week::where('start_date', '>=', $today)
            ->orWhere('id', $week->id)
            ->orderBy('start_date')
            ->pluck('id');

        $rawLessons = Lesson::where('group_id', $group->id)
            ->whereIn('week_id', $currentAndFutureWeeks)
            ->with(['day', 'subject', 'teacher', 'classroom', 'week', 'division', 'group'])
            ->orderBy('week_id')
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
                $dayShort = $dayLessons->first()->day->short;
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
                    'short' => $dayShort,
                    'lessons' => $lessonsList
                ];
            }

            $lessonsByWeek[$weekId] = [
                'week_id' => $weekId,
                'days' => $formattedLessons
            ];
        }

        $groups = Group::all();

        $weeks = Week::where('start_date', '>=', $today)
            ->orWhere('id', $week->id)
            ->orderBy('start_date')
            ->get();

        return Inertia::render('Group/Show', [
            'lessonsByWeek' => $lessonsByWeek,
            'groups' => $groups,
            'weeks' => $weeks,
            'selectedWeekId' => $week->id,
            'selectedGroupId' => $group->id,
        ]);
    }

    public function teacher(Request $request, Teacher $teacher): \Inertia\Response
    {
        $weekId = $request->input('week_id');

        $today = now()->subDays(5)->format('Y-m-d');

        if ($weekId) {
            $week = Week::find($weekId);
            if (!$request->has('week_id') && $week->start_date < $today) {
                $week = Week::where('start_date', '>=', $today)
                    ->orderBy('start_date')
                    ->first();

                if (!$week) {
                    $week = Week::orderBy('start_date', 'desc')->first();
                }
            }
        } else {
            $week = Week::where('start_date', '>=', $today)
                ->orderBy('start_date')
                ->first();

            if (!$week) {
                $week = Week::orderBy('start_date', 'desc')->first();
            }
        }

        $currentAndFutureWeeks = Week::where('start_date', '>=', $today)
            ->orWhere('id', $week->id)
            ->orderBy('start_date')
            ->pluck('id');

        $rawLessons = Lesson::where('teacher_id', $teacher->id)
            ->whereIn('week_id', $currentAndFutureWeeks)
            ->with(['day', 'subject', 'teacher', 'classroom', 'week', 'division', 'group'])
            ->orderBy('week_id')
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
                $dayShort = $dayLessons->first()->day->short;
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
                    'short' => $dayShort,
                    'lessons' => $lessonsList
                ];
            }

            $lessonsByWeek[$weekId] = [
                'week_id' => $weekId,
                'days' => $formattedLessons
            ];
        }

        $teachers = Teacher::all();

        $weeks = Week::where('start_date', '>=', $today)
            ->orWhere('id', $week->id)
            ->orderBy('start_date')
            ->get();

        return Inertia::render('Teacher/Show', [
            'lessonsByWeek' => $lessonsByWeek,
            'teachers' => $teachers,
            'weeks' => $weeks,
            'selectedWeekId' => $week->id,
            'selectedTeacherId' => $teacher->id,
        ]);
    }

    public function classroom(Request $request, Classroom $classroom): \Inertia\Response
    {
        $weekId = $request->input('week_id');

        $today = now()->subDays(5)->format('Y-m-d');

        if ($weekId) {
            $week = Week::find($weekId);
            if (!$request->has('week_id') && $week->start_date < $today) {
                $week = Week::where('start_date', '>=', $today)
                    ->orderBy('start_date')
                    ->first();

                if (!$week) {
                    $week = Week::orderBy('start_date', 'desc')->first();
                }
            }
        } else {
            $week = Week::where('start_date', '>=', $today)
                ->orderBy('start_date')
                ->first();

            if (!$week) {
                $week = Week::orderBy('start_date', 'desc')->first();
            }
        }

        $currentAndFutureWeeks = Week::where('start_date', '>=', $today)
            ->orWhere('id', $week->id)
            ->orderBy('start_date')
            ->pluck('id');

        $rawLessons = Lesson::where('classroom_id', $classroom->id)
            ->whereIn('week_id', $currentAndFutureWeeks)
            ->with(['day', 'subject', 'teacher', 'classroom', 'week', 'division', 'group'])
            ->orderBy('week_id')
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
                $dayShort = $dayLessons->first()->day->short;
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
                    'short' => $dayShort,
                    'lessons' => $lessonsList
                ];
            }

            $lessonsByWeek[$weekId] = [
                'week_id' => $weekId,
                'days' => $formattedLessons
            ];
        }

        $classrooms = Classroom::all();

        $weeks = Week::where('start_date', '>=', $today)
            ->orWhere('id', $week->id)
            ->orderBy('start_date')
            ->get();

        return Inertia::render('Classroom/Show', [
            'lessonsByWeek' => $lessonsByWeek,
            'classrooms' => $classrooms,
            'weeks' => $weeks,
            'selectedWeekId' => $week->id,
            'selectedClassroomId' => $classroom->id,
        ]);
    }
}

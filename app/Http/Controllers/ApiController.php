<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use App\Models\Lesson;
use App\Models\Group;
use App\Models\Week;
use App\Models\Day;
use App\Models\Teacher;
use App\Models\Subject;
use App\Models\Classroom;
use App\Models\Division;

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
        $groupName = $request->input("className");

        $groupsQuery = Group::query();
        if ($groupName) {
            $groupsQuery->where("name", $groupName);
        }
        $groups = $groupsQuery->get();

        if ($groups->isEmpty()) {
            return response()->json([]);
        }

        $baseDays = [
            "Pirmdiena",
            "Otrdiena",
            "Trešdiena",
            "Ceturtdiena",
            "Piektdiena",
        ];

        $timetableByClass = [];

        foreach ($groups as $group) {
            $targetClassName = $group->name;

            $lessons = Lesson::with([
                "day",
                "subject",
                "teacher",
                "classroom",
                "week",
                "division",
            ])
                ->where("group_id", $group->id)
                ->orderBy("day_id")
                ->orderBy("period")
                ->get();

            $timetableByClass[$targetClassName] = [];

            $allWeeks = Week::where(
                "start_date",
                ">=",
                now()->subDays(5)->format("Y-m-d"),
            )
                ->orderBy("start_date")
                ->get();

            foreach ($allWeeks as $week) {
                $weekStartDate = $week->start_date;

                $weekLessons = $lessons->where("week_id", $week->id);

                $weekLessonsByDay = $weekLessons->groupBy(function ($lesson) {
                    return $lesson->day->name;
                });

                // Check if this week has Saturday lessons
                $hasSaturday =
                    isset($weekLessonsByDay["Sestdiena"]) &&
                    $weekLessonsByDay["Sestdiena"]->isNotEmpty();

                // Use appropriate days array based on whether week has Saturday
                $allDays = $hasSaturday
                    ? array_merge($baseDays, ["Sestdiena"])
                    : $baseDays;

                $weeklyTimetable = [];

                foreach ($allDays as $dayName) {
                    $dayData = [
                        "day" => $dayName,
                        "data" => [],
                    ];

                    if (isset($weekLessonsByDay[$dayName])) {
                        $dayLessons = $weekLessonsByDay[$dayName];

                        $lessonsByPeriod = $dayLessons->groupBy("period");

                        foreach (
                            $lessonsByPeriod
                            as $period => $periodLessons
                        ) {
                            if ($periodLessons->count() == 1) {
                                $lesson = $periodLessons->first();
                                $dayData["data"][] = [
                                    "name" => $lesson->subject
                                        ? $lesson->subject->name
                                        : null,
                                    "class" => $lesson->classroom
                                        ? $lesson->classroom->name
                                        : null,
                                    "teacher" => $lesson->teacher
                                        ? $lesson->teacher->name
                                        : null,
                                    "start" => $lesson->start,
                                    "end" => $lesson->end,
                                ];
                            } else {
                                $divisionData = [];

                                foreach ($periodLessons as $lesson) {
                                    $divisionName = $lesson->division
                                        ? $lesson->division->name
                                        : "Main Group";

                                    $divisionData[$divisionName] = [
                                        "name" => $lesson->subject
                                            ? $lesson->subject->name
                                            : null,
                                        "class" => $lesson->classroom
                                            ? $lesson->classroom->name
                                            : null,
                                        "teacher" => $lesson->teacher
                                            ? $lesson->teacher->name
                                            : null,
                                        "start" => $lesson->start,
                                        "end" => $lesson->end,
                                    ];
                                }

                                $dayData["data"][] = $divisionData;
                            }
                        }
                    }

                    $weeklyTimetable[] = $dayData;
                }

                $timetableByClass[$targetClassName][
                    $weekStartDate
                ] = $weeklyTimetable;
            }
        }

        return response()->json($timetableByClass);
    }

    /**
     * Get lessons with comprehensive filtering
     *
     * Supported query parameters:
     * - teacher_id: Filter by teacher ID
     * - teacher_name: Filter by teacher name (partial match)
     * - subject_id: Filter by subject ID
     * - subject_name: Filter by subject name (partial match)
     * - classroom_id: Filter by classroom ID
     * - classroom_name: Filter by classroom name (partial match)
     * - group_id: Filter by group ID
     * - group_name: Filter by group name (partial match)
     * - division_id: Filter by division ID
     * - division_name: Filter by division name (partial match)
     * - week_id: Filter by week ID
     * - day_id: Filter by day ID
     * - day_name: Filter by day name (exact match)
     * - period: Filter by period number
     * - start_date: Filter weeks starting from this date (YYYY-MM-DD)
     * - end_date: Filter weeks ending before this date (YYYY-MM-DD)
     * - limit: Limit number of results
     * - offset: Offset for pagination
     * - with_relations: Include related data (default: true)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function lessons(Request $request)
    {
        $query = Lesson::query();

        // Apply filters

        // Teacher filters
        if ($request->has("teacher_id")) {
            $query->where("teacher_id", $request->input("teacher_id"));
        }

        if ($request->has("teacher_name")) {
            $query->whereHas("teacher", function ($q) use ($request) {
                $q->where(
                    "name",
                    "like",
                    "%" . $request->input("teacher_name") . "%",
                );
            });
        }

        // Subject filters
        if ($request->has("subject_id")) {
            $query->where("subject_id", $request->input("subject_id"));
        }

        if ($request->has("subject_name")) {
            $query->whereHas("subject", function ($q) use ($request) {
                $q->where(
                    "name",
                    "like",
                    "%" . $request->input("subject_name") . "%",
                );
            });
        }

        // Classroom filters
        if ($request->has("classroom_id")) {
            $query->where("classroom_id", $request->input("classroom_id"));
        }

        if ($request->has("classroom_name")) {
            $query->whereHas("classroom", function ($q) use ($request) {
                $q->where(
                    "name",
                    "like",
                    "%" . $request->input("classroom_name") . "%",
                );
            });
        }

        // Group filters
        if ($request->has("group_id")) {
            $query->where("group_id", $request->input("group_id"));
        }

        if ($request->has("group_name")) {
            $query->whereHas("group", function ($q) use ($request) {
                $q->where(
                    "name",
                    "like",
                    "%" . $request->input("group_name") . "%",
                );
            });
        }

        // Division filters
        if ($request->has("division_id")) {
            $query->where("division_id", $request->input("division_id"));
        }

        if ($request->has("division_name")) {
            $query->whereHas("division", function ($q) use ($request) {
                $q->where(
                    "name",
                    "like",
                    "%" . $request->input("division_name") . "%",
                );
            });
        }

        // Week filters
        if ($request->has("week_id")) {
            $query->where("week_id", $request->input("week_id"));
        }

        if ($request->has("start_date")) {
            $query->whereHas("week", function ($q) use ($request) {
                $q->where("start_date", ">=", $request->input("start_date"));
            });
        }

        if ($request->has("end_date")) {
            $query->whereHas("week", function ($q) use ($request) {
                $q->where("start_date", "<=", $request->input("end_date"));
            });
        }

        // Default to showing only current and future weeks
        if (!$request->has("start_date") && !$request->has("week_id")) {
            $query->whereHas("week", function ($q) {
                $q->where(
                    "start_date",
                    ">=",
                    now()->subDays(5)->format("Y-m-d"),
                );
            });
        }

        // Day filters
        if ($request->has("day_id")) {
            $query->where("day_id", $request->input("day_id"));
        }

        if ($request->has("day_name")) {
            $query->whereHas("day", function ($q) use ($request) {
                $q->where("name", $request->input("day_name"));
            });
        }

        // Period filter
        if ($request->has("period")) {
            $query->where("period", $request->input("period"));
        }

        // Load relations by default
        if ($request->input("with_relations", true)) {
            $query->with([
                "day",
                "subject",
                "teacher",
                "classroom",
                "week",
                "group",
                "division",
            ]);
        }

        // Order by
        $query->orderBy("week_id")->orderBy("day_id")->orderBy("period");

        // Pagination
        if ($request->has("limit")) {
            $limit = min((int) $request->input("limit"), 1000); // Max 1000
            $offset = $request->input("offset", 0);

            $total = $query->count();
            $lessons = $query->skip($offset)->take($limit)->get();

            return response()->json([
                "data" => $lessons,
                "meta" => [
                    "total" => $total,
                    "limit" => $limit,
                    "offset" => $offset,
                    "count" => $lessons->count(),
                ],
            ]);
        }

        $lessons = $query->get();

        return response()->json([
            "data" => $lessons,
            "meta" => [
                "total" => $lessons->count(),
                "count" => $lessons->count(),
            ],
        ]);
    }

    /**
     * Get groups with filtering
     *
     * Supported query parameters:
     * - id: Filter by group ID
     * - name: Filter by group name (partial match)
     * - teacher_id: Filter by teacher ID
     * - teacher_name: Filter by teacher name (partial match)
     * - limit: Limit number of results
     * - offset: Offset for pagination
     * - with_teacher: Include teacher data (default: true)
     * - with_lesson_count: Include count of lessons (default: false)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function groups(Request $request)
    {
        $query = Group::query();

        // Apply filters
        if ($request->has("id")) {
            $query->where("id", $request->input("id"));
        }

        if ($request->has("name")) {
            $query->where("name", "like", "%" . $request->input("name") . "%");
        }

        if ($request->has("teacher_id")) {
            $query->where("teacher_id", $request->input("teacher_id"));
        }

        if ($request->has("teacher_name")) {
            $query->whereHas("teacher", function ($q) use ($request) {
                $q->where(
                    "name",
                    "like",
                    "%" . $request->input("teacher_name") . "%",
                );
            });
        }

        // Load relations
        if ($request->input("with_teacher", true)) {
            $query->with("teacher");
        }

        // Count lessons if requested
        if ($request->input("with_lesson_count", false)) {
            $query->withCount("lessons");
        }

        // Order by name
        $query->orderBy("name");

        // Pagination
        if ($request->has("limit")) {
            $limit = min((int) $request->input("limit"), 1000);
            $offset = $request->input("offset", 0);

            $total = $query->count();
            $groups = $query->skip($offset)->take($limit)->get();

            return response()->json([
                "data" => $groups,
                "meta" => [
                    "total" => $total,
                    "limit" => $limit,
                    "offset" => $offset,
                    "count" => $groups->count(),
                ],
            ]);
        }

        $groups = $query->get();

        return response()->json([
            "data" => $groups,
            "meta" => [
                "total" => $groups->count(),
                "count" => $groups->count(),
            ],
        ]);
    }

    /**
     * Get all teachers
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function teachers(Request $request)
    {
        $query = Teacher::query();

        if ($request->has("name")) {
            $query->where("name", "like", "%" . $request->input("name") . "%");
        }

        if ($request->input("with_lesson_count", false)) {
            $query->withCount("lessons");
        }

        if ($request->input("with_group_count", false)) {
            $query->withCount("groups");
        }

        $teachers = $query->orderBy("name")->get();

        return response()->json([
            "data" => $teachers,
            "meta" => [
                "total" => $teachers->count(),
                "count" => $teachers->count(),
            ],
        ]);
    }

    /**
     * Get all subjects
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function subjects(Request $request)
    {
        $query = Subject::query();

        if ($request->has("name")) {
            $query->where("name", "like", "%" . $request->input("name") . "%");
        }

        if ($request->has("short")) {
            $query->where(
                "short",
                "like",
                "%" . $request->input("short") . "%",
            );
        }

        if ($request->input("with_lesson_count", false)) {
            $query->withCount("lessons");
        }

        $subjects = $query->orderBy("name")->get();

        return response()->json([
            "data" => $subjects,
            "meta" => [
                "total" => $subjects->count(),
                "count" => $subjects->count(),
            ],
        ]);
    }

    /**
     * Get all classrooms
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function classrooms(Request $request)
    {
        $query = Classroom::query();

        if ($request->has("name")) {
            $query->where("name", "like", "%" . $request->input("name") . "%");
        }

        if ($request->input("with_lesson_count", false)) {
            $query->withCount("lessons");
        }

        $classrooms = $query->orderBy("name")->get();

        return response()->json([
            "data" => $classrooms,
            "meta" => [
                "total" => $classrooms->count(),
                "count" => $classrooms->count(),
            ],
        ]);
    }

    /**
     * Get all divisions
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function divisions(Request $request)
    {
        $query = Division::query();

        if ($request->has("name")) {
            $query->where("name", "like", "%" . $request->input("name") . "%");
        }

        if ($request->input("with_lesson_count", false)) {
            $query->withCount("lessons");
        }

        $divisions = $query->orderBy("name")->get();

        return response()->json([
            "data" => $divisions,
            "meta" => [
                "total" => $divisions->count(),
                "count" => $divisions->count(),
            ],
        ]);
    }

    /**
     * Get all days
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function days(Request $request)
    {
        $query = Day::query();

        if ($request->has("name")) {
            $query->where("name", $request->input("name"));
        }

        if ($request->input("with_lesson_count", false)) {
            $query->withCount("lessons");
        }

        $days = $query->get();

        return response()->json([
            "data" => $days,
            "meta" => [
                "total" => $days->count(),
                "count" => $days->count(),
            ],
        ]);
    }

    /**
     * Get all weeks
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function weeks(Request $request)
    {
        $query = Week::query();

        if ($request->has("name")) {
            $query->where("name", "like", "%" . $request->input("name") . "%");
        }

        if ($request->has("number")) {
            $query->where("number", $request->input("number"));
        }

        if ($request->has("start_date")) {
            $query->where("start_date", ">=", $request->input("start_date"));
        }

        if ($request->has("end_date")) {
            $query->where("start_date", "<=", $request->input("end_date"));
        }

        // Default to current and future weeks only
        if (!$request->has("start_date") && !$request->has("show_all")) {
            $query->where(
                "start_date",
                ">=",
                now()->subDays(5)->format("Y-m-d"),
            );
        }

        if ($request->input("with_lesson_count", false)) {
            $query->withCount("lessons");
        }

        $weeks = $query->orderBy("start_date")->get();

        return response()->json([
            "data" => $weeks,
            "meta" => [
                "total" => $weeks->count(),
                "count" => $weeks->count(),
            ],
        ]);
    }
}

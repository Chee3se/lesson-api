<?php

namespace App\Console\Commands;

use App\Models\Classroom;
use App\Models\Day;
use App\Models\Division;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Week;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ScrapeLessons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:scrape-lessons';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape timetable data from EduPage';

    /**
     * Session cookie value
     *
     * @var string
     */
    protected $sessionId = '87889684f00bd60486f6323f52a1da14';

    /**
     * Convert day code (e.g., "01000") to day name
     *
     * @param string $dayCode The binary day code
     * @return string|null The day name or null if unknown
     */
    function getDayId($dayCode)
    {
        $days = [
            '10000' => Day::where('name', 'Pirmdiena')->first()->id,
            '01000' => Day::where('name', 'Otrdiena')->first()->id,
            '00100' => Day::where('name', 'Trešdiena')->first()->id,
            '00010' => Day::where('name', 'Ceturtdiena')->first()->id,
            '00001' => Day::where('name', 'Piektdiena')->first()->id,
        ];

        return $days[$dayCode] ?? null;
    }

    /**
     * Get the start time for a lesson based on day and period
     *
     * @param int $dayId The day ID from the database
     * @param int $period The period number
     * @return string Time in H:i:s format
     */
    function getStartTime($dayId, $period)
    {
        $isFriday = Day::find($dayId)->name === 'Piektdiena';

        if ($isFriday) {
            $startTimes = [
                1 => '08:10:00',
                3 => '09:40:00',
                5 => '11:10:00',
                7 => '13:00:00',
                9 => '14:30:00',
            ];
        } else {
            $startTimes = [
                1 => '08:30:00',
                3 => '10:10:00',
                5 => '12:30:00',
                7 => '14:00:00',
                9 => '15:30:00',
            ];
        }

        return $startTimes[$period] ?? '08:00:00';
    }

    /**
     * Get the end time for a lesson based on day and period
     *
     * @param int $dayId The day ID from the database
     * @param int $period The period number
     * @return string Time in H:i:s format
     */
    function getEndTime($dayId, $period)
    {
        $startTime = $this->getStartTime($dayId, $period);
        $timestamp = strtotime($startTime) + (80 * 60);
        return date('H:i:s', $timestamp);
    }

    /**
     * Get the common headers for all requests
     *
     * @return array
     */
    protected function getHeaders()
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:138.0) Gecko/20100101 Firefox/138.0',
            'Accept' => '*/*',
            'Accept-Language' => 'en-US,en;q=0.5',
            'Accept-Encoding' => 'gzip, deflate, br, zstd',
            'Referer' => 'https://pteh.edupage.org/',
            'Content-Type' => 'application/json; charset=utf-8',
            'Origin' => 'https://pteh.edupage.org',
            'DNT' => '1',
            'Sec-GPC' => '1',
            'Cookie' => 'PHPSESSID=' . $this->sessionId,
            'Sec-Fetch-Dest' => 'empty',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Site' => 'same-origin',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'no-cache',
        ];
    }

    /**
     * Get available timetables
     */
    public function timetables()
    {
        $this->info('Fetching available timetables...');

        $website = 'https://pteh.edupage.org/timetable/server/ttviewer.js?__func=getTTViewerData';

        $payload = [
            '__args' => [null, 2024],
            '__gsh' => '00000000',
        ];

        $response = Http::withHeaders($this->getHeaders())
            ->post($website, $payload);

        return $response->json();
    }

    /**
     * Get timetable details for a specific timetable number
     *
     * @param string $ttNum The timetable number
     * @return array
     */
    public function getTimetableDetails($ttNum = "37")
    {
        $this->info("Getting details for timetable {$ttNum}...");

        $website = 'https://pteh.edupage.org/timetable/server/regulartt.js?__func=regularttGetData';

        $payload = [
            '__args' => [null, $ttNum],
            '__gsh' => '00000000',
        ];

        $response = Http::withHeaders($this->getHeaders())
            ->post($website, $payload);

        return $response->json();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->createDays();

        $timetables = $this->timetables();
        $this->info('Timetables fetched!');
        $timetables = $timetables['r']['regular']['timetables'];

        $newWeeksCount = 0;
        $newLessonsCount = 0;

        foreach ($timetables as $tt) {
            $ttNum = $tt['tt_num'];

            $week = Week::where('number', $ttNum)->first();

            if (!$week) {
                $week = Week::create([
                    'name' => $tt['text'],
                    'number' => $ttNum,
                    'start_date' => $tt['datefrom'],
                ]);
                $newWeeksCount++;
                $this->info("Created new week: {$week->name} (ID: {$week->id})");
            } else {
                $this->info("Week already exists: {$week->name} (ID: {$week->id})");
            }

            $weekId = $week->id;

            $existingLessonsCount = Lesson::where('week_id', $weekId)->count();

            if ($existingLessonsCount > 0) {
                $this->info("Week {$week->name} already has {$existingLessonsCount} lessons. Skipping...");
                continue;
            }

            $ttDetails = $this->getTimetableDetails($ttNum);
            $this->info('Timetable details fetched!');

            $groups = $this->indexArrayById($ttDetails['r']['dbiAccessorRes']['tables'][15]['data_rows']);
            $teachers = $this->indexArrayById($ttDetails['r']['dbiAccessorRes']['tables'][14]['data_rows']);
            $subjects = $this->indexArrayById($ttDetails['r']['dbiAccessorRes']['tables'][13]['data_rows']);
            $classrooms = $this->indexArrayById($ttDetails['r']['dbiAccessorRes']['tables'][11]['data_rows']);
            $classes = $this->indexArrayById($ttDetails['r']['dbiAccessorRes']['tables'][12]['data_rows']);
            $lessons = $this->indexArrayById($ttDetails['r']['dbiAccessorRes']['tables'][18]['data_rows']);
            $cards = $ttDetails['r']['dbiAccessorRes']['tables'][20]['data_rows'];

            $this->createTeachers($teachers);
            $this->createClassrooms($classrooms);
            $this->createSubjects($subjects);
            $this->createGroups($classes, $teachers);
            $this->createDivisions($groups);

            $newLessonsCount += $this->processCardsAndCreateLessons($cards, $lessons, $weekId, $subjects, $classrooms, $teachers, $classes, $groups);

            $this->info("Processed week {$week->name} successfully.");
        }

        $this->info("Scraping complete: Added {$newWeeksCount} new weeks and {$newLessonsCount} new lessons.");
    }

    /**
     * Create days if they don't exist
     */
    private function createDays()
    {
        $days = [
            'Pirmdiena',
            'Otrdiena',
            'Trešdiena',
            'Ceturtdiena',
            'Piektdiena',
        ];

        foreach ($days as $day) {
            if (!Day::where('name', $day)->exists()) {
                Day::create(['name' => $day]);
            }
        }
    }

    /**
     * Index array by ID field
     */
    private function indexArrayById($array)
    {
        $indexed = [];
        foreach ($array as $item) {
            $indexed[$item['id']] = $item;
        }
        return $indexed;
    }

    /**
     * Create teachers if they don't exist
     */
    private function createTeachers($teachers)
    {
        foreach ($teachers as $teacher) {
            Teacher::firstOrCreate(['name' => $teacher['name']]);
        }
    }

    /**
     * Create classrooms if they don't exist
     */
    private function createClassrooms($classrooms)
    {
        foreach ($classrooms as $classroom) {
            Classroom::firstOrCreate(['name' => $classroom['name']]);
        }
    }

    /**
     * Create subjects if they don't exist
     */
    private function createSubjects($subjects)
    {
        foreach ($subjects as $subject) {
            Subject::firstOrCreate(
                ['name' => $subject['name']],
                ['short' => $subject['short']]
            );
        }
    }

    /**
     * Create groups if they don't exist
     */
    private function createGroups($classes, $teachers)
    {
        foreach ($classes as $class) {
            $teacherId = null;
            if (isset($class['teacherids'][0]) && isset($teachers[$class['teacherids'][0]])) {
                $teacherId = Teacher::where('name', $teachers[$class['teacherids'][0]]['name'])->first()->id;
            }

            Group::firstOrCreate(
                ['name' => $class['name']],
                ['teacher_id' => $teacherId]
            );
        }
    }

    /**
     * Create divisions if they don't exist
     */
    private function createDivisions($groups)
    {
        foreach ($groups as $group) {
            Division::firstOrCreate(['name' => $group['name']]);
        }
    }

    /**
     * Process cards and create lessons
     */
    private function processCardsAndCreateLessons($cards, $lessons, $weekId, $subjects, $classrooms, $teachers, $classes, $groups)
    {
        $newLessonsCount = 0;

        foreach ($cards as $card) {
            $lessonId = $card['lessonid'];

            if (!isset($lessons[$lessonId])) {
                $this->warn("Skipping card with missing lesson: {$lessonId}");
                continue;
            }

            $lesson = $lessons[$lessonId];
            $dayCode = $card['days'];
            $dayId = $this->getDayId($dayCode);

            if ($dayId === null) {
                continue;
            }

            $period = (int)$card['period'];

            $classroomId = null;
            if (isset($card['classroomids']) && !empty($card['classroomids']) && $card['classroomids'][0] !== "") {
                $classroomName = $classrooms[$card['classroomids'][0]]['name'] ?? null;
                if ($classroomName) {
                    $classroom = Classroom::where('name', $classroomName)->first();
                    $classroomId = $classroom->id;
                }
            }

            $subjectId = null;
            if (isset($lesson['subjectid']) && isset($subjects[$lesson['subjectid']])) {
                $subjectName = $subjects[$lesson['subjectid']]['name'];
                $subject = Subject::where('name', $subjectName)->first();
                $subjectId = $subject->id;
            } else {
                $this->warn("Skipping lesson with missing subject: {$lessonId}");
                continue;
            }

            $teacherId = null;
            if (isset($lesson['teacherids']) && !empty($lesson['teacherids'])) {
                $teacherId = $lesson['teacherids'][0];
                if (isset($teachers[$teacherId])) {
                    $teacherName = $teachers[$teacherId]['name'];
                    $teacher = Teacher::where('name', $teacherName)->first();
                    $teacherId = $teacher->id;
                }
            }

            if (!isset($lesson['classids']) || empty($lesson['classids'])) {
                $this->warn("Skipping lesson with no class IDs: {$lessonId}");
                continue;
            }

            foreach ($lesson['classids'] as $classId) {
                if (!isset($classes[$classId])) {
                    $this->warn("Skipping class ID not found in classes: {$classId}");
                    continue;
                }

                $groupName = $classes[$classId]['name'];
                $group = Group::where('name', $groupName)->first();
                if (!$group) {
                    $this->warn("Group not found: {$groupName}");
                    continue;
                }

                $groupId = $group->id;

                if (!isset($lesson['groupids'][0]) || !isset($groups[$lesson['groupids'][0]])) {
                    $this->warn("Division not found for lesson: {$lessonId}");
                    continue;
                }

                $divisionName = $groups[$lesson['groupids'][0]]['name'];
                $division = Division::where('name', $divisionName)->first();
                if (!$division) {
                    $this->warn("Division not found: {$divisionName}");
                    continue;
                }

                $divisionId = $division->id;

                $durationPeriods = $lesson['durationperiods'] ?? 2;
                $repetitions = ceil($durationPeriods / 2);

                for ($i = 0; $i < $repetitions; $i++) {
                    $currentPeriod = $period + ($i * 2);

                    $existingLesson = Lesson::where([
                        'day_id' => $dayId,
                        'week_id' => $weekId,
                        'period' => $currentPeriod,
                        'group_id' => $groupId,
                        'division_id' => $divisionId,
                    ])->first();

                    if (!$existingLesson) {
                        Lesson::create([
                            'day_id' => $dayId,
                            'week_id' => $weekId,
                            'period' => $currentPeriod,
                            'group_id' => $groupId,
                            'division_id' => $divisionId,
                            'subject_id' => $subjectId,
                            'teacher_id' => $teacherId,
                            'classroom_id' => $classroomId,
                            'start' => $this->getStartTime($dayId, $currentPeriod),
                            'end' => $this->getEndTime($dayId, $currentPeriod)
                        ]);

                        $newLessonsCount++;
                    }
                }
            }
        }

        return $newLessonsCount;
    }
}

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
use function PHPUnit\Framework\isNull;

class ScrapeAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:lessons';

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
        $this->info('Scraping timetables...');

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
        // Contains the current available timetables
        $timetables = $this->timetables();
        $this->info('Timetables fetched!');
        $timetables = $timetables['r']['regular']['timetables'];

        foreach ($timetables as $tt) {
            Week::create([
                'name' => $tt['text'],
                'number' => $tt['tt_num'],
                'start_date' => $tt['datefrom'],
            ]);
            $ttNum = $tt['tt_num'];
            $weekId = Week::where('number', $ttNum)->first()->id;

            // Actual info
            $ttDetails = $this->getTimetableDetails($ttNum);
            $this->info('Timetable details fetched!');

            $groups = $ttDetails['r']['dbiAccessorRes']['tables'][15]['data_rows']; // Divisions / groups
            $indexedGroups = [];
            foreach ($groups as $group) {
                $indexedGroups[$group['id']] = $group;
            }
            $groups = $indexedGroups;

            /**
             * Example of a teacher data structure:
             * {
             *     id: "-135", // Teacher ID (negative numbers are common)
             *     nameprefix: "",
             *     namesuffix: "",
             *     name: "John Doe",
             *     short: "",
             *     bell: "",
             *     color: "#FF00CC",
             *     fontcolorprint: "",
             *     fontcolorprint2: "",
             *     customfields: [],
             *     edupageid: "-135",
             *     classids: [ "-139" ] // may be empty
             * }
             */
            $teachers = $ttDetails['r']['dbiAccessorRes']['tables'][14]['data_rows']; // teacher-id array
            $indexedTeachers = [];
            foreach ($teachers as $teacher) {
                $indexedTeachers[$teacher['id']] = $teacher;
            }
            $teachers = $indexedTeachers;

            /**
             * Example of a subject data structure:
             * {
             *     id: "-290",
             *     name: "English Language",
             *     short: "ENG",
             *     color: "#FF0066",
             *     picture_url: "",
             *     contract_weight: 1,
             *     edupageid: "-290"
             * }
             */
            $subjects = $ttDetails['r']['dbiAccessorRes']['tables'][13]['data_rows']; // subject-id array
            $indexedSubjects = [];
            foreach ($subjects as $subject) {
                $indexedSubjects[$subject['id']] = $subject;
            }
            $subjects = $indexedSubjects;

            /**
             * Example of a classroom data structure:
             * {
             *     id: "-1",
             *     name: "C.301.",
             *     short: "C.301.",
             *     buildingid: "",
             *     bell: "",
             *     color: "#FF0000"
             * }
             */
            $classrooms = $ttDetails['r']['dbiAccessorRes']['tables'][11]['data_rows']; // class-id array
            $indexedClassrooms = [];
            foreach ($classrooms as $classroom) {
                $indexedClassrooms[$classroom['id']] = $classroom;
            }
            $classrooms = $indexedClassrooms;

            /**
             * Example of a class/group data structure:
             * {
             *     id: "-122",
             *     name: "IPa22",
             *     short: "",
             *     teacherid: "-202",
             *     classroomids: [],
             *     bell: "0",
             *     color: "#003300",
             *     customfields: [],
             *     printsubjectpictures: true,
             *     edupageid: "-122",
             *     classroomid: "",
             *     teacherids: [ "-202" ]
             * }
             */
            $classes = $ttDetails['r']['dbiAccessorRes']['tables'][12]['data_rows']; // group-id array
            $indexedClasses = [];
            foreach ($classes as $class) {
                $indexedClasses[$class['id']] = $class;
            }
            $classes = $indexedClasses;

            /**
             * Example of a lesson data structure:
             * {
             *     id: "*1",                // Lesson ID (may start with * for special entries)
             *     subjectid: "-52",        // Subject ID for this lesson
             *     teacherids: [ "-199" ],  // Array of teacher IDs who teach this lesson
             *     groupids: [ "*31" ],     // Array of group IDs that attend this lesson
             *     classids: [ "-128" ],    // Array of class IDs associated with this lesson
             *     count: 6,                // Number of lessons per defined period
             *     durationperiods: 8,      // Duration in periods (lesson units)
             *     termsdefid: "*3",        // Term definition ID
             *     weeksdefid: "*3",        // Week pattern definition ID
             *     daysdefid: "*6",         // Days definition ID
             *     terms: "1",              // Terms in which this lesson occurs
             *     seminargroup: null,
             *     texts: null,
             *     studentids: [],
             *     groupnames: [ "" ],
             *     classdata: {
             *         -128: {
             *             divisionid: "-128:",
             *             groups: "1"
             *         }
             *     }
             * }
             */
            $lessons = $ttDetails['r']['dbiAccessorRes']['tables'][18]['data_rows'];
            $indexedLessons = [];
            foreach ($lessons as $lesson) {
                $indexedLessons[$lesson['id']] = $lesson;
            }
            $lessons = $indexedLessons;

            /**
             * Example of a card data structure:
             * {
             *     id: "*1",               // Card ID (may start with * for special entries)
             *     lessonid: "*1",         // Reference to the parent lesson ID
             *     locked: false,          // Whether this card is locked in the timetable
             *     period: "1",            // Period number in the day
             *     days: "01000",          // Binary representation of days (e.g., "01000" = Tuesday)
             *     weeks: "1",             // Week pattern for this card
             *     classroomids: [         // Array of classroom IDs where this lesson takes place
             *         "-92"
             *     ]
             * }
             */
            $cards = $ttDetails['r']['dbiAccessorRes']['tables'][20]['data_rows'];

            // Print all data
            $this->info('Seeding database... ');

            // Create days
            $days = [
                'Pirmdiena',
                'Otrdiena',
                'Trešdiena',
                'Ceturtdiena',
                'Piektdiena',
            ];
            foreach ($days as $day) {
                if (!Day::where('name', $day)->exists()) {
                    Day::create([
                        'name' => $day,
                        'short' => $day == 'Piektdiena' ?  'Pk' : substr($day, 0, 1),
                    ]);
                }
            }

            // Create teachers
            foreach ($teachers as $teacher) {
                if (!Teacher::where('name', $teacher['name'])->exists()) {
                    Teacher::create([
                        'name' => $teacher['name'],
                    ]);
                }
            }

            // Create classrooms
            foreach ($classrooms as $classroom) {
                if (!Classroom::where('name', $classroom['name'])->exists()) {
                    Classroom::create([
                        'name' => $classroom['name'],
                    ]);
                }
            }

            // Create subjects
            foreach ($subjects as $subject) {
                if (!Subject::where('name', $subject['name'])->exists()) {
                    Subject::create([
                        'name' => $subject['name'],
                        'short' => $subject['short'],
                    ]);
                }
            }

            // Create groups
            foreach ($classes as $class) {
                if (!Group::where('name', $class['name'])->exists()) {
                    Group::create([
                        'name' => $class['name'],
                        'teacher_id' => Teacher::where('name', $teachers[$class['teacherids'][0]]['name'])->first()->id,
                    ]);
                }
            }

            // Create divisions
            foreach ($groups as $group) {
                if (!Division::where('name', $group['name'])->exists()) {
                    Division::create([
                        'name' => $group['name'],
                    ]);
                }
            }

            // Modify the loop for creating lessons in the handle method
            foreach ($cards as $card) {
                $lessonId = $card['lessonid'];

                if (!isset($lessons[$lessonId])) {
                    $this->warn("Skipping card with missing lesson: {$lessonId}");
                    continue;
                }

                $lesson = $lessons[$lessonId];
                $dayCode = $card['days'];
                $dayId = $this->getDayId($dayCode);

                // Skip lessons with unknown days
                if ($dayId === null) {
                    continue;
                }

                $period = (int)$card['period'];

                // Extract classroom info
                $classroomId = null;
                if (isset($card['classroomids']) && !empty($card['classroomids']) && $card['classroomids'][0] !== "") {
                    $classroomName = $classrooms[$card['classroomids'][0]]['name'] ?? null;
                    if ($classroomName) {
                        $classroom = Classroom::firstOrCreate(['name' => $classroomName]);
                        $classroomId = $classroom->id;
                    }
                }

                // Extract subject info
                $subjectId = null;
                if (isset($lesson['subjectid']) && isset($subjects[$lesson['subjectid']])) {
                    $subjectName = $subjects[$lesson['subjectid']]['name'];
                    $subject = Subject::firstOrCreate(
                        ['name' => $subjectName],
                        ['short' => $subjects[$lesson['subjectid']]['short'] ?? '']
                    );
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
                        $teacher = Teacher::firstOrCreate(['name' => $teacherName]);
                        $teacherId = $teacher->id;
                    }
                }

                // Make sure we have classIds to work with
                if (!isset($lesson['classids']) || empty($lesson['classids'])) {
                    $this->warn("Skipping lesson with no class IDs: {$lessonId}");
                    continue;
                }

                // Process each class this lesson is for
                foreach ($lesson['classids'] as $classId) {
                    if (!isset($classes[$classId])) {
                        $this->warn("Skipping class ID not found in classes: {$classId}");
                        continue;
                    }

                    $groupName = $classes[$classId]['name'];
                    $group = Group::where('name', $groupName)->first();

                    if (!$group) {
                        // Create the group if it doesn't exist
                        $group = Group::create([
                            'name' => $groupName,
                            'teacher_id' => isset($classes[$classId]['teacherids'][0]) && isset($teachers[$classes[$classId]['teacherids'][0]])
                                ? Teacher::firstOrCreate(['name' => $teachers[$classes[$classId]['teacherids'][0]]['name']])->id
                                : null
                        ]);
                    }

                    $groupId = $group->id;

                    // Time defaults
                    $start = date('H:i:s');
                    $end = date('H:i:s');

                    // Get the number of repetitions
                    $durationPeriods = $lesson['durationperiods'] ?? 2;
                    $repetitions = ceil($durationPeriods / 2);

                    $divisionId = Division::where('name', $groups[$lesson['groupids'][0]]['name'])->first()->id; // Division ID

                    // Create duplicate lessons based on the repetition count
                    for ($i = 0; $i < $repetitions; $i++) {
                        Lesson::updateOrCreate(
                            [
                                'day_id' => $dayId,
                                'week_id' => $weekId,
                                'period' => $period + ($i * 2),
                                'group_id' => $groupId,
                                'division_id' => $divisionId,
                            ],
                            [
                                'subject_id' => $subjectId,
                                'teacher_id' => $teacherId,
                                'classroom_id' => $classroomId,
                                'start' => $this->getStartTime($dayId, $period + ($i * 2)),
                                'end' => $this->getEndTime($dayId, $period + ($i * 2))
                            ]
                        );
                    }
                }
            }
            $this->info('Database seeding completed for week ' . $weekId);
        }
    }
}

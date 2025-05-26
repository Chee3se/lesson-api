import { Navbar } from '@/components/Navbar';
import { ThemeProvider } from '@/providers/ThemeProvider';
import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { Group, Week, WeekLessons } from '@/types/lessons';
import GroupSelector from '@/components/timetable/GroupSelector';
import WeekSelector from '@/components/timetable/WeekSelector';
import DaySelector from '@/components/timetable/DaySelector';
import LessonDisplay from '@/components/timetable/LessonDisplay';

export default function Lessons({ lessonsByWeek, groups, weeks, selectedGroupId, selectedWeekId }: {
    lessonsByWeek: Record<string, WeekLessons>,
    groups: Group[],
    weeks: Week[],
    selectedGroupId: number,
    selectedWeekId: number
}) {
    const [activeWeek, setActiveWeek] = useState(selectedWeekId);
    const [activeDay, setActiveDay] = useState(() => {
        const savedDay = localStorage.getItem('activeLessonsDay');
        return savedDay ? parseInt(savedDay) : 0;
    });
    const [value, setValue] = useState(() => {
        return localStorage.getItem('selectedGroupId') || String(selectedGroupId) || "";
    });

    const currentWeekLessons = lessonsByWeek[activeWeek]?.days || [];

    useEffect(() => {
        const savedDay = localStorage.getItem('activeLessonsDay');

        if (currentWeekLessons.length > 0) {
            if (savedDay && currentWeekLessons.some(day => day.id === parseInt(savedDay))) {
                setActiveDay(parseInt(savedDay));
            } else {
                setActiveDay(currentWeekLessons[0].id);
            }
        }

        const savedWeekId = localStorage.getItem('selectedWeekId');
        if (savedWeekId) {
            setActiveWeek(parseInt(savedWeekId));
        }
    }, [lessonsByWeek, currentWeekLessons, selectedGroupId]);

    useEffect(() => {
        if (activeDay) {
            localStorage.setItem('activeLessonsDay', String(activeDay));
        }
    }, [activeDay]);

    useEffect(() => {
        if (currentWeekLessons.length > 0 && !currentWeekLessons.some(day => day.id === activeDay)) {
            setActiveDay(currentWeekLessons[0].id);
        }
    }, [activeWeek, currentWeekLessons]);

    const handleGroupSelect = (selectedValue: string) => {
        if (selectedValue !== value) {
            setValue(selectedValue);
            localStorage.setItem('selectedGroupId', selectedValue);
            router.visit('/lessons', {
                data: { group_id: selectedValue },
                preserveState: false,
                replace: true,
            });
        } else {
            setValue("");
        }
    };

    const handleWeekSelect = (weekId: number) => {
        setActiveWeek(weekId);
        localStorage.setItem('selectedWeekId', String(weekId));
    };

    const activeDayLessons = currentWeekLessons.find(day => day.id === activeDay)?.lessons || [];

    return (
        <ThemeProvider defaultTheme="system" storageKey="app-theme">
            <div className="min-h-screen bg-background text-foreground flex flex-col">
                <Navbar />
                <main className="flex-1 container mx-auto px-4 py-8">
                    <div className="flex items-center flex-col gap-4 mb-6 sm:grid sm:grid-cols-2 sm:grid-rows-2 md:flex md:flex-row md:items-center">
                        <h1 className="text-3xl font-bold w-full text-center sm:col-span-2 mb-2 md:mb-0 md:mr-auto">Stundu saraksts</h1>

                        <WeekSelector
                            weeks={weeks}
                            activeWeek={activeWeek}
                            onWeekSelect={handleWeekSelect}
                        />

                        <GroupSelector
                            groups={groups}
                            selectedGroupId={value}
                            onGroupSelect={handleGroupSelect}
                        />
                    </div>

                    {/* Desktop view with tabs */}
                    <div className="hidden md:block">
                        <DaySelector
                            days={currentWeekLessons}
                            activeDay={activeDay}
                            setActiveDay={setActiveDay}
                        />
                        <LessonDisplay lessons={activeDayLessons} />
                    </div>

                    {/* Mobile view with vertical scrolling */}
                    <div className="md:hidden">
                        <div className="h-[70vh] overflow-y-auto pr-2 -mr-2 pb-4 scroll-smooth snap-y snap-mandatory">
                            {currentWeekLessons.map(day => (
                                <div
                                    key={day.id}
                                    className="mb-8 min-h-[70vh] snap-start snap-always py-2"
                                >
                                    <h2 className="text-xl font-semibold sticky top-0 bg-background py-3 z-10 border-b mb-4 shadow-sm">
                                        {day.day}
                                    </h2>
                                    <div className="pb-8">
                                        <LessonDisplay lessons={day.lessons} />
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </main>
            </div>
        </ThemeProvider>
    );
}

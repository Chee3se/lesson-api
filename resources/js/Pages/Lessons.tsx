import { Navbar } from '@/components/Navbar';
import { ThemeProvider } from '@/providers/ThemeProvider';
import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { Group, Week, WeekLessons } from '@/types/lessons';
import GroupSelector from '@/components/timetable/GroupSelector';
import WeekSelector from '@/components/timetable/WeekSelector';
import DaySelector from '@/components/timetable/DaySelector';
import LessonDisplay from '@/components/timetable/LessonDisplay';
import { Menu, X } from 'lucide-react';
import { Button } from '@/components/ui/button';

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
    const [menuState, setMenuState] = useState<'closed' | 'opening' | 'open' | 'closing'>('closed');

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

    useEffect(() => {
        if (menuState === 'opening') {
            setMenuState('open');
        } else if (menuState === 'closing') {
            const timer = setTimeout(() => {
                setMenuState('closed');
            }, 300);
            return () => clearTimeout(timer);
        }
    }, [menuState]);

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
        closeMenu();
    };

    const handleWeekSelect = (weekId: number) => {
        setActiveWeek(weekId);
        localStorage.setItem('selectedWeekId', String(weekId));
        closeMenu();
    };

    const toggleMenu = () => {
        if (menuState === 'open' || menuState === 'opening') {
            closeMenu();
        } else {
            setMenuState('opening');
        }
    };

    const closeMenu = () => {
        setMenuState('closing');
    };

    const activeDayLessons = currentWeekLessons.find(day => day.id === activeDay)?.lessons || [];

    const showMenu = menuState !== 'closed';

    return (
        <ThemeProvider defaultTheme="system" storageKey="app-theme">
            <div className="min-h-screen bg-background text-foreground flex flex-col">
                <Navbar />
                <main className="flex-1 container mx-auto px-4 py-8">
                    <div className="hidden md:flex items-center justify-between mb-6">
                        <h1 className="text-3xl font-bold">Stundu saraksts</h1>
                        <div className="flex items-center gap-4">
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
                    </div>

                    <div className="md:hidden fixed top-16 right-4 z-20">
                        <Button
                            variant="outline"
                            size="icon"
                            onClick={toggleMenu}
                            className="shadow-md"
                        >
                            <Menu className="h-5 w-5" />
                        </Button>
                    </div>

                    {showMenu && (
                        <div
                            className={`md:hidden fixed inset-0 z-50 bg-background/95 backdrop-blur-sm ${
                                menuState === 'closing' ? 'animate-out fade-out duration-300' : 'animate-in fade-in duration-300'
                            }`}
                        >
                            <div className={`p-4 flex flex-col h-full ${
                                menuState === 'closing' ? 'animate-out slide-out-to-right duration-300' : 'animate-in slide-in-from-right duration-300'
                            }`}
                            >
                                <div className="flex justify-between items-center mb-8">
                                    <h2 className="text-xl font-semibold">Settings</h2>
                                    <Button variant="ghost" size="icon" onClick={closeMenu}>
                                        <X className="h-6 w-6" />
                                    </Button>
                                </div>

                                <div className="space-y-6">
                                    <div>
                                        <h3 className="text-sm font-medium mb-2">Week</h3>
                                        <WeekSelector
                                            weeks={weeks}
                                            activeWeek={activeWeek}
                                            onWeekSelect={handleWeekSelect}
                                        />
                                    </div>

                                    <div>
                                        <h3 className="text-sm font-medium mb-2">Group</h3>
                                        <GroupSelector
                                            groups={groups}
                                            selectedGroupId={value}
                                            onGroupSelect={handleGroupSelect}
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    <div className="hidden md:block">
                        <DaySelector
                            days={currentWeekLessons}
                            activeDay={activeDay}
                            setActiveDay={setActiveDay}
                        />
                        <LessonDisplay lessons={activeDayLessons} />
                    </div>

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

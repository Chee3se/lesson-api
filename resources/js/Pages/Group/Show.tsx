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
import { route } from 'ziggy-js';

export default function Show({ lessonsByWeek, groups, weeks, selectedGroupId, selectedWeekId }: {
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

    const hasLessons = currentWeekLessons.length > 0 && currentWeekLessons.some(day => day.lessons.length > 0);

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
            router.get(route('group', {id: selectedValue}));
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
                    {hasLessons ? (
                        <>
                            <div className="hidden md:block">
                                <DaySelector
                                    days={currentWeekLessons}
                                    activeDay={activeDay}
                                    setActiveDay={setActiveDay}
                                />
                                <LessonDisplay lessons={activeDayLessons} context="group" />
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
                                                <LessonDisplay lessons={day.lessons} context="group" />
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </>
                    ) : (
                        <div className="flex items-center justify-center min-h-[50vh]">
                            <div className="text-center">
                                <div className="mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" className="h-16 w-16 mx-auto text-muted-foreground" viewBox="0 -960 960 960" width="24px" fill="currentColor">
                                        <path d="M480-500q65 0 112 44t56 109q2 12 11 19.5t21 7.5q12 0 21.5-8t7.5-20q-10-90-74.5-151T480-560q-90 0-154.5 61T251-348q-2 12 7.5 20t21.5 8q12 0 21-7.5t11-19.5q9-65 56-109t112-44Zm-58-159q9-8 8.5-20.5T420-700q-10-8-22-7t-22 9q-16 14-33.5 23.5T305-658q-12 5-19.5 15t-5.5 23q2 13 12 20t21 3q30-10 58-25.5t51-36.5Zm116 0q23 21 50 36t57 25q12 4 22.5-2.5T680-620q2-13-6-23t-20-15q-19-7-36.5-17T584-698q-10-8-22-9t-22 7q-10 8-10.5 20.5T538-659ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"/>
                                    </svg>
                                </div>
                                <h3 className="text-xl font-semibold text-muted-foreground mb-2">
                                    Neviena stunda šajā periodā netika atrasta!
                                </h3>
                                <p className="text-muted-foreground">
                                    Izvēlieties citu nedēļu vai grupu.
                                </p>
                            </div>
                        </div>
                    )}
                </main>
            </div>
        </ThemeProvider>
    );
}

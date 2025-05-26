import { Navbar } from '@/components/Navbar';
import { ThemeProvider } from '@/providers/ThemeProvider';
import { useState, useEffect, useRef } from 'react';
import * as React from "react"
import { Check, ChevronsUpDown } from "lucide-react"
import { router } from '@inertiajs/react';
import { Group, Lesson, LessonGroup, Week, WeekLessons } from '@/types/lessons';
import { cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from "@/components/ui/command"
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover"

export default function Lessons({ lessonsByWeek, groups, weeks, selectedGroupId, selectedWeekId }: {
    lessonsByWeek: Record<string, WeekLessons>,
    groups: Group[],
    weeks: Week[],
    selectedGroupId: number,
    selectedWeekId: number
}) {
    const [activeWeek, setActiveWeek] = React.useState(selectedWeekId);
    const [activeDay, setActiveDay] = useState(0);
    const [open, setOpen] = React.useState(false);
    const [value, setValue] = React.useState(String(selectedGroupId) || "");

    const currentWeekLessons = lessonsByWeek[activeWeek]?.days || [];

    useEffect(() => {
        if (currentWeekLessons.length > 0) {
            const savedDay = localStorage.getItem('activeLessonsDay');
            if (savedDay && currentWeekLessons.some(day => day.id === parseInt(savedDay))) {
                setActiveDay(parseInt(savedDay));
            } else {
                setActiveDay(currentWeekLessons[0].id);
            }
        }

        const savedGroupId = localStorage.getItem('selectedGroupId');
        if (savedGroupId && savedGroupId !== String(selectedGroupId)) {
            router.visit('/lessons', {
                data: { group_id: savedGroupId },
                preserveState: false,
                replace: true,
            });
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
        // Reset active day when week changes if the current day doesn't exist in the new week
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
        setOpen(false);
    };

    const handleWeekSelect = (weekId: number) => {
        setActiveWeek(weekId);
        localStorage.setItem('selectedWeekId', String(weekId));
    };

    const groupLessonsByTime = (lessons: Lesson[]): LessonGroup[] => {
        const groups: Record<string, Lesson[]> = {};

        lessons.forEach(lesson => {
            const timeKey = `${lesson.start}-${lesson.end}`;
            if (!groups[timeKey]) {
                groups[timeKey] = [];
            }
            groups[timeKey].push(lesson);
        });

        return Object.entries(groups).map(([timeKey, lessons]) => ({
            timeKey,
            lessons
        }));
    };

    const activeDayLessons = currentWeekLessons.find(day => day.id === activeDay)?.lessons || [];
    const groupedLessons = groupLessonsByTime(activeDayLessons);

    return (
        <ThemeProvider defaultTheme="system" storageKey="app-theme">
            <div className="min-h-screen bg-background text-foreground flex flex-col">
                <Navbar />
                <main className="flex-1 container mx-auto px-4 py-8">
                    <div className="flex flex-row items-center mb-6">
                        <h1 className="text-3xl font-bold mr-auto">Stundu saraksts</h1>
                        {/* Week Selection */}
                        <div className="flex overflow-x-auto my-auto mr-10 gap-2">
                            {weeks.map(week => (
                                <Button
                                    key={week.id}
                                    onClick={() => handleWeekSelect(week.id)}
                                    variant={activeWeek === week.id ? "default" : "outline"}
                                    size="sm"
                                    className="whitespace-nowrap"
                                >
                                    {week.start_date}
                                </Button>
                            ))}
                        </div>
                        <Popover open={open} onOpenChange={setOpen}>
                            <PopoverTrigger asChild>
                                <Button
                                    variant="outline"
                                    role="combobox"
                                    aria-expanded={open}
                                    className="w-[200px] justify-between"
                                >
                                    {value && groups.find((group) => String(group.id) === String(value))
                                        ? groups.find((group) => String(group.id) === String(value))?.name
                                        : "Izvēlieties grupu..."}
                                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent className="w-[200px] p-0">
                                <Command>
                                    <CommandInput
                                        placeholder="Meklēt grupu..."
                                        className="ring-0 border-0 focus-visible:ring-offset-0 focus-visible:ring-0"
                                    />
                                    <CommandList>
                                        <CommandEmpty>Grupa netika atrasta.</CommandEmpty>
                                        <CommandGroup>
                                            {groups.map((group) => (
                                                <CommandItem
                                                    key={group.id}
                                                    value={group.name}
                                                    onSelect={() => handleGroupSelect(String(group.id))}
                                                >
                                                    <Check
                                                        className={cn(
                                                            "mr-2 h-4 w-4",
                                                            String(group.id) === String(value) ? "opacity-100" : "opacity-0"
                                                        )}
                                                    />
                                                    {group.name}
                                                </CommandItem>
                                            ))}
                                        </CommandGroup>
                                    </CommandList>
                                </Command>
                            </PopoverContent>
                        </Popover>
                    </div>

                    {/* Day Tabs */}
                    <div className="flex overflow-x-auto mb-6 border-b">
                        {currentWeekLessons.map(day => (
                            <button
                                key={day.id}
                                onClick={() => setActiveDay(day.id)}
                                className={`py-2 px-4 font-medium whitespace-nowrap transition-colors ${
                                    activeDay === day.id
                                        ? "border-b-2 border-primary text-primary"
                                        : "text-muted-foreground hover:text-foreground"
                                }`}
                            >
                                {day.day}
                            </button>
                        ))}
                    </div>

                    {/* Lesson List */}
                    <div className="space-y-4">
                        {groupedLessons.map(group => (
                            <div key={group.timeKey} className="space-y-4">
                                {group.lessons.length === 1 ? (
                                    // Single lesson - use original layout
                                    <div
                                        key={group.lessons[0].id}
                                        className="border rounded-lg p-4 bg-card shadow-sm hover:shadow-md transition-shadow"
                                    >
                                        <div className="flex justify-between items-center mb-2">
                                            <div className="flex items-center">
                                                <span className="font-bold text-lg">{group.lessons[0].subject}</span>
                                                {group.lessons[0].division && (
                                                    <span className="ml-2 px-2 py-0.5 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs rounded-full">
                                                        {group.lessons[0].division}
                                                    </span>
                                                )}
                                            </div>
                                            <div className="text-sm font-medium text-muted-foreground">
                                                {group.lessons[0].start} - {group.lessons[0].end}
                                            </div>
                                        </div>

                                        <div className="flex flex-row gap-16 text-sm mt-3">
                                            {group.lessons[0].teacher !== null && (
                                                <div className="flex items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 mr-1 text-muted-foreground" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fillRule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clipRule="evenodd" />
                                                    </svg>
                                                    <span>{group.lessons[0].teacher}</span>
                                                </div>
                                            )}
                                            {group.lessons[0].classroom !== null && (
                                                <div className="flex items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 mr-1 text-muted-foreground" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fillRule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 01-1.581.814L10 13.197l-4.419 3.617A1 1 0 014 16V4z" clipRule="evenodd" />
                                                    </svg>
                                                    <span>{group.lessons[0].classroom}</span>
                                                </div>
                                            )}
                                            {(group.lessons[0].teacher == null && group.lessons[0].classroom == null)  && (
                                                <div className="pb-5"></div>
                                            )}
                                        </div>
                                    </div>
                                ) : (
                                    <div className="space-y-2">
                                        <div className="text-sm font-medium text-muted-foreground">
                                            {group.lessons[0].start} - {group.lessons[0].end}
                                        </div>
                                        <div className={`grid grid-cols-1 ${
                                            group.lessons.length === 2
                                                ? 'sm:grid-cols-2'
                                                : 'sm:grid-cols-2 md:grid-cols-3'
                                        } gap-4`}>
                                            {group.lessons.map(lesson => (
                                                <div
                                                    key={lesson.id}
                                                    className="border rounded-lg p-4 bg-card shadow-sm hover:shadow-md transition-shadow"
                                                >
                                                    <div className="flex items-center mb-2">
                                                        <span className="font-bold text-lg">{lesson.subject}</span>
                                                        {lesson.division && (
                                                            <span className="ml-auto mr-4 px-2 py-0.5 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs rounded-xl">
                                                                {lesson.division}
                                                            </span>
                                                        )}
                                                    </div>

                                                    <div className="flex gap-16 text-sm mt-3">
                                                        <div className="flex items-center">
                                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 mr-1 text-muted-foreground" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fillRule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clipRule="evenodd" />
                                                            </svg>
                                                            <span>{lesson.teacher}</span>
                                                        </div>
                                                        <div className="flex items-center">
                                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 mr-1 text-muted-foreground" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fillRule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 01-1.581.814L10 13.197l-4.419 3.617A1 1 0 014 16V4z" clipRule="evenodd" />
                                                            </svg>
                                                            <span>{lesson.classroom}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                </main>
            </div>
        </ThemeProvider>
    );
}

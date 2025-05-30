import React from 'react';
import { Lesson, LessonGroup } from '@/types/lessons';

type DisplayContext = 'general' | 'teacher' | 'classroom' | 'group';

interface LessonDisplayProps {
    lessons: Lesson[];
    context: DisplayContext;
}

const LessonDisplay: React.FC<LessonDisplayProps> = ({ lessons, context = 'general' }) => {
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

    const shouldShowTeacher = context !== 'teacher';
    const shouldShowClassroom = context !== 'classroom';
    const shouldShowGroup = context !== 'group';

    const groupedLessons = groupLessonsByTime(lessons);

    return (
        <div className="space-y-4">
            {groupedLessons.map(group => (
                <div key={group.timeKey} className="space-y-4">
                    {group.lessons.length === 1 ? (
                        // Single lesson layout
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
                                {(group.lessons[0].teacher !== null && shouldShowTeacher) && (
                                    <div className="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 mr-1 text-muted-foreground" viewBox="0 0 20 20" fill="currentColor">
                                            <path fillRule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clipRule="evenodd" />
                                        </svg>
                                        <span>{group.lessons[0].teacher}</span>
                                    </div>
                                )}
                                {(group.lessons[0].classroom !== null  && shouldShowClassroom) && (
                                    <div className="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 mr-1 text-muted-foreground" viewBox="0 0 20 20" fill="currentColor">
                                            <path fillRule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 01-1.581.814L10 13.197l-4.419 3.617A1 1 0 014 16V4z" clipRule="evenodd" />
                                        </svg>
                                        <span>{group.lessons[0].classroom}</span>
                                    </div>
                                )}
                                {(group.lessons[0].group !== null  && shouldShowGroup) && (
                                    <div className="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 mr-1.5 text-muted-foreground" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                        </svg>
                                        <span>{group.lessons[0].group}</span>
                                    </div>
                                )}
                                {(group.lessons[0].teacher == null && group.lessons[0].classroom == null) && (
                                    <div className="pb-5"></div>
                                )}
                            </div>
                        </div>
                    ) : (
                        // Multiple lessons layout
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
                                            {(lesson.teacher !== null && shouldShowTeacher) && (
                                                <div className="flex items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 mr-1 text-muted-foreground" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fillRule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clipRule="evenodd" />
                                                    </svg>
                                                    <span>{lesson.teacher}</span>
                                                </div>
                                            )}
                                            {(lesson.classroom !== null  && shouldShowClassroom) && (
                                                <div className="flex items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 mr-1 text-muted-foreground" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fillRule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 01-1.581.814L10 13.197l-4.419 3.617A1 1 0 014 16V4z" clipRule="evenodd" />
                                                    </svg>
                                                    <span>{lesson.classroom}</span>
                                                </div>
                                            )}
                                            {(lesson.group !== null  && shouldShowGroup) && (
                                                <div className="flex items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 mr-1.5 text-muted-foreground" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                                    </svg>
                                                    <span>{lesson.group}</span>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            ))}
        </div>
    );
};

export default LessonDisplay;

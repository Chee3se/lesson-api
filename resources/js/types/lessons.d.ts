export interface Lesson {
    id: number;
    period: number;
    subject: string;
    classroom: string;
    teacher: string;
    group: string;
    division: string | null;
    start: string;
    end: string;
}

export interface LessonGroup {
    timeKey: string;
    lessons: Lesson[];
}

export interface Group {
    id: number;
    name: string;
}

export interface Teacher {
    id: number;
    name: string;
}

export interface Classroom {
    id: number;
    name: string;
}

export interface Week {
    id: number;
    name: string;
    number: number;
    start_date: string;
}

export interface Day {
    id: number;
    day: string;
    lessons: Lesson[];
}

export interface WeekLessons {
    week_id: number;
    days: Day[];
}

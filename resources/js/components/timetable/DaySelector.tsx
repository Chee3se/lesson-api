import React from 'react';
import { Day } from '@/types/lessons';

interface DaySelectorProps {
    days: Day[];
    activeDay: number;
    setActiveDay: (id: number) => void;
}

const DaySelector: React.FC<DaySelectorProps> = ({
     days,
     activeDay,
     setActiveDay
 }) => {
    return (
        <div className="flex overflow-x-auto mb-6 border-b">
            {days.map(day => (
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
    );
};

export default DaySelector;

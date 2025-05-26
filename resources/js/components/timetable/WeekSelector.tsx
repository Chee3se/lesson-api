import React from 'react';
import { Week } from '@/types/lessons';
import { Button } from "@/components/ui/button";

interface WeekSelectorProps {
    weeks: Week[];
    activeWeek: number;
    onWeekSelect: (weekId: number) => void;
}

const WeekSelector: React.FC<WeekSelectorProps> = ({
   weeks,
   activeWeek,
   onWeekSelect
}) => {
    return (
        <div className="flex my-auto gap-2">
            {weeks.map(week => (
                <Button
                    key={week.id}
                    onClick={() => onWeekSelect(week.id)}
                    variant={activeWeek === week.id ? "default" : "outline"}
                    size="sm"
                    className="whitespace-nowrap"
                >
                    {week.start_date}
                </Button>
            ))}
        </div>
    );
};

export default WeekSelector;

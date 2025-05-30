import React, { useState, useEffect } from 'react';
import { Check, ChevronsUpDown } from 'lucide-react';
import { Classroom } from '@/types/lessons';
import { cn } from "@/lib/utils";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover";
import { Button } from "@/components/ui/button";
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from "@/components/ui/command";

interface ClassroomSelectorProps {
    classrooms: Classroom[];
    selectedClassroomId: string;
    onClassroomSelect: (value: string) => void;
}

const ClassroomSelector: React.FC<ClassroomSelectorProps> = ({
                                                                 classrooms,
                                                                 selectedClassroomId,
                                                                 onClassroomSelect
                                                             }) => {
    const [open, setOpen] = useState(false);
    const [value, setValue] = useState(selectedClassroomId);

    useEffect(() => {
        setValue(selectedClassroomId);
    }, [selectedClassroomId]);

    const handleClassroomSelect = (selectedValue: string) => {
        if (selectedValue !== value) {
            setValue(selectedValue);
            onClassroomSelect(selectedValue);
        }
        setOpen(false);
    };

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    className="w-[200px] justify-between sm:ml-auto"
                >
                    {value && classrooms.find((classroom) => String(classroom.id) === value)
                        ? classrooms.find((classroom) => String(classroom.id) === value)?.name
                        : "Izvēlieties klasi..."}
                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-[200px] p-0">
                <Command>
                    <CommandInput
                        placeholder="Meklēt klasi..."
                        className="ring-0 border-0 focus-visible:ring-offset-0 focus-visible:ring-0"
                    />
                    <CommandList>
                        <CommandEmpty>Klase netika atrasta.</CommandEmpty>
                        <CommandGroup>
                            {classrooms.map((classroom) => (
                                <CommandItem
                                    key={classroom.id}
                                    value={classroom.name}
                                    onSelect={() => handleClassroomSelect(String(classroom.id))}
                                >
                                    <Check
                                        className={cn(
                                            "mr-2 h-4 w-4",
                                            String(classroom.id) === value ? "opacity-100" : "opacity-0"
                                        )}
                                    />
                                    {classroom.name}
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
};

export default ClassroomSelector;

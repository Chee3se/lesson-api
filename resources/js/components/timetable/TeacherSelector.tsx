import React, { useState, useEffect } from 'react';
import { Check, ChevronsUpDown } from 'lucide-react';
import { Teacher } from '@/types/lessons';
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

interface TeacherSelectorProps {
    teachers: Teacher[];
    selectedTeacherId: string;
    onTeacherSelect: (value: string) => void;
}

const TeacherSelector: React.FC<TeacherSelectorProps> = ({
                                                             teachers,
                                                             selectedTeacherId,
                                                             onTeacherSelect
                                                         }) => {
    const [open, setOpen] = useState(false);
    const [value, setValue] = useState(selectedTeacherId);

    useEffect(() => {
        setValue(selectedTeacherId);
    }, [selectedTeacherId]);

    const handleTeacherSelect = (selectedValue: string) => {
        if (selectedValue !== value) {
            setValue(selectedValue);
            onTeacherSelect(selectedValue);
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
                    {value && teachers.find((teacher) => String(teacher.id) === value)
                        ? teachers.find((teacher) => String(teacher.id) === value)?.name
                        : "Izvēlieties skolotāju..."}
                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-[200px] p-0">
                <Command>
                    <CommandInput
                        placeholder="Meklēt skolotāju..."
                        className="ring-0 border-0 focus-visible:ring-offset-0 focus-visible:ring-0"
                    />
                    <CommandList>
                        <CommandEmpty>Skolotājs netika atrasts.</CommandEmpty>
                        <CommandGroup>
                            {teachers.map((teacher) => (
                                <CommandItem
                                    key={teacher.id}
                                    value={teacher.name}
                                    onSelect={() => handleTeacherSelect(String(teacher.id))}
                                >
                                    <Check
                                        className={cn(
                                            "mr-2 h-4 w-4",
                                            String(teacher.id) === value ? "opacity-100" : "opacity-0"
                                        )}
                                    />
                                    {teacher.name}
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
};

export default TeacherSelector;

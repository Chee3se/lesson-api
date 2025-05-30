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

    // Function to normalize text by removing diacritics and converting to lowercase
    const normalizeText = (text: string): string => {
        return text
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '') // Remove diacritics
            .replace(/[āăâàáäãåą]/g, 'a')
            .replace(/[ēĕêèéëę]/g, 'e')
            .replace(/[īĭîìíïįı]/g, 'i')
            .replace(/[ōŏôòóöõøų]/g, 'o')
            .replace(/[ūŭûùúüů]/g, 'u')
            .replace(/[ć]/g, 'c')
            .replace(/[ģ]/g, 'g')
            .replace(/[ķ]/g, 'k')
            .replace(/[ļ]/g, 'l')
            .replace(/[ņ]/g, 'n')
            .replace(/[ŗ]/g, 'r')
            .replace(/[ş]/g, 's')
            .replace(/[ţ]/g, 't')
            .replace(/[ž]/g, 'z');
    };

    const handleTeacherSelect = (selectedValue: string) => {
        if (selectedValue !== value) {
            setValue(selectedValue);
            onTeacherSelect(selectedValue);
        }
        setOpen(false);
    };

    // Create teachers with normalized search values
    const teachersWithNormalizedSearch = teachers.map(teacher => ({
        ...teacher,
        normalizedName: normalizeText(teacher.name)
    }));

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
                <Command
                    filter={(value, search) => {
                        // Find the teacher with this normalized name
                        const teacher = teachersWithNormalizedSearch.find(t => t.normalizedName === value);
                        if (!teacher) return 0;

                        // Normalize the search input and check if it's included in the teacher's normalized name
                        const normalizedSearch = normalizeText(search);
                        return teacher.normalizedName.includes(normalizedSearch) ? 1 : 0;
                    }}
                >
                    <CommandInput
                        placeholder="Meklēt skolotāju..."
                        className="ring-0 border-0 focus-visible:ring-offset-0 focus-visible:ring-0"
                    />
                    <CommandList>
                        <CommandEmpty>Skolotājs netika atrasts.</CommandEmpty>
                        <CommandGroup>
                            {teachersWithNormalizedSearch.map((teacher) => (
                                <CommandItem
                                    key={teacher.id}
                                    value={teacher.normalizedName}
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

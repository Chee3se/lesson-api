import React, { useState, useEffect } from 'react';
import { Check, ChevronsUpDown } from 'lucide-react';
import { Group } from '@/types/lessons';
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

interface GroupSelectorProps {
    groups: Group[];
    selectedGroupId: string;
    onGroupSelect: (value: string) => void;
}

const GroupSelector: React.FC<GroupSelectorProps> = ({
     groups,
     selectedGroupId,
     onGroupSelect
 }) => {
    const [open, setOpen] = useState(false);
    const [value, setValue] = useState(selectedGroupId);

    useEffect(() => {
        setValue(selectedGroupId);
    }, [selectedGroupId]);

    const handleGroupSelect = (selectedValue: string) => {
        if (selectedValue !== value) {
            setValue(selectedValue);
            onGroupSelect(selectedValue);
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
                    {value && groups.find((group) => String(group.id) === value)
                        ? groups.find((group) => String(group.id) === value)?.name
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
                                            String(group.id) === value ? "opacity-100" : "opacity-0"
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
    );
};

export default GroupSelector;

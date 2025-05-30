import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { ThemeSwitcher } from '@/components/ThemeSwitcher';
import { ChevronDown } from 'lucide-react';
import { route } from 'ziggy-js';
import { router } from '@inertiajs/react';

export function Navbar() {
    const storedGroupId = localStorage.getItem('selectedGroupId');
    const storedTeacherId = localStorage.getItem('storedTeacherId');
    const storedClassroomId = localStorage.getItem('classroomId');
    return (
        <header className="w-full border-b border-border/40 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
            <div className="container flex h-14 max-w-screen-2xl items-center justify-between mx-auto">
                <div className="w-1/4 hidden md:block">
                    {/* Left spacer to balance layout */}
                </div>
                <div className="flex justify-center flex-1">
                    <nav className="flex items-center space-x-4 text-sm font-medium">
                        <a
                            href="/"
                            className="transition-colors hover:text-foreground/80 pr-4"
                        >
                            Home
                        </a>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild className="focus-visible:ring-0">
                                <Button
                                    variant="ghost"
                                    className="flex items-center gap-1 pl-4 pr-2"
                                >
                                    Search <ChevronDown className="h-4 w-4 opacity-50" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-[160px]">
                                <DropdownMenuItem onClick={() => router.get(storedGroupId ? route('group', {id: storedGroupId}) : route('group', {id: "31"}))}>Group</DropdownMenuItem>
                                <DropdownMenuItem onClick={() => router.get(storedTeacherId ? route('teacher', {id: storedTeacherId}) : route('teacher', {id: "1"}))}>Teacher</DropdownMenuItem>
                                <DropdownMenuItem onClick={() => router.get(storedClassroomId ? route('classroom', {id: storedClassroomId}) : route('classroom', {id: "1"}))}>Classroom</DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild className="focus-visible:ring-0">
                                <Button
                                    variant="ghost"
                                    className="flex items-center gap-1 pl-4 pr-2"
                                >
                                    API <ChevronDown className="h-4 w-4 opacity-50" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-[160px]">
                                <DropdownMenuItem onClick={() => window.open(route('api.timetable'), '_blank')}>Timetable</DropdownMenuItem>
                                <DropdownMenuItem onClick={() => window.open(route('api.lessons'), '_blank')}>Lessons</DropdownMenuItem>
                                <DropdownMenuItem onClick={() => window.open(route('api.groups'), '_blank')}>Groups</DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </nav>
                </div>
                <div className="w-1/4 flex justify-end space-x-2 mr-4">
                    <ThemeSwitcher />
                </div>
            </div>
        </header>
    );
}

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

export function Navbar() {
    return (
        <header className="w-full border-b border-border/40 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
            <div className="container flex h-14 max-w-screen-2xl items-center">
                <div className="flex justify-center w-full md:justify-start md:w-auto md:ml-24 sm:ml-12 ml-0">
                    <nav className="flex items-center space-x-6 text-sm font-medium">
                        <a
                            href="/"
                            className="transition-colors hover:text-foreground/80"
                        >
                            Home
                        </a>
                        <a
                            href="/lessons"
                            className="transition-colors hover:text-foreground/80"
                        >
                            Lessons
                        </a>
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
                <div className="flex flex-1 md:justify-end space-x-2 mr-4">
                    <ThemeSwitcher />
                </div>
            </div>
        </header>
    );
}

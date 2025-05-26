import { Navbar } from '@/components/Navbar';
import { AnimatedHeading, AnimatedParagraph } from '@/components/AnimatedText';
import { ThemeProvider } from '@/providers/ThemeProvider';

function Home() {
    return (
        <ThemeProvider defaultTheme="system" storageKey="app-theme">
            <div className="min-h-screen bg-background text-foreground flex flex-col">
                <Navbar />
                <main className="flex-1">
                    <section className="w-full py-12 md:py-24 lg:py-32 flex items-center justify-center">
                        <div className="container px-4 md:px-6">
                            <div className="flex flex-col items-center justify-center space-y-4 text-center">
                                <AnimatedHeading className="text-3xl font-bold tracking-tighter sm:text-4xl md:text-5xl lg:text-6xl">
                                    Lesson API
                                </AnimatedHeading>
                                <AnimatedParagraph className="mx-auto max-w-[700px] text-muted-foreground md:text-xl">
                                    A simple API built for making your own lesson list and timetable.
                                </AnimatedParagraph>
                            </div>
                        </div>
                    </section>
                </main>
            </div>
        </ThemeProvider>
    );
}

export default Home;

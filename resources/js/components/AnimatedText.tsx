import { cn } from '@/lib/utils';
import { useEffect, useState } from 'react';

interface AnimatedTextProps {
    text: string;
    className?: string;
    delay?: number;
}

export function AnimatedText({
                                 text,
                                 className,
                                 delay = 100,
                             }: AnimatedTextProps) {
    const [displayedText, setDisplayedText] = useState('');
    const [currentIndex, setCurrentIndex] = useState(0);

    useEffect(() => {
        if (currentIndex < text.length) {
            const timer = setTimeout(() => {
                setDisplayedText((prev) => prev + text.charAt(currentIndex));
                setCurrentIndex((prev) => prev + 1);
            }, delay);

            return () => clearTimeout(timer);
        }
    }, [currentIndex, delay, text]);

    return (
        <span className={cn('relative', className)}>
      {displayedText}
            <span className="animate-pulse">|</span>
    </span>
    );
}

export function AnimatedHeading({
                                    children,
                                    className,
                                }: {
    children: React.ReactNode;
    className?: string;
}) {
    const [isVisible, setIsVisible] = useState(false);

    useEffect(() => {
        const timer = setTimeout(() => {
            setIsVisible(true);
        }, 300);

        return () => clearTimeout(timer);
    }, []);

    return (
        <h1
            className={cn(
                'transition-all duration-700',
                isVisible
                    ? 'translate-y-0 opacity-100'
                    : 'translate-y-4 opacity-0',
                className
            )}
        >
            {children}
        </h1>
    );
}

export function AnimatedParagraph({
                                      children,
                                      className,
                                      delay = 600,
                                  }: {
    children: React.ReactNode;
    className?: string;
    delay?: number;
}) {
    const [isVisible, setIsVisible] = useState(false);

    useEffect(() => {
        const timer = setTimeout(() => {
            setIsVisible(true);
        }, delay);

        return () => clearTimeout(timer);
    }, [delay]);

    return (
        <p
            className={cn(
                'transition-all duration-700',
                isVisible
                    ? 'translate-y-0 opacity-100'
                    : 'translate-y-4 opacity-0',
                className
            )}
        >
            {children}
        </p>
    );
}

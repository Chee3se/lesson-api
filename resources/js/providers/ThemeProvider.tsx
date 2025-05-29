import { createContext, useContext, useEffect, useState } from 'react';

type Theme = 'dark' | 'light' | 'system';

type ThemeProviderProps = {
    children: React.ReactNode;
    defaultTheme?: Theme;
    storageKey?: string;
    lightFavicon?: string;
    darkFavicon?: string;
};

type ThemeProviderState = {
    theme: Theme;
    setTheme: (theme: Theme) => void;
};

const initialState: ThemeProviderState = {
    theme: 'system',
    setTheme: () => null,
};

const ThemeProviderContext = createContext<ThemeProviderState>(initialState);

export function ThemeProvider({
                                  children,
                                  defaultTheme = 'system',
                                  storageKey = 'vite-ui-theme',
                                  lightFavicon = '/favicon-light.ico',
                                  darkFavicon = '/favicon-dark.ico',
                                  ...props
                              }: ThemeProviderProps) {
    const [theme, setTheme] = useState<Theme>(
        () => (localStorage.getItem(storageKey) as Theme) || defaultTheme
    );

    const updateFavicon = (currentTheme: 'light' | 'dark') => {
        const favicon = document.querySelector('link[rel="icon"]') as HTMLLinkElement;
        const faviconPath = currentTheme === 'dark' ? darkFavicon : lightFavicon;

        if (favicon) {
            favicon.href = faviconPath;
        } else {
            // Create favicon link if it doesn't exist
            const newFavicon = document.createElement('link');
            newFavicon.rel = 'icon';
            newFavicon.href = faviconPath;
            document.head.appendChild(newFavicon);
        }
    };

    useEffect(() => {
        const root = window.document.documentElement;
        root.classList.remove('light', 'dark');

        let resolvedTheme: 'light' | 'dark';

        if (theme === 'system') {
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)')
                .matches
                ? 'dark'
                : 'light';

            root.classList.add(systemTheme);
            resolvedTheme = systemTheme;
        } else {
            root.classList.add(theme);
            resolvedTheme = theme;
        }

        updateFavicon(resolvedTheme);
    }, [theme, lightFavicon, darkFavicon]);

    // Listen for system theme changes when theme is set to 'system'
    useEffect(() => {
        if (theme !== 'system') return;

        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

        const handleChange = (e: MediaQueryListEvent) => {
            const root = window.document.documentElement;
            const systemTheme = e.matches ? 'dark' : 'light';

            root.classList.remove('light', 'dark');
            root.classList.add(systemTheme);
            updateFavicon(systemTheme);
        };

        mediaQuery.addEventListener('change', handleChange);

        return () => mediaQuery.removeEventListener('change', handleChange);
    }, [theme, lightFavicon, darkFavicon]);

    const value = {
        theme,
        setTheme: (theme: Theme) => {
            localStorage.setItem(storageKey, theme);
            setTheme(theme);
        },
    };

    return (
        <ThemeProviderContext.Provider {...props} value={value}>
            {children}
        </ThemeProviderContext.Provider>
    );
}

export const useTheme = () => {
    const context = useContext(ThemeProviderContext);

    if (context === undefined)
        throw new Error('useTheme must be used within a ThemeProvider');

    return context;
};

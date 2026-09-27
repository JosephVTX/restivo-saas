import { useCallback, useEffect, useState } from 'react';

export type Theme = 'restivo' | 'restivo-dark';

const DEFAULT_THEME: Theme = 'restivo';

function initialTheme(): Theme {
    if (typeof document === 'undefined') {
        return DEFAULT_THEME;
    }

    const stored = localStorage.getItem('theme') as Theme | null;

    if (stored === 'restivo' || stored === 'restivo-dark') {
        return stored;
    }

    const attr = document.documentElement.getAttribute('data-theme');

    return attr === 'restivo-dark' ? 'restivo-dark' : DEFAULT_THEME;
}

export function useTheme() {
    const [theme, setThemeState] = useState<Theme>(initialTheme);

    const setTheme = useCallback((next: Theme) => {
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
        setThemeState(next);
    }, []);

    const toggle = useCallback(() => {
        setTheme(theme === 'restivo-dark' ? 'restivo' : 'restivo-dark');
    }, [theme, setTheme]);

    useEffect(() => {
        document.documentElement.setAttribute('data-theme', theme);
    }, [theme]);

    return { theme, setTheme, toggle };
}

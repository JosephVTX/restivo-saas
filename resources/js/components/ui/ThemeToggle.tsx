import { useTheme } from '@/hooks/use-theme';

export function ThemeToggle() {
    const { theme, toggle } = useTheme();

    return (
        <button
            type="button"
            className="btn btn-ghost btn-sm btn-circle"
            onClick={toggle}
            aria-label="Cambiar tema"
            title="Cambiar tema"
        >
            <i className={`fa-solid ${theme === 'restivo-dark' ? 'fa-sun' : 'fa-moon'}`} aria-hidden="true" />
        </button>
    );
}

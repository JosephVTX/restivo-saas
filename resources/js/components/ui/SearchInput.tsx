import { cn } from '@/lib/utils';

export function SearchInput({
    value,
    onChange,
    placeholder = 'Buscar…',
    className,
}: {
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    className?: string;
}) {
    return (
        <label className={cn('input input-sm w-full max-w-xs', className)}>
            <i className="fa-solid fa-magnifying-glass opacity-50" aria-hidden="true" />
            <input
                type="search"
                placeholder={placeholder}
                value={value}
                onChange={(event) => onChange(event.target.value)}
            />
        </label>
    );
}

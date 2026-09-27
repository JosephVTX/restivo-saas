import type { EnumOption } from '@/types';

export function StatusFilter({
    value,
    onChange,
    options,
    allLabel = 'Todos los estados',
}: {
    value: string;
    onChange: (value: string) => void;
    options: EnumOption[];
    allLabel?: string;
}) {
    return (
        <select className="select select-sm" value={value} onChange={(event) => onChange(event.target.value)}>
            <option value="">{allLabel}</option>
            {options.map((option) => (
                <option key={option.value} value={option.value}>
                    {option.label}
                </option>
            ))}
        </select>
    );
}

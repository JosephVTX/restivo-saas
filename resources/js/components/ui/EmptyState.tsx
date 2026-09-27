import type { ReactNode } from 'react';

export function EmptyState({
    icon = 'fa-inbox',
    title,
    children,
}: {
    icon?: string;
    title: string;
    children?: ReactNode;
}) {
    return (
        <div className="flex flex-col items-center justify-center gap-3 px-6 py-16 text-center">
            <div className="grid h-14 w-14 place-items-center rounded-full bg-base-200">
                <i className={`fa-solid ${icon} text-xl opacity-50`} aria-hidden="true" />
            </div>
            <p className="font-medium">{title}</p>
            {children ? <div className="max-w-sm text-sm opacity-70">{children}</div> : null}
        </div>
    );
}

import type { ReactNode } from 'react';
import { EmptyState } from '@/components/ui/EmptyState';

/**
 * Shared list table: handles the scroll container, loading spinner, empty state
 * and header row so pages only render their `<tr>` rows.
 */
export function TableShell({
    head,
    isLoading = false,
    isEmpty = false,
    empty,
    children,
}: {
    head: string[];
    isLoading?: boolean;
    isEmpty?: boolean;
    empty?: ReactNode;
    children: ReactNode;
}) {
    return (
        <div className="overflow-x-auto rounded-box border border-base-300 bg-base-100">
            {isLoading ? (
                <div className="flex justify-center py-16">
                    <span className="loading loading-spinner" />
                </div>
            ) : isEmpty ? (
                empty ?? <EmptyState title="Sin resultados" />
            ) : (
                <table className="table">
                    <thead>
                        <tr>
                            {head.map((label, index) => (
                                <th key={index}>{label}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>{children}</tbody>
                </table>
            )}
        </div>
    );
}

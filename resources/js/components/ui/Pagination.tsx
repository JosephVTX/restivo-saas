import type { PaginationLink } from '@/types';
import { cn } from '@/lib/utils';

export function Pagination({
    meta,
    onChange,
}: {
    meta?: { current_page: number; last_page: number; total: number; links: PaginationLink[] };
    onChange: (page: number) => void;
}) {
    const links = meta?.links ?? [];
    const current = meta?.current_page ?? 1;

    if (links.length <= 3) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center justify-between gap-3">
            <p className="text-sm opacity-70">{meta?.total ?? 0} en total</p>
            <div className="join">
                {links.map((link, index) => {
                    const page = Number(link.label);
                    const isNumber = !Number.isNaN(page);

                    if (!isNumber) {
                        const isPrev = index === 0;
                        const target = current + (isPrev ? -1 : 1);

                        return (
                            <button
                                key={`nav-${index}`}
                                type="button"
                                className="join-item btn btn-sm"
                                disabled={link.url === null}
                                onClick={() => onChange(target)}
                            >
                                <i
                                    className={`fa-solid ${isPrev ? 'fa-chevron-left' : 'fa-chevron-right'}`}
                                    aria-hidden="true"
                                />
                            </button>
                        );
                    }

                    return (
                        <button
                            key={`page-${index}`}
                            type="button"
                            className={cn('join-item btn btn-sm', link.active && 'btn-primary')}
                            onClick={() => onChange(page)}
                        >
                            {link.label}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}

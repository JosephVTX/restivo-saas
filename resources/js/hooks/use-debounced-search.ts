import { useCallback, useEffect, useRef, useState } from 'react';

/**
 * Debounced search state for list pages (SWR + QueryBuilder).
 *
 * - `search` is the immediate input value (bound to the input).
 * - `query` is the debounced value (use it in the SWR key / request).
 * - `change` updates the input and, after `delay`, commits `query` and resets
 *   the page to 1 — so we neither fetch per keystroke nor fetch a stale page.
 */
export function useDebouncedSearch(delay = 350) {
    const [search, setSearch] = useState('');
    const [query, setQuery] = useState('');
    const [page, setPage] = useState(1);
    const timer = useRef<ReturnType<typeof setTimeout> | undefined>(undefined);

    const change = useCallback(
        (value: string) => {
            setSearch(value);
            clearTimeout(timer.current);
            timer.current = setTimeout(() => {
                setQuery(value);
                setPage(1);
            }, delay);
        },
        [delay],
    );

    useEffect(() => () => clearTimeout(timer.current), []);

    return { search, query, change, page, setPage };
}

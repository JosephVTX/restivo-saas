import type { ReactNode } from 'react';
import { SWRConfig } from 'swr';
import { fetcher } from './http';

export function SWRProvider({ children }: { children: ReactNode }) {
    return (
        <SWRConfig
            value={{
                fetcher,
                revalidateOnFocus: false,
                shouldRetryOnError: false,
                keepPreviousData: true,
            }}
        >
            {children}
        </SWRConfig>
    );
}

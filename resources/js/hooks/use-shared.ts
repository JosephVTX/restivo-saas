import { usePage } from '@inertiajs/react';
import type { SharedData } from '@/types';

export function useShared(): SharedData {
    return usePage().props as unknown as SharedData;
}

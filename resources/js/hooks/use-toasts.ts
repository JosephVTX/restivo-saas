import { useEffect, useState } from 'react';
import { dismissToast, getToastsSnapshot, subscribeToasts, type ToastItem } from '@/lib/toast';

export function useToasts() {
    const [toasts, setToasts] = useState<ToastItem[]>(() => getToastsSnapshot());

    useEffect(() => subscribeToasts(() => setToasts(getToastsSnapshot())), []);

    return { toasts, dismiss: dismissToast };
}

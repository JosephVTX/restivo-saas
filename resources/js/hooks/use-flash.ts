import { usePage } from '@inertiajs/react';
import { useState } from 'react';

interface Toast {
    type: 'success' | 'error';
    message: string;
}

export function useFlash() {
    const { props } = usePage();
    const flash = (props.flash ?? {}) as { success?: string | null; error?: string | null };
    const [dismissed, setDismissed] = useState<string[]>([]);

    const toasts: Toast[] = [];

    if (flash.success && !dismissed.includes(`success:${flash.success}`)) {
        toasts.push({ type: 'success', message: flash.success });
    }

    if (flash.error && !dismissed.includes(`error:${flash.error}`)) {
        toasts.push({ type: 'error', message: flash.error });
    }

    const dismiss = () => {
        setDismissed(toasts.map((toast) => `${toast.type}:${toast.message}`));
    };

    return { toasts, dismiss };
}

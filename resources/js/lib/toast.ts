export type ToastType = 'success' | 'error' | 'info';

export interface ToastItem {
    id: number;
    type: ToastType;
    message: string;
}

let counter = 0;
let items: ToastItem[] = [];
const listeners = new Set<() => void>();

function emit(): void {
    listeners.forEach((listener) => listener());
}

export function subscribeToasts(listener: () => void): () => void {
    listeners.add(listener);

    return () => {
        listeners.delete(listener);
    };
}

export function getToastsSnapshot(): ToastItem[] {
    return items;
}

export function dismissToast(id: number): void {
    items = items.filter((toast) => toast.id !== id);
    emit();
}

export function pushToast(type: ToastType, message: string, duration = 4000): number {
    const id = ++counter;

    items = [...items, { id, type, message }];
    emit();

    if (duration > 0) {
        window.setTimeout(() => dismissToast(id), duration);
    }

    return id;
}

/**
 * Imperative toast API usable from anywhere (event handlers, http layer).
 * Rendered globally by <FlashToasts />.
 */
export const toast = {
    success: (message: string) => pushToast('success', message),
    error: (message: string) => pushToast('error', message),
    info: (message: string) => pushToast('info', message),
};

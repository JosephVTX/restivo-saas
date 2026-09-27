import { useRef, useState } from 'react';
import { cn } from '@/lib/utils';

/**
 * Reusable drag-and-drop image picker. Handles drop, click-to-select, and a
 * thumbnail grid of every image (saved + selected). Files at index >=
 * `removableFrom` show a remove button. When `canAdd` is false it hides the
 * "Agregar" tile and ignores new drops. No upload logic — the caller decides.
 */
export function FileDropzone({
    accept = 'image/jpeg,image/png,image/webp,image/gif',
    previews = [],
    removableFrom = 0,
    canAdd = true,
    onFiles,
    onRemove,
    disabled = false,
    multiple = true,
    hint,
}: {
    accept?: string;
    previews?: string[];
    removableFrom?: number;
    canAdd?: boolean;
    onFiles: (files: File[]) => void;
    onRemove?: (index: number) => void;
    disabled?: boolean;
    multiple?: boolean;
    hint?: string;
}) {
    const input = useRef<HTMLInputElement>(null);
    const [dragging, setDragging] = useState(false);

    const canPick = !disabled && canAdd;

    const pick = (list: FileList | null | undefined) => {
        if (!canPick) {
            return;
        }

        const files = Array.from(list ?? []).filter((file) => file.size > 0);

        if (files.length > 0) {
            onFiles(multiple ? files : files.slice(0, 1));
        }
    };

    const openPicker = () => {
        if (canPick) {
            input.current?.click();
        }
    };

    return (
        <div
            onDragOver={(event) => {
                event.preventDefault();
                if (canPick) {
                    setDragging(true);
                }
            }}
            onDragLeave={() => setDragging(false)}
            onDrop={(event) => {
                event.preventDefault();
                setDragging(false);
                pick(event.dataTransfer.files);
            }}
            className={cn(
                'rounded-box border-2 border-dashed p-3 transition',
                dragging ? 'border-primary bg-primary/5' : 'border-base-300',
                disabled && 'opacity-60',
            )}
        >
            {previews.length > 0 ? (
                <div className="grid grid-cols-3 gap-2 sm:grid-cols-4">
                    {previews.map((url, index) => (
                        <div key={`${url}-${index}`} className="relative">
                            <img src={url} alt="" className="h-20 w-full rounded-box object-cover" />
                            {onRemove && !disabled && index >= removableFrom ? (
                                <button
                                    type="button"
                                    className="btn btn-circle btn-error btn-xs absolute -top-1 -right-1"
                                    onClick={() => onRemove(index)}
                                    aria-label="Quitar imagen"
                                >
                                    <i className="fa-solid fa-xmark" aria-hidden="true" />
                                </button>
                            ) : null}
                        </div>
                    ))}

                    {canPick ? (
                        <button
                            type="button"
                            onClick={openPicker}
                            className="grid h-20 place-items-center rounded-box border border-dashed border-base-300 text-xs opacity-70 transition hover:border-primary/60 hover:opacity-100"
                        >
                            <span className="flex flex-col items-center gap-1">
                                <i className="fa-solid fa-plus" aria-hidden="true" />
                                Agregar
                            </span>
                        </button>
                    ) : null}
                </div>
            ) : (
                <div
                    role="button"
                    tabIndex={canPick ? 0 : -1}
                    aria-disabled={!canPick}
                    onClick={openPicker}
                    onKeyDown={(event) => {
                        if (event.key === 'Enter' || event.key === ' ') {
                            event.preventDefault();
                            openPicker();
                        }
                    }}
                    className={cn(
                        'flex flex-col items-center justify-center gap-2 py-6 text-center',
                        canPick ? 'cursor-pointer' : 'cursor-not-allowed opacity-60',
                    )}
                >
                    <i className="fa-solid fa-cloud-arrow-up text-2xl opacity-60" aria-hidden="true" />
                    <span className="text-sm font-medium">Arrastra una imagen aquí</span>
                    <span className="text-xs opacity-60">
                        {multiple ? 'o haz clic para seleccionar (varias a la vez)' : 'o haz clic para seleccionar'}
                    </span>
                </div>
            )}

            <input
                ref={input}
                type="file"
                accept={accept}
                multiple={multiple}
                className="hidden"
                disabled={!canPick}
                onChange={(event) => {
                    pick(event.target.files);
                    event.target.value = '';
                }}
            />

            {hint ? <p className="mt-2 text-xs opacity-60">{hint}</p> : null}
        </div>
    );
}

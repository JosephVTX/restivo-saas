import type { ReactNode } from 'react';

/**
 * daisyUI v5 form field wrapper.
 *
 * v5 removed `form-control` / `label-text` in favour of `fieldset` +
 * `fieldset-legend` + `label`. Always build forms through this component so
 * inputs stay aligned.
 */
export function Field({
    label,
    error,
    hint,
    children,
}: {
    label: string;
    error?: string;
    hint?: string;
    children: ReactNode;
}) {
    return (
        <fieldset className="fieldset min-w-0">
            <legend className="fieldset-legend">{label}</legend>
            {children}
            {error ? (
                <p className="mt-1 flex items-start gap-1.5 text-sm text-error">
                    <i className="fa-solid fa-circle-exclamation mt-0.5 shrink-0" aria-hidden="true" />
                    <span className="min-w-0 break-words">{error}</span>
                </p>
            ) : hint ? (
                <p className="mt-1 text-sm opacity-60">{hint}</p>
            ) : null}
        </fieldset>
    );
}

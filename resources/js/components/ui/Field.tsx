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
        <fieldset className="fieldset">
            <legend className="fieldset-legend">{label}</legend>
            {children}
            {error ? (
                <p className="label text-error">
                    <i className="fa-solid fa-circle-exclamation" aria-hidden="true" /> {error}
                </p>
            ) : hint ? (
                <p className="label">{hint}</p>
            ) : null}
        </fieldset>
    );
}

import { Head } from '@inertiajs/react';
import { useRef, useState } from 'react';
import useSWR from 'swr';
import AppLayout from '@/components/layout/AppLayout';
import { Field } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { validationErrors } from '@/lib/http';
import { http } from '@/lib/axios';
import { billingSchema, type BillingValues } from '@/schemas/billing';
import type { BillingMode, BillingSetting, EnumOption } from '@/types';

interface Props {
    billingModeOptions: EnumOption<BillingMode>[];
}

function errorMessage(error: unknown): string | null {
    return (error as { response?: { data?: { message?: string } } })?.response?.data?.message ?? null;
}

function errorsFor(error: unknown): Record<string, string> {
    const fieldErrors = validationErrors(error);
    const message = errorMessage(error);

    return message && Object.keys(fieldErrors).length === 0 ? { message } : fieldErrors;
}

function toValues(settings: BillingSetting): BillingValues {
    return {
        enabled: settings.enabled,
        ruc: settings.ruc ?? '',
        business_name: settings.business_name ?? '',
        trade_name: settings.trade_name ?? '',
        address: settings.address ?? '',
        ubigeo: settings.ubigeo ?? '',
        email: settings.email ?? '',
        phone: settings.phone ?? '',
        sol_user: settings.sol_user ?? '',
        sol_password: '',
        mode: settings.mode ?? 'beta',
        boleta_series: settings.boleta_series ?? '',
        factura_series: settings.factura_series ?? '',
        legend: settings.legend ?? '',
        igv_rate: Number(settings.igv_rate ?? 0.18),
    };
}

export default function BillingSettings({ billingModeOptions }: Props) {
    const { data, isLoading, mutate } = useSWR<{ data: BillingSetting }>('/api/v1/billing/settings');

    const settings = data?.data ?? null;
    const [edited, setEdited] = useState<BillingValues | null>(null);
    const values = edited ?? (settings ? toValues(settings) : null);
    const setValues = (next: BillingValues) => setEdited(next);

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [saving, setSaving] = useState(false);
    const [saved, setSaved] = useState(false);

    const [file, setFile] = useState<File | null>(null);
    const [certificatePassword, setCertificatePassword] = useState('');
    const [certErrors, setCertErrors] = useState<Record<string, string>>({});
    const [uploading, setUploading] = useState(false);
    const fileInput = useRef<HTMLInputElement>(null);

    const save = async () => {
        if (!values) {
            return;
        }

        const parsed = billingSchema.safeParse(values);

        if (!parsed.success) {
            setErrors(Object.fromEntries(parsed.error.issues.map((issue) => [String(issue.path[0]), issue.message])));

            return;
        }

        setSaving(true);
        setErrors({});
        setSaved(false);

        try {
            const { sol_password, ...rest } = parsed.data;
            const payload = sol_password ? { ...rest, sol_password } : rest;

            await http.put('/api/v1/billing/settings', payload);
            await mutate();
            setEdited(null);
            setSaved(true);
        } catch (error) {
            setErrors(errorsFor(error));
        } finally {
            setSaving(false);
        }
    };

    const uploadCertificate = async () => {
        if (!file) {
            setCertErrors({ certificate: 'Selecciona un archivo de certificado.' });

            return;
        }

        const formData = new FormData();
        formData.append('certificate', file);

        if (certificatePassword) {
            formData.append('certificate_password', certificatePassword);
        }

        setUploading(true);
        setCertErrors({});

        try {
            await http.post('/api/v1/billing/settings/certificate', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            await mutate();
            setFile(null);
            setCertificatePassword('');

            if (fileInput.current) {
                fileInput.current.value = '';
            }
        } catch (error) {
            setCertErrors(errorsFor(error));
        } finally {
            setUploading(false);
        }
    };

    return (
        <AppLayout title="Facturación">
            <Head title="Facturación" />
            <PageHeader title="Facturación electrónica" description="Configura los datos de SUNAT para emitir comprobantes." />

            {isLoading || !values ? (
                <div className="flex justify-center py-16">
                    <span className="loading loading-spinner" />
                </div>
            ) : (
                <div className="mt-4 space-y-6">
                    <div className="flex flex-wrap gap-2">
                        <span className={settings?.enabled ? 'badge badge-success' : 'badge badge-ghost'}>
                            {settings?.enabled ? 'Facturación activa' : 'Facturación inactiva'}
                        </span>
                        <span className={settings?.has_certificate ? 'badge badge-success' : 'badge badge-ghost'}>
                            {settings?.has_certificate ? 'Certificado cargado' : 'Sin certificado'}
                        </span>
                        <span
                            className={
                                settings?.is_electronic_configured ? 'badge badge-success' : 'badge badge-warning'
                            }
                        >
                            {settings?.is_electronic_configured ? 'Listo para SUNAT' : 'Configuración incompleta'}
                        </span>
                    </div>

                    {errors.message ? (
                        <div className="alert alert-error">
                            <i className="fa-solid fa-circle-exclamation" aria-hidden="true" />
                            <span>{errors.message}</span>
                        </div>
                    ) : null}

                    {saved ? (
                        <div className="alert alert-success">
                            <i className="fa-solid fa-circle-check" aria-hidden="true" />
                            <span>Configuración guardada.</span>
                        </div>
                    ) : null}

                    <section className="card border border-base-300 bg-base-100">
                        <div className="card-body gap-3">
                            <h2 className="card-title">
                                <i className="fa-solid fa-building" aria-hidden="true" /> Datos de la empresa
                            </h2>

                            <label className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    className="toggle toggle-primary"
                                    checked={values.enabled}
                                    onChange={(event) => setValues({ ...values, enabled: event.target.checked })}
                                />
                                <span className="text-sm">Habilitar facturación electrónica</span>
                            </label>

                            <div className="grid gap-3 md:grid-cols-2">
                                <Field label="RUC" error={errors.ruc}>
                                    <input
                                        className="input w-full"
                                        value={values.ruc}
                                        onChange={(event) => setValues({ ...values, ruc: event.target.value })}
                                    />
                                </Field>
                                <Field label="Razón social" error={errors.business_name}>
                                    <input
                                        className="input w-full"
                                        value={values.business_name}
                                        onChange={(event) => setValues({ ...values, business_name: event.target.value })}
                                    />
                                </Field>
                                <Field label="Nombre comercial" error={errors.trade_name}>
                                    <input
                                        className="input w-full"
                                        value={values.trade_name}
                                        onChange={(event) => setValues({ ...values, trade_name: event.target.value })}
                                    />
                                </Field>
                                <Field label="Dirección" error={errors.address}>
                                    <input
                                        className="input w-full"
                                        value={values.address}
                                        onChange={(event) => setValues({ ...values, address: event.target.value })}
                                    />
                                </Field>
                                <Field label="Ubigeo" error={errors.ubigeo}>
                                    <input
                                        className="input w-full"
                                        value={values.ubigeo}
                                        onChange={(event) => setValues({ ...values, ubigeo: event.target.value })}
                                    />
                                </Field>
                                <Field label="Correo" error={errors.email}>
                                    <input
                                        className="input w-full"
                                        value={values.email}
                                        onChange={(event) => setValues({ ...values, email: event.target.value })}
                                    />
                                </Field>
                                <Field label="Teléfono" error={errors.phone}>
                                    <input
                                        className="input w-full"
                                        value={values.phone}
                                        onChange={(event) => setValues({ ...values, phone: event.target.value })}
                                    />
                                </Field>
                            </div>
                        </div>
                    </section>

                    <section className="card border border-base-300 bg-base-100">
                        <div className="card-body gap-3">
                            <h2 className="card-title">
                                <i className="fa-solid fa-key" aria-hidden="true" /> Clave SOL
                            </h2>

                            <div className="grid gap-3 md:grid-cols-2">
                                <Field label="Usuario SOL" error={errors.sol_user}>
                                    <input
                                        className="input w-full"
                                        value={values.sol_user}
                                        onChange={(event) => setValues({ ...values, sol_user: event.target.value })}
                                    />
                                </Field>
                                <Field label="Clave SOL" error={errors.sol_password} hint="Déjala vacía para conservarla.">
                                    <input
                                        type="password"
                                        className="input w-full"
                                        value={values.sol_password}
                                        onChange={(event) => setValues({ ...values, sol_password: event.target.value })}
                                    />
                                </Field>
                            </div>
                        </div>
                    </section>

                    <section className="card border border-base-300 bg-base-100">
                        <div className="card-body gap-3">
                            <h2 className="card-title">
                                <i className="fa-solid fa-file-invoice" aria-hidden="true" /> Comprobantes
                            </h2>

                            <div className="grid gap-3 md:grid-cols-2">
                                <Field label="Modo" error={errors.mode}>
                                    <select
                                        className="select w-full"
                                        value={values.mode}
                                        onChange={(event) =>
                                            setValues({ ...values, mode: event.target.value as BillingMode })
                                        }
                                    >
                                        {billingModeOptions.map((option) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                </Field>
                                <Field label="Tasa IGV" error={errors.igv_rate} hint="0.18 equivale a 18%.">
                                    <input
                                        type="number"
                                        min="0"
                                        max="1"
                                        step="0.01"
                                        className="input w-full"
                                        value={values.igv_rate}
                                        onChange={(event) =>
                                            setValues({ ...values, igv_rate: Number(event.target.value) })
                                        }
                                    />
                                </Field>
                                <Field label="Serie boletas" error={errors.boleta_series}>
                                    <input
                                        className="input w-full"
                                        value={values.boleta_series}
                                        onChange={(event) =>
                                            setValues({ ...values, boleta_series: event.target.value })
                                        }
                                    />
                                </Field>
                                <Field label="Serie facturas" error={errors.factura_series}>
                                    <input
                                        className="input w-full"
                                        value={values.factura_series}
                                        onChange={(event) =>
                                            setValues({ ...values, factura_series: event.target.value })
                                        }
                                    />
                                </Field>
                            </div>

                            <Field label="Leyenda" error={errors.legend}>
                                <input
                                    className="input w-full"
                                    value={values.legend}
                                    onChange={(event) => setValues({ ...values, legend: event.target.value })}
                                />
                            </Field>
                        </div>
                    </section>

                    <section className="card border border-base-300 bg-base-100">
                        <div className="card-body gap-3">
                            <h2 className="card-title">
                                <i className="fa-solid fa-certificate" aria-hidden="true" /> Certificado digital
                            </h2>
                            <p className="text-sm opacity-70">
                                {settings?.has_certificate
                                    ? 'Ya hay un certificado cargado. Sube uno nuevo para reemplazarlo.'
                                    : 'Sube el certificado digital (PEM, P12/PFX) para firmar comprobantes.'}
                            </p>

                            {certErrors.message ? (
                                <div className="alert alert-error">
                                    <i className="fa-solid fa-circle-exclamation" aria-hidden="true" />
                                    <span>{certErrors.message}</span>
                                </div>
                            ) : null}

                            <Field label="Archivo de certificado" error={certErrors.certificate}>
                                <input
                                    ref={fileInput}
                                    type="file"
                                    className="file-input w-full"
                                    accept=".pem,.p12,.pfx,.crt,.cer,.txt"
                                    onChange={(event) => setFile(event.target.files?.[0] ?? null)}
                                />
                            </Field>

                            <Field label="Contraseña del certificado (opcional)" error={certErrors.certificate_password}>
                                <input
                                    type="password"
                                    className="input w-full"
                                    value={certificatePassword}
                                    onChange={(event) => setCertificatePassword(event.target.value)}
                                />
                            </Field>

                            <div className="card-actions justify-end">
                                <button
                                    type="button"
                                    className="btn btn-outline"
                                    disabled={uploading}
                                    onClick={uploadCertificate}
                                >
                                    {uploading ? <span className="loading loading-spinner loading-sm" /> : null}
                                    Subir certificado
                                </button>
                            </div>
                        </div>
                    </section>

                    <div className="flex justify-end">
                        <button type="button" className="btn btn-primary" disabled={saving} onClick={save}>
                            {saving ? <span className="loading loading-spinner" /> : null}
                            Guardar configuración
                        </button>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}

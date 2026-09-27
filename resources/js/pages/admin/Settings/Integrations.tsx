import { Head } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '@/components/layout/AdminLayout';
import { Field } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { http } from '@/lib/axios';
import { validationErrors } from '@/lib/http';
import type { PlatformSetting } from '@/types';

interface Props {
    settings: PlatformSetting;
}

interface FormValues {
    cloudinary_enabled: boolean;
    cloudinary_cloud_name: string;
    cloudinary_api_key: string;
    cloudinary_api_secret: string;
    cloudinary_folder: string;
    max_images_per_product: number;
    image_max_width: number;
    webp_quality: number;
}

interface PingResult {
    ok: boolean;
    message: string;
}

function toValues(settings: PlatformSetting): FormValues {
    return {
        cloudinary_enabled: settings.cloudinary_enabled,
        cloudinary_cloud_name: settings.cloudinary_cloud_name ?? '',
        cloudinary_api_key: settings.cloudinary_api_key ?? '',
        cloudinary_api_secret: '',
        cloudinary_folder: settings.cloudinary_folder ?? '',
        max_images_per_product: settings.max_images_per_product,
        image_max_width: settings.image_max_width,
        webp_quality: settings.webp_quality,
    };
}

function errorMessage(error: unknown): string | null {
    return (error as { response?: { data?: { message?: string } } })?.response?.data?.message ?? null;
}

function errorsFor(error: unknown): Record<string, string> {
    const fieldErrors = validationErrors(error);
    const message = errorMessage(error);

    return message && Object.keys(fieldErrors).length === 0 ? { message } : fieldErrors;
}

export default function Integrations({ settings: initialSettings }: Props) {
    const [settings, setSettings] = useState<PlatformSetting>(initialSettings);
    const [values, setValues] = useState<FormValues>(() => toValues(initialSettings));
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [saving, setSaving] = useState(false);
    const [saved, setSaved] = useState(false);

    const [testing, setTesting] = useState(false);
    const [ping, setPing] = useState<PingResult | null>(null);

    const save = async () => {
        setSaving(true);
        setErrors({});
        setSaved(false);

        try {
            const { cloudinary_api_secret, ...rest } = values;
            const payload = cloudinary_api_secret ? { ...rest, cloudinary_api_secret } : rest;

            const response = await http.put<{ data: PlatformSetting }>(
                '/api/v1/admin/settings/integrations',
                payload,
            );

            setSettings(response.data.data);
            setValues(toValues(response.data.data));
            setSaved(true);
        } catch (error) {
            setErrors(errorsFor(error));
        } finally {
            setSaving(false);
        }
    };

    const testConnection = async () => {
        setTesting(true);
        setPing(null);

        try {
            const response = await http.post<{ data: PingResult }>('/api/v1/admin/settings/integrations/test');

            setPing(response.data.data);
        } catch (error) {
            setPing({ ok: false, message: errorMessage(error) ?? 'No se pudo probar la conexión.' });
        } finally {
            setTesting(false);
        }
    };

    return (
        <AdminLayout title="Integraciones">
            <Head title="Integraciones" />
            <PageHeader
                title="Integraciones"
                description="Servicios compartidos por todos los clientes de la plataforma."
            />

            <div className="mt-4 space-y-6">
                <div className="alert alert-info">
                    <i className="fa-solid fa-circle-info" aria-hidden="true" />
                    <span>Estas credenciales se aplican a todos los clientes.</span>
                </div>

                <div className="flex flex-wrap gap-2">
                    <span className={settings.cloudinary_enabled ? 'badge badge-success' : 'badge badge-ghost'}>
                        {settings.cloudinary_enabled ? 'Cloudinary activo' : 'Cloudinary inactivo'}
                    </span>
                    <span className={settings.is_configured ? 'badge badge-success' : 'badge badge-warning'}>
                        {settings.is_configured ? 'Configuración completa' : 'Configuración incompleta'}
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
                            <i className="fa-solid fa-cloud" aria-hidden="true" /> Cloudinary
                        </h2>

                        <label className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                className="toggle toggle-primary"
                                checked={values.cloudinary_enabled}
                                onChange={(event) =>
                                    setValues({ ...values, cloudinary_enabled: event.target.checked })
                                }
                            />
                            <span className="text-sm">Activo</span>
                        </label>

                        <div className="grid gap-3 md:grid-cols-2">
                            <Field label="Cloud name" error={errors.cloudinary_cloud_name}>
                                <input
                                    className="input w-full"
                                    value={values.cloudinary_cloud_name}
                                    onChange={(event) =>
                                        setValues({ ...values, cloudinary_cloud_name: event.target.value })
                                    }
                                />
                            </Field>
                            <Field label="API key" error={errors.cloudinary_api_key}>
                                <input
                                    className="input w-full"
                                    value={values.cloudinary_api_key}
                                    onChange={(event) =>
                                        setValues({ ...values, cloudinary_api_key: event.target.value })
                                    }
                                />
                            </Field>
                            <Field label="API secret" error={errors.cloudinary_api_secret}>
                                <input
                                    type="password"
                                    className="input w-full"
                                    placeholder="•••• deja vacío para no cambiar"
                                    value={values.cloudinary_api_secret}
                                    onChange={(event) =>
                                        setValues({ ...values, cloudinary_api_secret: event.target.value })
                                    }
                                />
                                {settings.has_secret ? (
                                    <span className="badge badge-success mt-1">Secret guardado</span>
                                ) : null}
                            </Field>
                            <Field label="Carpeta" error={errors.cloudinary_folder}>
                                <input
                                    className="input w-full"
                                    value={values.cloudinary_folder}
                                    onChange={(event) =>
                                        setValues({ ...values, cloudinary_folder: event.target.value })
                                    }
                                />
                            </Field>
                        </div>
                    </div>
                </section>

                <section className="card border border-base-300 bg-base-100">
                    <div className="card-body gap-3">
                        <h2 className="card-title">
                            <i className="fa-solid fa-image" aria-hidden="true" /> Optimización de imágenes
                        </h2>

                        <div className="grid gap-3 md:grid-cols-3">
                            <Field
                                label="Máximo de imágenes por producto"
                                error={errors.max_images_per_product}
                                hint="Recomendado 1."
                            >
                                <input
                                    type="number"
                                    min={1}
                                    max={10}
                                    className="input w-full"
                                    value={values.max_images_per_product}
                                    onChange={(event) =>
                                        setValues({
                                            ...values,
                                            max_images_per_product: Number(event.target.value),
                                        })
                                    }
                                />
                            </Field>
                            <Field label="Ancho máximo (px)" error={errors.image_max_width} hint="Entre 200 y 2000.">
                                <input
                                    type="number"
                                    min={200}
                                    max={2000}
                                    className="input w-full"
                                    value={values.image_max_width}
                                    onChange={(event) =>
                                        setValues({ ...values, image_max_width: Number(event.target.value) })
                                    }
                                />
                            </Field>
                            <Field label="Calidad WebP" error={errors.webp_quality} hint="Entre 30 y 100.">
                                <input
                                    type="number"
                                    min={30}
                                    max={100}
                                    className="input w-full"
                                    value={values.webp_quality}
                                    onChange={(event) =>
                                        setValues({ ...values, webp_quality: Number(event.target.value) })
                                    }
                                />
                            </Field>
                        </div>
                    </div>
                </section>

                {ping ? (
                    <div className={ping.ok ? 'alert alert-success' : 'alert alert-error'}>
                        <i
                            className={`fa-solid ${ping.ok ? 'fa-circle-check' : 'fa-circle-exclamation'}`}
                            aria-hidden="true"
                        />
                        <span>{ping.message}</span>
                    </div>
                ) : null}

                <div className="flex justify-end gap-2">
                    <button
                        type="button"
                        className="btn btn-outline"
                        disabled={testing}
                        onClick={testConnection}
                    >
                        {testing ? <span className="loading loading-spinner loading-sm" /> : null}
                        Probar conexión
                    </button>
                    <button type="button" className="btn btn-primary" disabled={saving} onClick={save}>
                        {saving ? <span className="loading loading-spinner loading-sm" /> : null}
                        Guardar
                    </button>
                </div>
            </div>
        </AdminLayout>
    );
}

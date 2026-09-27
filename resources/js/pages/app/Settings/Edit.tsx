import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AppLayout from '@/components/layout/AppLayout';
import { Field } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { useShared } from '@/hooks/use-shared';

export default function SettingsEdit() {
    const { auth, tenant } = useShared();
    const canManage = auth.permissions.includes('settings.manage');

    const { data, setData, put, processing, errors } = useForm({
        name: tenant?.name ?? '',
        locale: tenant?.locale ?? 'es',
        settings: {
            timezone: String(tenant?.settings?.timezone ?? ''),
            currency: String(tenant?.settings?.currency ?? ''),
        },
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        put('/app/settings');
    };

    return (
        <AppLayout title="Configuración">
            <Head title="Configuración" />
            <PageHeader title="Configuración del espacio" description="Nombre, idioma y preferencias del cliente." />

            <form className="mt-6 max-w-xl space-y-1" onSubmit={submit}>
                <Field label="Nombre del espacio" error={errors.name}>
                    <input
                        className="input w-full"
                        value={data.name}
                        onChange={(event) => setData('name', event.target.value)}
                    />
                </Field>

                <Field label="Idioma" error={errors.locale}>
                    <select
                        className="select w-full"
                        value={data.locale}
                        onChange={(event) => setData('locale', event.target.value)}
                    >
                        <option value="es">Español</option>
                        <option value="en">Inglés</option>
                    </select>
                </Field>

                <div className="grid gap-3 sm:grid-cols-2">
                    <Field label="Zona horaria">
                        <input
                            className="input w-full"
                            value={data.settings.timezone}
                            onChange={(event) => setData('settings', { ...data.settings, timezone: event.target.value })}
                            placeholder="America/Lima"
                        />
                    </Field>
                    <Field label="Moneda">
                        <input
                            className="input w-full"
                            value={data.settings.currency}
                            onChange={(event) => setData('settings', { ...data.settings, currency: event.target.value })}
                            placeholder="PEN"
                        />
                    </Field>
                </div>

                <div className="pt-2">
                    <button type="submit" className="btn btn-primary" disabled={processing || !canManage}>
                        {processing ? <span className="loading loading-spinner loading-sm" /> : 'Guardar cambios'}
                    </button>
                    {!canManage ? (
                        <span className="ml-3 text-xs opacity-60">No tienes permisos para editar la configuración.</span>
                    ) : null}
                </div>
            </form>
        </AppLayout>
    );
}

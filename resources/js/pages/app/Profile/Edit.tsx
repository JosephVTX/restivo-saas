import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AppLayout from '@/components/layout/AppLayout';
import { Field } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import type { User } from '@/types';

interface Props {
    profile: User;
}

export default function ProfileEdit({ profile }: Props) {
    const { data, setData, patch, processing, errors } = useForm({
        name: profile.name,
        email: profile.email,
        locale: profile.locale,
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        patch('/app/profile');
    };

    return (
        <AppLayout title="Perfil">
            <Head title="Perfil" />
            <PageHeader title="Tu perfil" description="Actualiza tus datos y tu contraseña." />

            <form className="mt-6 max-w-xl space-y-1" onSubmit={submit}>
                <Field label="Nombre" error={errors.name}>
                    <input
                        className="input w-full"
                        value={data.name}
                        onChange={(event) => setData('name', event.target.value)}
                    />
                </Field>

                <Field label="Correo electrónico" error={errors.email}>
                    <input
                        type="email"
                        className="input w-full"
                        value={data.email}
                        onChange={(event) => setData('email', event.target.value)}
                    />
                </Field>

                <div className="divider my-2 text-xs uppercase tracking-wide opacity-60">Cambiar contraseña</div>

                <Field label="Contraseña actual" error={errors.current_password}>
                    <input
                        type="password"
                        className="input w-full"
                        value={data.current_password}
                        onChange={(event) => setData('current_password', event.target.value)}
                        autoComplete="current-password"
                    />
                </Field>

                <div className="grid gap-3 sm:grid-cols-2">
                    <Field label="Nueva contraseña" error={errors.password}>
                        <input
                            type="password"
                            className="input w-full"
                            value={data.password}
                            onChange={(event) => setData('password', event.target.value)}
                            autoComplete="new-password"
                        />
                    </Field>
                    <Field label="Confirmar contraseña">
                        <input
                            type="password"
                            className="input w-full"
                            value={data.password_confirmation}
                            onChange={(event) => setData('password_confirmation', event.target.value)}
                            autoComplete="new-password"
                        />
                    </Field>
                </div>

                <div className="pt-2">
                    <button type="submit" className="btn btn-primary" disabled={processing}>
                        {processing ? <span className="loading loading-spinner loading-sm" /> : 'Guardar cambios'}
                    </button>
                </div>
            </form>
        </AppLayout>
    );
}

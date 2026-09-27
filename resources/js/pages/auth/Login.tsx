import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AuthLayout from '@/components/layout/AuthLayout';
import { Field } from '@/components/ui/Field';

export default function Login() {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post('/login', { onFinish: () => reset('password') });
    };

    return (
        <AuthLayout title="Bienvenido de vuelta" subtitle="Ingresa para gestionar tu restaurante.">
            <Head title="Iniciar sesión" />
            <form className="space-y-2" onSubmit={submit}>
                <Field label="Correo electrónico" error={errors.email}>
                    <input
                        type="email"
                        className="input w-full"
                        value={data.email}
                        onChange={(event) => setData('email', event.target.value)}
                        autoComplete="email"
                        autoFocus
                        required
                    />
                </Field>

                <Field label="Contraseña" error={errors.password}>
                    <input
                        type="password"
                        className="input w-full"
                        value={data.password}
                        onChange={(event) => setData('password', event.target.value)}
                        autoComplete="current-password"
                        required
                    />
                </Field>

                <label className="flex cursor-pointer items-center gap-2 pt-1 text-sm">
                    <input
                        type="checkbox"
                        className="checkbox checkbox-sm"
                        checked={data.remember}
                        onChange={(event) => setData('remember', event.target.checked)}
                    />
                    Recordarme
                </label>

                <button type="submit" className="btn btn-primary mt-2 w-full" disabled={processing}>
                    {processing ? <span className="loading loading-spinner loading-sm" /> : 'Entrar'}
                </button>

                <p className="pt-2 text-center text-sm opacity-70">
                    El acceso es otorgado por el administrador de la plataforma.
                </p>
            </form>
        </AuthLayout>
    );
}

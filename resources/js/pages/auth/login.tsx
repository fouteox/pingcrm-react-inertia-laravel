import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { InputField } from '@/components/resource-form-fields';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import login from '@/wayfinder/routes/login';

type LoginForm = {
    email: string;
    password: string;
    remember: boolean;
};

interface LoginProps {
    status?: string;
}

export default function Login({ status }: LoginProps) {
    const { t } = useTranslation();

    const { data, setData, submit, processing, errors, reset } = useForm<Required<LoginForm>>({
        email: 'johndoe@example.com',
        password: 'secret',
        remember: false,
    });

    const onSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        submit(login.store(), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <>
            <Head title={t('Login')} />

            <form className="flex flex-col gap-6" onSubmit={onSubmit}>
                <div className="grid gap-6">
                    <InputField
                        label={t('Email')}
                        error={errors.email}
                        id="email"
                        type="email"
                        required
                        autoComplete="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                    />

                    <InputField
                        label={t('Password')}
                        error={errors.password}
                        id="password"
                        type="password"
                        required
                        autoComplete="current-password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                    />

                    <div className="flex items-center space-x-3">
                        <Checkbox
                            id="remember"
                            name="remember"
                            checked={data.remember}
                            onCheckedChange={(checked) => setData('remember', Boolean(checked))}
                        />
                        <Label htmlFor="remember">{t('Remember me')}</Label>
                    </div>

                    <Button type="submit" className="mt-4 w-full" disabled={processing}>
                        {processing && <LoaderCircle className="size-4 animate-spin" />}
                        {t('Log in')}
                    </Button>
                </div>
            </form>

            {status && <div className="mb-4 text-center text-sm font-medium text-green-600">{status}</div>}
        </>
    );
}

Login.layout = () => ({
    authTitle: 'Log in to your account',
    authDescription: 'Enter your email and password below to log in',
});

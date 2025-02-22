import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import LoadingButton from '@/Components/LoadingButton';
import Logo from '@/Components/Logo';
import TextInput from '@/Components/TextInput';
import { Head, useForm } from '@inertiajs/react';
import React, { ChangeEvent } from 'react';
import { useTranslation } from 'react-i18next';

interface LoginForm {
    email: string;
    password: string;
    remember: boolean;
    [key: string]: string | boolean;
}

export default function Login() {
    const { t } = useTranslation();

    const { data, setData, post, processing, errors } = useForm<LoginForm>({
        email: 'johndoe@example.com',
        password: 'secret',
        remember: false,
    });

    const onHandleChange = (event: ChangeEvent<HTMLInputElement>) => {
        setData(
            event.target.name as keyof LoginForm,
            event.target.type === 'checkbox'
                ? event.target.checked
                : event.target.value,
        );
    };

    const submit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        post(route('login'));
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-indigo-800 p-6">
            <Head title="Login" />

            <div className="w-full max-w-md">
                <Logo
                    className="mx-auto block w-full max-w-xs fill-current text-white"
                    height={50}
                />

                <form
                    onSubmit={submit}
                    className="mt-8 overflow-hidden rounded-lg bg-white shadow-xl"
                >
                    <div className="px-10 py-12">
                        <h1 className="text-center text-3xl font-bold">
                            {t('Welcome Back!')}
                        </h1>

                        <div className="mx-auto mt-6 w-24 border-b-2" />

                        <div className="mt-6">
                            <InputLabel forInput="email" value={t('Email')} />

                            <TextInput
                                type="email"
                                name="email"
                                value={data.email}
                                autoComplete="username"
                                isFocused={true}
                                handleChange={onHandleChange}
                            />

                            <InputError message={errors.email} />
                        </div>

                        <div className="mt-4">
                            <InputLabel
                                forInput="password"
                                value={t('Password')}
                            />

                            <TextInput
                                type="password"
                                name="password"
                                value={data.password}
                                autoComplete="current-password"
                                handleChange={onHandleChange}
                            />

                            <InputError message={errors.password} />
                        </div>

                        <label className="mt-6 flex items-center select-none">
                            <Checkbox
                                name="remember"
                                value={data.remember}
                                handleChange={onHandleChange}
                            />
                            <span className="ml-2 text-sm text-gray-600">
                                {t('Remember me')}
                            </span>
                        </label>
                    </div>

                    <div className="flex items-center justify-end border-t border-gray-200 bg-gray-100 px-10 py-4">
                        <LoadingButton
                            className="ml-auto"
                            processing={processing}
                        >
                            {t('Log in')}
                        </LoadingButton>
                    </div>
                </form>
            </div>
        </div>
    );
}

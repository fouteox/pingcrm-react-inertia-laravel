import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';
import { useTranslation } from 'react-i18next';
import AppLogoIcon from '@/components/app-logo-icon';
import { dashboard } from '@/wayfinder/routes';

export default function AuthSimpleLayout({
    children,
    authTitle = '',
    authDescription = '',
}: PropsWithChildren<{ authTitle?: string; authDescription?: string }>) {
    const { t } = useTranslation();

    return (
        <div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-4">
                        <Link href={dashboard()} className="flex flex-col items-center gap-2 font-medium">
                            <div className="mb-1 flex items-center justify-center rounded-md">
                                <AppLogoIcon className="h-20 w-52 fill-current text-[var(--foreground)] dark:text-white" />
                            </div>
                            <span className="sr-only">{t(authTitle)}</span>
                        </Link>

                        <div className="space-y-2 text-center">
                            <h1 className="text-xl font-medium">{t(authTitle)}</h1>
                            <p className="text-center text-sm text-muted-foreground">{t(authDescription)}</p>
                        </div>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}

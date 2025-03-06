import AppLogoIcon from '@/components/app-logo-icon';
import { usePageContext } from '@/contexts/page-context';
import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function AuthSimpleLayout({ children }: PropsWithChildren) {
    const { authTitle, authDescription } = usePageContext();

    return (
        <div className="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-4">
                        <Link href={route('dashboard')} className="flex flex-col items-center gap-2 font-medium">
                            <div className="mb-1 flex items-center justify-center rounded-md">
                                <AppLogoIcon className="h-20 w-52 fill-current text-[var(--foreground)] dark:text-white" />
                            </div>
                            <span className="sr-only">{authTitle}</span>
                        </Link>

                        <div className="space-y-2 text-center">
                            <h1 className="text-xl font-medium">{authTitle}</h1>
                            <p className="text-muted-foreground text-center text-sm">{authDescription}</p>
                        </div>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}

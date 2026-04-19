import { Link, router } from '@inertiajs/react';
import { LogOut, Settings } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { MobileAwareLink } from '@/components/mobile-aware-link';
import { DropdownMenuGroup, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user-info';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import type { UserResource } from '@/types/resources';
import { logout } from '@/wayfinder/routes';
import users from '@/wayfinder/routes/users';

interface UserMenuContentProps {
    user: UserResource;
}

export function UserMenuContent({ user }: UserMenuContentProps) {
    const { t } = useTranslation();
    const cleanup = useMobileNavigation();

    const handleLogout = () => {
        cleanup();
        router.flushAll();
    };

    return (
        <>
            <DropdownMenuLabel className="p-0 font-normal">
                <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                    <UserInfo user={user} showEmail={true} />
                </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
                <DropdownMenuItem
                    render={<MobileAwareLink className="block w-full" href={users.edit(user.id)} as="button" prefetch onClick={cleanup} />}
                >
                    <Settings className="mr-2" />
                    {t('My Profile')}
                </DropdownMenuItem>
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
            <DropdownMenuItem render={<Link className="block w-full" href={logout()} as="button" onClick={handleLogout} />}>
                <LogOut className="mr-2" />
                {t('Logout')}
            </DropdownMenuItem>
        </>
    );
}

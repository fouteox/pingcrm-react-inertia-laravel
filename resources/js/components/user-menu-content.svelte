<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import LogOut from '@lucide/svelte/icons/log-out';
    import Settings from '@lucide/svelte/icons/settings';
    import MobileAwareLink from '@/components/mobile-aware-link.svelte';
    import { DropdownMenuGroup, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
    import UserInfo from '@/components/user-info.svelte';
    import { cleanupMobileNavigation } from '@/hooks/use-mobile-navigation';
    import { useTranslation } from '@/hooks/use-translation.svelte';
    import { cn } from '@/lib/utils';
    import type { UserResource } from '@/types/resources';
    import { logout } from '@/wayfinder/routes';
    import users from '@/wayfinder/routes/users';

    let { user }: { user: UserResource } = $props();

    const { t } = useTranslation();

    const handleLogout = () => {
        cleanupMobileNavigation();
        router.flushAll();
    };
</script>

<DropdownMenuGroup>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo {user} showEmail={true} />
        </div>
    </DropdownMenuLabel>
</DropdownMenuGroup>
<DropdownMenuSeparator />
<DropdownMenuGroup>
    <DropdownMenuItem>
        {#snippet child({ props })}
            <MobileAwareLink {...props} class={cn(props.class as string, 'block w-full')} href={users.edit(user.id)} prefetch onclick={cleanupMobileNavigation}>
                <Settings class="mr-2" />
                {t('My Profile')}
            </MobileAwareLink>
        {/snippet}
    </DropdownMenuItem>
</DropdownMenuGroup>
<DropdownMenuSeparator />
<DropdownMenuItem>
    {#snippet child({ props })}
        <Link {...props} class={cn(props.class as string, 'block w-full')} href={logout()} as="button" onclick={handleLogout}>
            <LogOut class="mr-2" />
            {t('Logout')}
        </Link>
    {/snippet}
</DropdownMenuItem>

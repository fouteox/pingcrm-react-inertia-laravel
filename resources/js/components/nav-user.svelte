<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import ChevronsUpDown from '@lucide/svelte/icons/chevrons-up-down';
    import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
    import { SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar } from '@/components/ui/sidebar';
    import UserInfo from '@/components/user-info.svelte';
    import UserMenuContent from '@/components/user-menu-content.svelte';

    const sidebar = useSidebar();
    const user = $derived(page.props.auth.user);
</script>

{#if user}
    <SidebarMenu>
        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger>
                    {#snippet child({ props })}
                        <SidebarMenuButton {...props} size="lg" class="group text-sidebar-accent-foreground data-[state=open]:bg-sidebar-accent">
                            <UserInfo {user} />
                            <ChevronsUpDown class="ml-auto size-4" />
                        </SidebarMenuButton>
                    {/snippet}
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    class="w-(--bits-dropdown-menu-anchor-width) min-w-56 rounded-lg"
                    align="end"
                    side={sidebar.isMobile ? 'bottom' : sidebar.state === 'collapsed' ? 'left' : 'bottom'}
                >
                    <UserMenuContent {user} />
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>
{/if}

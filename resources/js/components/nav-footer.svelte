<script lang="ts">
    import ChevronsUpDown from '@lucide/svelte/icons/chevrons-up-down';
    import Globe from '@lucide/svelte/icons/globe';
    import Monitor from '@lucide/svelte/icons/monitor';
    import Moon from '@lucide/svelte/icons/moon';
    import Sun from '@lucide/svelte/icons/sun';
    import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
    import { SidebarGroup, SidebarGroupContent, SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar } from '@/components/ui/sidebar';
    import { appearance, type Appearance } from '@/hooks/use-appearance.svelte';
    import { useTranslation } from '@/hooks/use-translation.svelte';
    import { cn } from '@/lib/utils';

    const LANGUAGES = [
        { code: 'fr', name: 'Français', flag: 'fr' },
        { code: 'en', name: 'English', flag: 'gb' },
    ] as const;

    const translator = useTranslation();
    const { t } = translator;
    const sidebar = useSidebar();

    // `translator.language` is a rune-backed getter, so this updates after changeLanguage().
    const currentLang = $derived(LANGUAGES.find((lang) => lang.code === translator.language) ?? LANGUAGES[0]);

    const tabs = $derived([
        { value: 'light' as Appearance, icon: Sun, label: t('Light') },
        { value: 'dark' as Appearance, icon: Moon, label: t('Dark') },
        { value: 'system' as Appearance, icon: Monitor, label: t('System') },
    ]);
</script>

<SidebarGroup class="mt-auto group-data-[collapsible=icon]:p-0">
    <SidebarGroupContent>
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger>
                        {#snippet child({ props })}
                            <SidebarMenuButton {...props} class="text-neutral-600 hover:text-neutral-800 dark:text-neutral-300 dark:hover:text-neutral-100">
                                <Globe class="size-5" />
                                <span>{currentLang.name}</span>
                                <ChevronsUpDown class="ml-auto size-4" />
                            </SidebarMenuButton>
                        {/snippet}
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        class="w-(--bits-dropdown-menu-anchor-width) min-w-56"
                        align="center"
                        side={sidebar.isMobile ? 'top' : sidebar.state === 'collapsed' ? 'left' : 'top'}
                    >
                        {#each LANGUAGES as language (language.code)}
                            <DropdownMenuItem onSelect={() => void translator.changeLanguage(language.code)}>
                                <span class="fib fi-{language.flag} mr-2 size-5 rounded-sm" role="img" aria-label="Drapeau {language.name}"></span>
                                <span>{language.name}</span>
                            </DropdownMenuItem>
                        {/each}
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>

            <SidebarMenuItem>
                {#if sidebar.state === 'collapsed' && !sidebar.isMobile}
                    <DropdownMenu>
                        <DropdownMenuTrigger>
                            {#snippet child({ props })}
                                <SidebarMenuButton
                                    {...props}
                                    aria-label={t('Appearance')}
                                    class="text-neutral-600 hover:text-neutral-800 dark:text-neutral-300 dark:hover:text-neutral-100"
                                >
                                    {#if appearance.current === 'light'}
                                        <Sun class="size-5" />
                                    {:else if appearance.current === 'dark'}
                                        <Moon class="size-5" />
                                    {:else}
                                        <Monitor class="size-5" />
                                    {/if}
                                </SidebarMenuButton>
                            {/snippet}
                        </DropdownMenuTrigger>
                        <DropdownMenuContent class="min-w-32" align="center" side={sidebar.isMobile ? 'top' : 'right'}>
                            {#each tabs as { value, icon: Icon, label } (value)}
                                <DropdownMenuItem onSelect={() => appearance.update(value)}>
                                    <Icon class="mr-2 size-4" />
                                    <span>{label}</span>
                                </DropdownMenuItem>
                            {/each}
                        </DropdownMenuContent>
                    </DropdownMenu>
                {:else}
                    <div class="inline-flex w-full gap-1 rounded-md bg-neutral-100 p-1 dark:bg-neutral-800">
                        {#each tabs as { value, icon: Icon, label } (value)}
                            <button
                                type="button"
                                aria-label={label}
                                aria-pressed={appearance.current === value}
                                onclick={() => appearance.update(value)}
                                class={cn(
                                    'flex flex-1 items-center justify-center rounded-md px-3 py-1 transition-colors',
                                    appearance.current === value
                                        ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                                        : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
                                )}
                            >
                                <Icon class="size-4" />
                            </button>
                        {/each}
                    </div>
                {/if}
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroupContent>
</SidebarGroup>

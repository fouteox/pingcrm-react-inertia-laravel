import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { SidebarGroup, SidebarGroupContent, SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar } from '@/components/ui/sidebar';
import { useAppearance, type Appearance } from '@/hooks/use-appearance';
import { useIsMobile } from '@/hooks/use-mobile';
import { cn } from '@/lib/utils';
import { ChevronsUpDown, Globe, Monitor, Moon, Sun } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

const LANGUAGES = [
    { code: 'fr', name: 'Français', flag: 'fr' },
    { code: 'en', name: 'English', flag: 'gb' },
] as const;

type Language = (typeof LANGUAGES)[number];

export function NavFooter() {
    const { i18n } = useTranslation();
    const { appearance, updateAppearance } = useAppearance();
    const { state } = useSidebar();
    const isMobile = useIsMobile();

    const [currentLang, setCurrentLang] = useState<Language>(LANGUAGES.find((lang) => lang.code === i18n.language) ?? LANGUAGES[0]);

    const handleLanguageChange = (language: Language) => {
        void i18n.changeLanguage(language.code);
        setCurrentLang(language);
    };

    const tabs = [
        { value: 'light' as Appearance, icon: Sun },
        { value: 'dark' as Appearance, icon: Moon },
        { value: 'system' as Appearance, icon: Monitor },
    ];

    return (
        <SidebarGroup className="mt-auto group-data-[collapsible=icon]:p-0">
            <SidebarGroupContent>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <SidebarMenuButton className="text-neutral-600 hover:text-neutral-800 dark:text-neutral-300 dark:hover:text-neutral-100">
                                    <Globe className="h-5 w-5" />
                                    <span>{currentLang.name}</span>
                                    <ChevronsUpDown className="ml-auto size-4" />
                                </SidebarMenuButton>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent
                                className="w-(--radix-dropdown-menu-trigger-width) min-w-56"
                                align="center"
                                side={isMobile ? 'top' : state === 'collapsed' ? 'left' : 'top'}
                            >
                                {LANGUAGES.map((language) => (
                                    <DropdownMenuItem key={language.code} onClick={() => handleLanguageChange(language)}>
                                        <span
                                            className={`fib fi-${language.flag} mr-2 size-5 rounded-sm`}
                                            role="img"
                                            aria-label={`Drapeau ${language.name}`}
                                        />
                                        <span>{language.name}</span>
                                    </DropdownMenuItem>
                                ))}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </SidebarMenuItem>

                    <SidebarMenuItem>
                        <div className="inline-flex w-full gap-1 rounded-md bg-neutral-100 p-1 dark:bg-neutral-800">
                            {tabs.map(({ value, icon: Icon }) => (
                                <button
                                    key={value}
                                    onClick={() => updateAppearance(value)}
                                    className={cn(
                                        'flex flex-1 items-center justify-center rounded-md px-3 py-1 transition-colors',
                                        appearance === value
                                            ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                                            : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
                                    )}
                                >
                                    <Icon className="h-4 w-4" />
                                </button>
                            ))}
                        </div>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroupContent>
        </SidebarGroup>
    );
}

import Icon from '@/Components/Icon';
import { PageProps } from '@/types';
import { Button } from '@headlessui/react';
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

const LANGUAGES = [
    { code: 'fr', name: 'Français', flag: 'fr' },
    { code: 'en', name: 'English', flag: 'gb' },
] as const;

type Language = (typeof LANGUAGES)[number];

export default function BottomHeader() {
    const { t, i18n } = useTranslation();

    const [currentLang, setCurrentLang] = useState<Language>(
        LANGUAGES.find((lang) => lang.code === i18n.language) ?? LANGUAGES[0],
    );

    const handleLanguageChange = (language: Language) => {
        void i18n.changeLanguage(language.code);
        setCurrentLang(language);
    };

    const { auth } = usePage<PageProps>().props;
    const [menuOpened, setMenuOpened] = useState(false);
    const [menuFlagOpened, setMenuFlagOpened] = useState(false);

    return (
        <div className="d:text-md flex w-full items-center justify-between border-b bg-white p-4 text-sm md:px-12 md:py-0">
            <div className="mt-1 mr-4">{auth.user.account.name}</div>
            <div className="flex justify-between gap-4">
                <div className="relative">
                    <div
                        className="group flex cursor-pointer items-center select-none"
                        onClick={() => setMenuFlagOpened(true)}
                    >
                        <span
                            className={`fib fi-${currentLang.flag} h-[24px] w-[24px] rounded-sm md:h-[20px] md:w-[20px]`}
                            role="img"
                            aria-label={`Drapeau ${currentLang.name}`}
                        />
                    </div>
                    <div className={menuFlagOpened ? '' : 'hidden'}>
                        <div className="absolute top-0 right-0 left-auto z-20 mt-8 rounded-sm bg-white py-2 text-sm whitespace-nowrap shadow-xl">
                            {LANGUAGES.map((language) => (
                                <Button
                                    key={language.code}
                                    onClick={() => {
                                        handleLanguageChange(language);
                                        setMenuFlagOpened(false);
                                    }}
                                    className="flex w-full items-center px-6 py-2 hover:bg-indigo-600 hover:text-white"
                                >
                                    <span
                                        className={`fib size-5 fi-${language.flag} mr-2 rounded-sm`}
                                        role="img"
                                        aria-label={`Drapeau ${language.name}`}
                                    />
                                    <span>{language.name}</span>
                                </Button>
                            ))}
                        </div>
                        <div
                            onClick={() => {
                                setMenuFlagOpened(false);
                            }}
                            className="fixed inset-0 z-10 bg-black opacity-25"
                        ></div>
                    </div>
                </div>
                <div className="relative">
                    <div
                        className="group flex cursor-pointer items-center select-none"
                        onClick={() => setMenuOpened(true)}
                    >
                        <div className="mr-1 whitespace-nowrap text-gray-800 group-hover:text-indigo-600 focus:text-indigo-600">
                            <span>{auth.user.first_name}</span>
                            <span className="ml-1 hidden md:inline">
                                {auth.user.last_name}
                            </span>
                        </div>
                        <Icon
                            className="h-5 w-5 fill-current text-gray-800 group-hover:text-indigo-600 focus:text-indigo-600"
                            name="cheveron-down"
                        />
                    </div>
                    <div className={menuOpened ? '' : 'hidden'}>
                        <div className="absolute top-0 right-0 left-auto z-20 mt-8 rounded-sm bg-white py-2 text-sm whitespace-nowrap shadow-xl">
                            <Link
                                href={route('users.edit', auth.user.id)}
                                className="block px-6 py-2 hover:bg-indigo-600 hover:text-white"
                                onClick={() => setMenuOpened(false)}
                            >
                                {t('My Profile')}
                            </Link>
                            <Link
                                href={route('users.index')}
                                className="block px-6 py-2 hover:bg-indigo-600 hover:text-white"
                                onClick={() => setMenuOpened(false)}
                            >
                                {t('Manage Users')}
                            </Link>
                            <Link
                                as="button"
                                href={route('logout')}
                                className="block w-full px-6 py-2 text-left hover:bg-indigo-600 hover:text-white focus:outline-hidden"
                                method="delete"
                            >
                                {t('Logout')}
                            </Link>
                        </div>
                        <div
                            onClick={() => {
                                setMenuOpened(false);
                            }}
                            className="fixed inset-0 z-10 bg-black opacity-25"
                        ></div>
                    </div>
                </div>
            </div>
        </div>
    );
}

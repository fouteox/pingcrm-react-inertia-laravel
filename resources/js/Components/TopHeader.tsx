import Logo from '@/Components/Logo';
import MainMenu from '@/Components/MainMenu';
import { Link } from '@inertiajs/react';
import { useState } from 'react';

export default function TopHeader() {
    const [menuOpened, setMenuOpened] = useState(false);
    return (
        <div className="flex items-center justify-between bg-indigo-900 px-6 py-4 md:w-56 md:shrink-0 md:justify-center">
            <Link className="mt-1" href="/public">
                <Logo
                    className="fill-current text-white"
                    width="120"
                    height="28"
                />
            </Link>
            <div className="relative md:hidden">
                <svg
                    onClick={() => setMenuOpened(true)}
                    className="h-6 w-6 cursor-pointer fill-current text-white"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                >
                    <path d="M0 3h20v2H0V3zm0 6h20v2H0V9zm0 6h20v2H0v-2z" />
                </svg>
                <div
                    className={`${
                        menuOpened ? '' : 'hidden'
                    } absolute right-0 z-20`}
                >
                    <MainMenu
                        onClick={() => {
                            setMenuOpened(false);
                        }}
                        className="relative z-20 mt-2 rounded-sm bg-indigo-800 px-8 py-4 pb-2 shadow-lg"
                    />
                    <div
                        onClick={() => {
                            setMenuOpened(false);
                        }}
                        className="fixed inset-0 z-10 bg-black opacity-25"
                    ></div>
                </div>
            </div>
        </div>
    );
}

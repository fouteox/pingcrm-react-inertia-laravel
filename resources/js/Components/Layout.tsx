import BottomHeader from '@/Components/BottomHeader';
import FlashMessages from '@/Components/FlashMessages';
import MainMenu from '@/Components/MainMenu';
import TopHeader from '@/Components/TopHeader';
import React from 'react';

interface MainLayoutProps {
    children: React.ReactNode;
}

export default function Layout({ children }: MainLayoutProps) {
    return (
        <>
            <FlashMessages />
            <div className="flex flex-col">
                <div className="flex h-screen flex-col">
                    <div className="md:flex">
                        <TopHeader />
                        <BottomHeader />
                    </div>
                    <div className="flex grow overflow-hidden">
                        <MainMenu className="hidden w-60 shrink-0 overflow-y-auto bg-indigo-800 p-12 md:block" />
                        {/* To reset scroll region (https://inertiajs.com/pages#scroll-regions) add `scroll-region="true"` to div below */}
                        <div className="w-full overflow-hidden overflow-y-auto px-4 py-8 md:p-12">
                            {children}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

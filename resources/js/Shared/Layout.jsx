import MainMenu from "@/Shared/MainMenu";
import TopHeader from "@/Shared/TopHeader";
import BottomHeader from "@/Shared/BottomHeader";
import FlashMessages from "@/Shared/FlashMessages";

export default function Layout({ children }) {
    return (
        <>
            <FlashMessages />
            <div className="flex flex-col">
                <div className="flex flex-col h-screen">
                    <div className="md:flex">
                        <TopHeader />
                        <BottomHeader />
                    </div>
                    <div className="flex flex-grow overflow-hidden">
                        <MainMenu className="flex-shrink-0 hidden w-56 p-12 overflow-y-auto bg-indigo-800 md:block" />
                        {/* To reset scroll region (https://inertiajs.com/pages#scroll-regions) add `scroll-region="true"` to div below */}
                        <div className="w-full px-4 py-8 overflow-hidden overflow-y-auto md:p-12">
                            {children}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

import MainMenuItem from '@/Components/MainMenuItem';

interface MainMenuProps {
    className?: string;
    onClick?: () => void;
}

export default function MainMenu({ className, onClick }: MainMenuProps) {
    return (
        <div className={className} onClick={onClick}>
            <MainMenuItem text="Dashboard" link="dashboard" icon="dashboard" />
            <MainMenuItem
                text="Organizations"
                link="organizations.index"
                icon="office"
            />
            <MainMenuItem text="Contacts" link="contacts.index" icon="users" />
            <MainMenuItem text="Reports" link="reports" icon="printer" />
        </div>
    );
}

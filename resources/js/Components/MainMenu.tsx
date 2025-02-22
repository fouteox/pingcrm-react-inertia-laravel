import MainMenuItem from '@/Components/MainMenuItem';
import { useTranslation } from 'react-i18next';

interface MainMenuProps {
    className?: string;
    onClick?: () => void;
}

export default function MainMenu({ className, onClick }: MainMenuProps) {
    const { t } = useTranslation();

    return (
        <div className={className} onClick={onClick}>
            <MainMenuItem
                text={t('Dashboard')}
                link="dashboard"
                icon="dashboard"
            />
            <MainMenuItem
                text={t('Organization', { count: 2 })}
                link="organizations.index"
                icon="office"
            />
            <MainMenuItem
                text={t('Contact', { count: 2 })}
                link="contacts.index"
                icon="users"
            />
            <MainMenuItem text={t('Reports')} link="reports" icon="printer" />
        </div>
    );
}

import { NavLink } from 'react-router-dom';

interface SettingsNavProps {
    active: 'profile' | 'notifications' | 'appearance' | 'security';
}

export default function SettingsNav({ active }: SettingsNavProps): React.JSX.Element {
    const baseClasses = 'block rounded-md px-3 py-2 text-sm font-medium transition-colors';
    const activeClasses = 'bg-white/10 text-white';
    const inactiveClasses = 'text-gray-400 hover:bg-white/5 hover:text-white';
    const placeholderClasses = 'text-gray-600 cursor-default';

    return (
        <aside className="w-44 border-r border-white/10 p-4">
            <p className="mb-3 text-xs font-medium uppercase tracking-wide text-gray-500">Settings</p>
            <nav className="space-y-0.5">
                <span className={[baseClasses, active === 'profile' ? activeClasses : placeholderClasses].join(' ')}>
                    Profile
                </span>
                <NavLink
                    to="/settings/notifications"
                    className={[baseClasses, active === 'notifications' ? activeClasses : inactiveClasses].join(' ')}
                >
                    Notifications
                </NavLink>
                <span className={[baseClasses, active === 'appearance' ? activeClasses : placeholderClasses].join(' ')}>
                    Appearance
                </span>
                <span className={[baseClasses, active === 'security' ? activeClasses : placeholderClasses].join(' ')}>
                    Security
                </span>
            </nav>
        </aside>
    );
}


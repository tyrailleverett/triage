import { NavLink } from 'react-router-dom';

interface SettingsNavItem {
    label: string;
    to: string;
}

const settingsNavItems: SettingsNavItem[] = [
    { label: 'Notifications', to: '/settings/notifications' },
];

export default function SettingsNav(): React.JSX.Element {
    return (
        <aside className="w-44 border-r border-white/10 p-4">
            <p className="mb-3 text-xs font-medium uppercase tracking-wide text-gray-500">Settings</p>
            <nav className="space-y-0.5">
                {settingsNavItems.map((item) => (
                    <NavLink
                        key={item.to}
                        to={item.to}
                        className={({ isActive }) =>
                            [
                                'block rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                isActive
                                    ? 'bg-white/10 text-white'
                                    : 'text-gray-400 hover:bg-white/5 hover:text-white',
                            ].join(' ')
                        }
                    >
                        {item.label}
                    </NavLink>
                ))}
            </nav>
        </aside>
    );
}

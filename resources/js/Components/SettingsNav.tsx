import { NavLink } from 'react-router-dom';
import { Bell, Palette, Shield, User } from 'lucide-react';
import { cn } from '@/lib/utils';

interface SettingsNavProps {
    active: 'profile' | 'notifications' | 'appearance' | 'security';
}

const navItems = [
    { key: 'profile', label: 'Profile', icon: User, to: null },
    { key: 'notifications', label: 'Notifications', icon: Bell, to: '/settings/notifications' },
    { key: 'appearance', label: 'Appearance', icon: Palette, to: null },
    { key: 'security', label: 'Security', icon: Shield, to: null },
] as const;

export default function SettingsNav({ active }: SettingsNavProps): React.JSX.Element {
    return (
        <aside className="w-full border-b border-border p-4 md:w-52 md:border-b-0 md:border-r">
            <nav className="flex flex-wrap gap-1 md:block md:space-y-0.5">
                {navItems.map((item) => {
                    const isActive = active === item.key;
                    const Icon = item.icon;

                    const baseClass = cn(
                        'flex w-full items-center gap-2.5 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                        isActive
                            ? 'bg-accent text-foreground'
                            : item.to !== null
                            ? 'text-muted-foreground hover:bg-accent/50 hover:text-foreground'
                            : 'cursor-default text-muted-foreground/50',
                    );

                    if (item.to !== null) {
                        return (
                            <NavLink key={item.key} to={item.to} className={baseClass}>
                                <Icon className="h-4 w-4 shrink-0" />
                                {item.label}
                            </NavLink>
                        );
                    }

                    return (
                        <span key={item.key} className={baseClass}>
                            <Icon className="h-4 w-4 shrink-0" />
                            {item.label}
                        </span>
                    );
                })}
            </nav>
        </aside>
    );
}

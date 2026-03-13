import { NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom';
import { BarChart2, Inbox, LayoutGrid, Plus, Settings, Ticket } from 'lucide-react';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
import { Button } from '@/Components/ui/button';
import { cn } from '@/lib/utils';

interface NavItem {
    label: string;
    to: string;
    icon: React.ComponentType<{ className?: string }>;
}

function getInitials(name: string): string {
    return name
        .split(' ')
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}

export default function TriageLayout(): React.JSX.Element {
    const location = useLocation();
    const navigate = useNavigate();
    const currentAgent = window.TriageConfig?.currentAgent ?? null;
    const searchParams = new URLSearchParams(location.search);
    const currentAssigneeId = searchParams.get('assignee_id');

    const navItems: NavItem[] = [
        { label: 'All Tickets', to: '/tickets', icon: Ticket },
        {
            label: 'My Queue',
            to: currentAgent?.id !== undefined
                ? `/tickets?assignee_id=${encodeURIComponent(currentAgent.id)}`
                : '/tickets',
            icon: Inbox,
        },
        { label: 'Reports', to: '/reports', icon: BarChart2 },
        { label: 'Settings', to: '/settings', icon: Settings },
    ];

    return (
        <div className="flex min-h-screen flex-col bg-background text-foreground md:h-screen md:flex-row">
            <aside className="flex w-full flex-col border-b border-border bg-card md:w-52 md:border-b-0 md:border-r">
                {/* Logo */}
                <div className="flex items-center gap-2.5 px-4 py-4">
                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-primary">
                        <LayoutGrid className="h-4 w-4 text-primary-foreground" />
                    </div>
                    <span className="text-base font-semibold tracking-tight text-foreground">Triage</span>
                </div>

                {/* New Ticket Button */}
                <div className="px-3 pb-3">
                    <Button
                        className="w-full justify-center gap-1.5 bg-primary text-primary-foreground hover:bg-primary/90"
                        onClick={() => navigate('/tickets/create')}
                    >
                        <Plus className="h-4 w-4" />
                        New Ticket
                    </Button>
                </div>

                {/* Navigation */}
                <nav className="flex flex-wrap gap-1 px-2 pb-3 md:flex-1 md:flex-col md:space-y-0.5 md:pb-0">
                    {navItems.map((item) => {
                        const isMyQueue = item.label === 'My Queue';
                        const isActive =
                            (!isMyQueue && location.pathname === item.to) ||
                            (!isMyQueue && item.to === '/tickets' && location.pathname === '/') ||
                            (isMyQueue && currentAgent?.id === currentAssigneeId) ||
                            (!isMyQueue && location.pathname.startsWith(item.to) && item.to !== '/tickets') ||
                            (!isMyQueue && item.to === '/tickets' && location.pathname.startsWith('/tickets') && currentAssigneeId === null);

                        return (
                            <NavLink
                                key={item.label}
                                to={item.to}
                                className={cn(
                                    'flex items-center gap-2.5 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                    isActive
                                        ? 'bg-accent text-accent-foreground'
                                        : 'text-muted-foreground hover:bg-accent/50 hover:text-foreground',
                                )}
                            >
                                <item.icon className="h-4 w-4 shrink-0" />
                                {item.label}
                            </NavLink>
                        );
                    })}
                </nav>

                {/* Agent Footer */}
                <div className="border-t border-border px-4 py-4 md:mt-auto">
                    <div className="flex items-center gap-2.5">
                        <Avatar className="h-8 w-8">
                            <AvatarFallback className="bg-primary text-xs font-medium text-primary-foreground">
                                {getInitials(currentAgent?.name ?? 'Agent')}
                            </AvatarFallback>
                        </Avatar>
                        <div className="min-w-0">
                            <p className="truncate text-xs font-medium text-foreground">{currentAgent?.name ?? 'Agent'}</p>
                            <p className="truncate text-xs text-muted-foreground">{currentAgent?.role ?? 'Support Agent'}</p>
                        </div>
                    </div>
                </div>
            </aside>

            <main className="min-h-0 flex-1 overflow-auto">
                <Outlet />
            </main>
        </div>
    );
}

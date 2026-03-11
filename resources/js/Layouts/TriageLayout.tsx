import { NavLink, Outlet, useLocation } from 'react-router-dom';

interface NavItem {
    label: string;
    to: string;
}

export default function TriageLayout(): React.JSX.Element {
    const location = useLocation();
    const currentAgent = window.TriageConfig?.currentAgent ?? null;
    const searchParams = new URLSearchParams(location.search);
    const currentAssigneeId = searchParams.get('assignee_id');
    const navItems: NavItem[] = [
        { label: 'All Tickets', to: '/tickets' },
        {
            label: 'My Queue',
            to: currentAgent?.id !== undefined
                ? `/tickets?assignee_id=${encodeURIComponent(currentAgent.id)}`
                : '/tickets',
        },
        { label: 'Reports', to: '/reports' },
        { label: 'Settings', to: '/settings' },
    ];

    return (
        <div className="flex h-screen bg-[#0d0f14] text-gray-100">
            <aside className="flex w-44 flex-col border-r border-white/10 bg-[#111318]">
                <div className="px-4 py-5">
                    <span className="text-lg font-semibold tracking-tight text-white">Triage</span>
                </div>

                <nav className="flex-1 space-y-0.5 px-2">
                    {navItems.map((item) => (
                        <NavLink
                            key={item.to}
                            to={item.to}
                            className={({ isActive }) =>
                                [
                                    'block rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                    isActive
                                    || (item.to === '/tickets' && location.pathname === '/')
                                    || (item.label === 'My Queue' && currentAgent?.id === currentAssigneeId)
                                        ? 'bg-white/10 text-white'
                                        : 'text-gray-400 hover:bg-white/5 hover:text-white',
                                ].join(' ')
                            }
                        >
                            {item.label}
                        </NavLink>
                    ))}
                </nav>

                <div className="border-t border-white/10 px-4 py-4">
                    <div className="flex items-center gap-2">
                        <div className="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-600 text-xs font-medium text-white">
                            {(currentAgent?.name ?? 'A').charAt(0).toUpperCase()}
                        </div>
                        <div className="min-w-0">
                            <p className="truncate text-xs font-medium text-white">{currentAgent?.name ?? 'Agent'}</p>
                            <p className="truncate text-xs text-gray-500">{currentAgent?.role ?? currentAgent?.email ?? 'Support Agent'}</p>
                        </div>
                    </div>
                </div>
            </aside>

            <main className="flex-1 overflow-auto">
                <Outlet />
            </main>
        </div>
    );
}

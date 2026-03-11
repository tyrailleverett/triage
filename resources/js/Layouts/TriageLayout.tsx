import { NavLink, Outlet, useLocation } from 'react-router-dom';

interface NavItem {
  label: string;
  to: string;
}

const navItems: NavItem[] = [
  { label: 'All Tickets', to: '/tickets' },
  { label: 'My Queue', to: '/tickets?assignee=me' },
  { label: 'Reports', to: '/reports' },
  { label: 'Settings', to: '/settings' },
];

export default function TriageLayout(): React.JSX.Element {
  const location = useLocation();

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
                  isActive || (item.to === '/tickets' && location.pathname === '/')
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
              A
            </div>
            <div className="min-w-0">
              <p className="truncate text-xs font-medium text-white">Agent</p>
              <p className="truncate text-xs text-gray-500">Support</p>
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

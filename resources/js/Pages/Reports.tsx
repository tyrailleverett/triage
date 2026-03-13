import { useEffect, useState } from 'react';
import { BarChart2 } from 'lucide-react';
import { api } from '@/lib/api';
import type { ApiError } from '@/lib/api';
import type { PaginatedResponse, Ticket } from '@/types';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
import { Separator } from '@/Components/ui/separator';
import { cn } from '@/lib/utils';

interface AgentStats {
    id: string;
    name: string;
    email: string;
    assigned: number;
    open: number;
    resolvedOrClosed: number;
}

const statusBarConfig = {
    open: { label: 'Open', color: 'bg-green-500' },
    pending: { label: 'Pending', color: 'bg-yellow-500' },
    resolved: { label: 'Resolved', color: 'bg-blue-500' },
    closed: { label: 'Closed', color: 'bg-gray-500' },
} as const;

const priorityBarConfig = {
    urgent: { label: 'Urgent', color: 'bg-red-500' },
    high: { label: 'High', color: 'bg-orange-500' },
    normal: { label: 'Normal', color: 'bg-blue-500' },
    low: { label: 'Low', color: 'bg-gray-500' },
} as const;

const avatarColors = [
    'bg-blue-600',
    'bg-purple-600',
    'bg-green-600',
    'bg-orange-600',
    'bg-pink-600',
    'bg-teal-600',
];

function avatarColor(name: string): string {
    let hash = 0;

    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }

    return avatarColors[Math.abs(hash) % avatarColors.length];
}

function getInitials(name: string): string {
    return name
        .split(' ')
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}

export default function Reports(): React.JSX.Element {
    const [tickets, setTickets] = useState<Ticket[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const fetchAll = async (): Promise<void> => {
            setIsLoading(true);

            try {
                const allTickets: Ticket[] = [];
                let page = 1;
                let lastPage = 1;

                do {
                    const params = new URLSearchParams({ page: String(page), per_page: '100' });
                    const data = await api.get<PaginatedResponse<Ticket>>(`/tickets?${params.toString()}`);
                    allTickets.push(...data.data);
                    lastPage = data.meta.last_page;
                    page++;
                } while (page <= lastPage);

                setTickets(allTickets);
            } catch (err) {
                const apiErr = err as ApiError;
                setError(apiErr.message ?? 'Failed to load report data.');
            } finally {
                setIsLoading(false);
            }
        };

        void fetchAll();
    }, []);

    const total = tickets.length;
    const openCount = tickets.filter((t) => t.status === 'open').length;
    const resolvedCount = tickets.filter((t) => t.status === 'resolved').length;
    const resolutionRate = total > 0 ? Math.round(((resolvedCount + tickets.filter((t) => t.status === 'closed').length) / total) * 100) : 0;

    const statusCounts = {
        open: tickets.filter((t) => t.status === 'open').length,
        pending: tickets.filter((t) => t.status === 'pending').length,
        resolved: tickets.filter((t) => t.status === 'resolved').length,
        closed: tickets.filter((t) => t.status === 'closed').length,
    };

    const priorityCounts = {
        urgent: tickets.filter((t) => t.priority === 'urgent').length,
        high: tickets.filter((t) => t.priority === 'high').length,
        normal: tickets.filter((t) => t.priority === 'normal').length,
        low: tickets.filter((t) => t.priority === 'low').length,
    };

    const maxStatusCount = Math.max(...Object.values(statusCounts), 1);
    const maxPriorityCount = Math.max(...Object.values(priorityCounts), 1);

    // Build agent stats
    const agentMap = new Map<string, AgentStats>();

    tickets.forEach((ticket) => {
        if (ticket.assignee !== null) {
            const { id, name, email } = ticket.assignee;

            if (!agentMap.has(id)) {
                agentMap.set(id, { id, name, email, assigned: 0, open: 0, resolvedOrClosed: 0 });
            }

            const stats = agentMap.get(id)!;
            stats.assigned++;

            if (ticket.status === 'open' || ticket.status === 'pending') {
                stats.open++;
            } else {
                stats.resolvedOrClosed++;
            }
        }
    });

    const agentStats = Array.from(agentMap.values()).sort((a, b) => b.assigned - a.assigned);

    const statCards = [
        { value: total, label: 'Total Tickets', sub: 'all time' },
        { value: openCount, label: 'Open', sub: 'need attention' },
        { value: resolvedCount, label: 'Resolved', sub: 'closed out' },
        { value: `${resolutionRate}%`, label: 'Resolution Rate', sub: 'of all tickets' },
    ];

    if (isLoading) {
        return (
            <div className="flex h-full items-center justify-center text-muted-foreground">Loading…</div>
        );
    }

    return (
        <div className="p-6">
            {/* Page header */}
            <div className="mb-6 flex items-center justify-between">
                <div>
                    <h1 className="text-xl font-semibold text-foreground">Reports</h1>
                    <p className="mt-0.5 text-sm text-muted-foreground">
                        Overview of ticket volume and agent performance
                    </p>
                </div>
                <div className="flex items-center gap-1.5 text-sm text-muted-foreground">
                    <BarChart2 className="h-4 w-4" />
                    All time
                </div>
            </div>

            {error !== null && (
                <div className="mb-4 rounded-md border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                    {error}
                </div>
            )}

            {/* Stat cards */}
            <div className="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {statCards.map((card) => (
                    <div key={card.label} className="rounded-lg border border-border bg-card p-5">
                        <p className="text-3xl font-semibold text-foreground">{card.value}</p>
                        <p className="mt-1 text-sm font-medium text-foreground">{card.label}</p>
                        <p className="text-xs text-muted-foreground">{card.sub}</p>
                    </div>
                ))}
            </div>

            {/* Charts row */}
            <div className="mb-6 grid gap-4 lg:grid-cols-2">
                {/* Tickets by Status */}
                <div className="rounded-lg border border-border bg-card p-5">
                    <h2 className="mb-4 text-base font-semibold text-foreground">Tickets by Status</h2>
                    <div className="space-y-3">
                        {(Object.entries(statusBarConfig) as Array<[keyof typeof statusBarConfig, (typeof statusBarConfig)[keyof typeof statusBarConfig]]>).map(([key, config]) => {
                            const count = statusCounts[key];
                            const pct = (count / maxStatusCount) * 100;

                            return (
                                <div key={key}>
                                    <div className="mb-1 flex items-center justify-between text-xs">
                                        <span className="text-muted-foreground">{config.label}</span>
                                        <span className="font-medium text-foreground">{count}</span>
                                    </div>
                                    <div className="h-2 overflow-hidden rounded-full bg-border">
                                        <div
                                            className={cn('h-full rounded-full transition-all', config.color)}
                                            style={{ width: `${pct}%` }}
                                        />
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>

                {/* Tickets by Priority */}
                <div className="rounded-lg border border-border bg-card p-5">
                    <h2 className="mb-4 text-base font-semibold text-foreground">Tickets by Priority</h2>
                    <div className="space-y-3">
                        {(Object.entries(priorityBarConfig) as Array<[keyof typeof priorityBarConfig, (typeof priorityBarConfig)[keyof typeof priorityBarConfig]]>).map(([key, config]) => {
                            const count = priorityCounts[key];
                            const pct = (count / maxPriorityCount) * 100;

                            return (
                                <div key={key}>
                                    <div className="mb-1 flex items-center justify-between text-xs">
                                        <span className="text-muted-foreground">{config.label}</span>
                                        <span className="font-medium text-foreground">{count}</span>
                                    </div>
                                    <div className="h-2 overflow-hidden rounded-full bg-border">
                                        <div
                                            className={cn('h-full rounded-full transition-all', config.color)}
                                            style={{ width: `${pct}%` }}
                                        />
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </div>

            {/* Agent Performance */}
            <div className="rounded-lg border border-border bg-card">
                <div className="border-b border-border px-5 py-4">
                    <h2 className="text-base font-semibold text-foreground">Agent Performance</h2>
                </div>

                {agentStats.length === 0 ? (
                    <div className="flex h-24 items-center justify-center text-sm text-muted-foreground">
                        No assigned tickets yet.
                    </div>
                ) : (
                    <div>
                        <div className="grid grid-cols-4 border-b border-border px-5 py-2.5 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            <span>Agent</span>
                            <span className="text-center">Assigned</span>
                            <span className="text-center">Open</span>
                            <span className="text-center">Resolved / Closed</span>
                        </div>
                        <div className="divide-y divide-border">
                            {agentStats.map((agent) => (
                                <div key={agent.id} className="grid grid-cols-4 items-center px-5 py-3.5">
                                    <div className="flex items-center gap-2.5">
                                        <Avatar className="h-8 w-8">
                                            <AvatarFallback className={`text-xs font-medium text-white ${avatarColor(agent.name)}`}>
                                                {getInitials(agent.name)}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div>
                                            <p className="text-sm font-medium text-foreground">{agent.name}</p>
                                            <p className="text-xs text-muted-foreground">{agent.email}</p>
                                        </div>
                                    </div>
                                    <p className="text-center text-sm text-foreground">{agent.assigned}</p>
                                    <p className={cn(
                                        'text-center text-sm font-medium',
                                        agent.open > 0 ? 'text-green-400' : 'text-muted-foreground',
                                    )}>
                                        {agent.open}
                                    </p>
                                    <p className="text-center text-sm text-foreground">{agent.resolvedOrClosed}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>

            <Separator className="my-6" />

            <p className="text-center text-xs text-muted-foreground">
                More detailed analytics coming in a future release.
            </p>
        </div>
    );
}

import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { ChevronLeft, Mail } from 'lucide-react';
import { api } from '@/lib/api';
import type { ApiError } from '@/lib/api';
import type { Ticket, TicketMessage, TicketNote, TicketPriority, TicketStatus } from '@/types';
import Badge from '@/Components/Badge';
import MessageComposer from '@/Components/MessageComposer';
import TicketMetaPanel from '@/Components/TicketMetaPanel';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';

interface TicketDetailResponse {
    data: Ticket & {
        messages: TicketMessage[];
        notes: TicketNote[];
    };
}

function getInitials(name: string): string {
    return name
        .split(' ')
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    });
}

function formatTicketId(id: string): string {
    const num = parseInt(id, 10);

    return isNaN(num) ? id : `TKT-${String(num).padStart(4, '0')}`;
}

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

export default function TicketShow(): React.JSX.Element {
    const { ticketId } = useParams<{ ticketId: string }>();
    const currentAgent = window.TriageConfig?.currentAgent ?? null;

    const [ticket, setTicket] = useState<TicketDetailResponse['data'] | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    const loadTicket = async (): Promise<void> => {
        setIsLoading(true);
        setError(null);

        try {
            const data = await api.get<TicketDetailResponse>(`/tickets/${ticketId}`);
            setTicket(data.data);
        } catch (err) {
            const apiErr = err as ApiError;

            if (apiErr.status === 404) {
                setError('Ticket not found.');
            } else if (apiErr.status === 403) {
                setError('You do not have permission to view this ticket.');
            } else {
                setError(apiErr.message ?? 'Failed to load ticket.');
            }
        } finally {
            setIsLoading(false);
        }
    };

    useEffect(() => {
        void loadTicket();
    }, [ticketId]);

    const handleReplySubmit = async (body: string): Promise<void> => {
        await api.post(`/tickets/${ticketId}/messages`, { body });
        await loadTicket();
    };

    const handleNoteSubmit = async (body: string): Promise<void> => {
        await api.post(`/tickets/${ticketId}/notes`, { body });
        await loadTicket();
    };

    const handleMetaChange = async (updates: { status?: TicketStatus; priority?: TicketPriority; assignee_id?: string | null }): Promise<void> => {
        await api.patch(`/tickets/${ticketId}`, updates);
        await loadTicket();
    };

    if (isLoading) {
        return (
            <div className="flex h-full items-center justify-center text-muted-foreground">
                Loading…
            </div>
        );
    }

    if (error !== null || ticket === null) {
        return (
            <div className="flex h-full items-center justify-center">
                <p className="text-sm text-destructive">{error ?? 'Ticket not found.'}</p>
            </div>
        );
    }

    const thread = [
        ...ticket.messages.map((m) => ({ ...m, itemType: 'message' as const })),
        ...ticket.notes.map((n) => ({ ...n, itemType: 'note' as const })),
    ].sort((a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime());

    return (
        <div className="flex h-full flex-col">
            {/* Page header */}
            <div className="flex items-start justify-between border-b border-border px-6 py-4">
                <div>
                    <Link
                        to="/tickets"
                        className="mb-1 inline-flex items-center gap-1 text-xs text-muted-foreground transition-colors hover:text-foreground"
                    >
                        <ChevronLeft className="h-3 w-3" />
                        All Tickets
                    </Link>
                    <h1 className="mt-0.5 text-xl font-semibold text-foreground">{ticket.subject}</h1>
                    <p className="mt-0.5 text-sm text-muted-foreground">{formatTicketId(ticket.id)}</p>
                </div>
                <div className="flex items-center gap-2">
                    <Badge type="status" value={ticket.status} />
                    <Badge type="priority" value={ticket.priority} />
                </div>
            </div>

            {/* Main content */}
            <div className="flex flex-1 flex-col overflow-hidden xl:flex-row">
                {/* Thread + Composer */}
                <div className="flex flex-1 flex-col overflow-hidden">
                    {/* Thread */}
                    <div className="flex-1 space-y-4 overflow-y-auto p-6">
                        {thread.map((item) => {
                            const isNote = item.itemType === 'note';
                            const isOutbound = !isNote && 'direction' in item && item.direction === 'outbound';
                            const authorName = item.author?.name ?? 'Unknown';

                            return (
                                <div
                                    key={`${item.itemType}-${item.id}`}
                                    className={isNote
                                        ? 'rounded-lg border border-yellow-800/40 bg-yellow-950/30 px-5 py-4'
                                        : 'rounded-lg border border-border bg-card px-5 py-4'}
                                >
                                    {/* Message header */}
                                    <div className="mb-3 flex items-center justify-between">
                                        <div className="flex items-center gap-2.5">
                                            <Avatar className="h-8 w-8">
                                                <AvatarFallback className={`text-xs font-medium text-white ${avatarColor(authorName)}`}>
                                                    {getInitials(authorName)}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <span className="text-sm font-medium text-foreground">{authorName}</span>
                                                    {isNote && (
                                                        <span className="inline-flex items-center gap-1 rounded border border-yellow-700/50 bg-yellow-900/30 px-1.5 py-0.5 text-[10px] font-medium text-yellow-400">
                                                            <span>📋</span>
                                                            Internal Note
                                                        </span>
                                                    )}
                                                    {isOutbound && (
                                                        <span className="inline-flex items-center gap-1 rounded border border-blue-700/50 bg-blue-900/30 px-1.5 py-0.5 text-[10px] font-medium text-blue-400">
                                                            <span>↗</span>
                                                            Outbound
                                                        </span>
                                                    )}
                                                    {!isNote && !isOutbound && (
                                                        <span className="inline-flex items-center gap-1 rounded border border-border bg-secondary px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground">
                                                            <Mail className="h-2.5 w-2.5" />
                                                            Customer
                                                        </span>
                                                    )}
                                                </div>
                                                {item.author?.email !== undefined && (
                                                    <p className="text-xs text-muted-foreground">{item.author.email}</p>
                                                )}
                                            </div>
                                        </div>
                                        <span className="text-xs text-muted-foreground">
                                            {formatDate(item.created_at)}
                                        </span>
                                    </div>

                                    {/* Message body */}
                                    <p className={`whitespace-pre-wrap text-sm leading-relaxed ${isNote ? 'text-yellow-200/90' : 'text-foreground'}`}>
                                        {item.body}
                                    </p>
                                </div>
                            );
                        })}
                    </div>

                    {/* Composer */}
                    <div className="border-t border-border p-4 md:p-6">
                        <MessageComposer
                            onReplySubmit={handleReplySubmit}
                            onNoteSubmit={handleNoteSubmit}
                        />
                    </div>
                </div>

                {/* Meta panel */}
                <aside className="w-full overflow-y-auto border-t border-border xl:w-72 xl:border-l xl:border-t-0">
                    <TicketMetaPanel
                        ticket={ticket}
                        onChange={handleMetaChange}
                        currentAgent={currentAgent !== null ? { id: currentAgent.id, name: currentAgent.name } : null}
                    />
                </aside>
            </div>
        </div>
    );
}

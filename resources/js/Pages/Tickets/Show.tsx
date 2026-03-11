import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { api } from '../../lib/api';
import type { ApiError } from '../../lib/api';
import type { Ticket, TicketMessage, TicketNote, TicketPriority, TicketStatus } from '../../types';
import Badge from '../../Components/Badge';
import MessageComposer from '../../Components/MessageComposer';
import TicketMetaPanel from '../../Components/TicketMetaPanel';

interface TicketDetailResponse {
    data: Ticket & {
        messages: TicketMessage[];
        notes: TicketNote[];
    };
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
            <div className="flex h-full items-center justify-center text-gray-500">
                Loading…
            </div>
        );
    }

    if (error !== null || ticket === null) {
        return (
            <div className="flex h-full items-center justify-center">
                <p className="text-sm text-red-400">{error ?? 'Ticket not found.'}</p>
            </div>
        );
    }

    const thread = [
        ...ticket.messages.map((m) => ({ ...m, itemType: 'message' as const })),
        ...ticket.notes.map((n) => ({ ...n, itemType: 'note' as const })),
    ].sort((a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime());

    return (
        <div className="flex h-full flex-col">
            <div className="border-b border-white/10 px-6 py-4">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <p className="text-xs font-medium uppercase tracking-wide text-gray-500">
                            #{ticket.id}
                        </p>
                        <h1 className="mt-0.5 text-lg font-semibold text-white">{ticket.subject}</h1>
                    </div>
                    <div className="flex items-center gap-2">
                        <Badge type="status" value={ticket.status} />
                        <Badge type="priority" value={ticket.priority} />
                    </div>
                </div>
            </div>

            <div className="flex flex-1 overflow-hidden">
                <div className="flex flex-1 flex-col overflow-hidden">
                    <div className="flex-1 overflow-y-auto px-6 py-4 space-y-4">
                        {thread.map((item) => (
                            <div
                                key={`${item.itemType}-${item.id}`}
                                className={[
                                    'rounded-lg border px-4 py-3',
                                    item.itemType === 'note'
                                        ? 'border-yellow-800/50 bg-yellow-900/20'
                                        : 'border-white/10 bg-white/5',
                                ].join(' ')}
                            >
                                <div className="mb-1 flex items-center justify-between">
                                    <span className="text-xs font-medium text-gray-400">
                                        {item.author?.name ?? 'Unknown'}
                                        {item.itemType === 'note' && (
                                            <span className="ml-1 text-yellow-500">(note)</span>
                                        )}
                                    </span>
                                    <span className="text-xs text-gray-600">
                                        {new Date(item.created_at).toLocaleString()}
                                    </span>
                                </div>
                                <p className="whitespace-pre-wrap text-sm text-gray-200">{item.body}</p>
                            </div>
                        ))}
                    </div>

                    <div className="border-t border-white/10 px-6 py-4 space-y-3">
                        <MessageComposer
                            label="Reply"
                            onSubmit={handleReplySubmit}
                        />
                        <MessageComposer
                            label="Add Note"
                            variant="note"
                            onSubmit={handleNoteSubmit}
                        />
                    </div>
                </div>

                <aside className="w-64 border-l border-white/10 overflow-y-auto">
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

import type { Ticket, TicketPriority, TicketStatus } from '../types';

interface TicketMetaPanelProps {
    ticket: Ticket;
    onChange: (updates: { status?: TicketStatus; priority?: TicketPriority; assignee_id?: string | null }) => Promise<void>;
    currentAgent?: {
        id: string;
        name: string;
    } | null;
}

const statusOptions: TicketStatus[] = ['open', 'pending', 'resolved', 'closed'];
const priorityOptions: TicketPriority[] = ['low', 'normal', 'high', 'urgent'];

const capitalize = (s: string): string => s.charAt(0).toUpperCase() + s.slice(1);

export default function TicketMetaPanel({ ticket, onChange, currentAgent = null }: TicketMetaPanelProps): React.JSX.Element {
    const handleStatusChange = async (e: React.ChangeEvent<HTMLSelectElement>): Promise<void> => {
        await onChange({ status: e.target.value as TicketStatus });
    };

    const handlePriorityChange = async (e: React.ChangeEvent<HTMLSelectElement>): Promise<void> => {
        await onChange({ priority: e.target.value as TicketPriority });
    };

    const handleAssignToMe = async (): Promise<void> => {
        if (currentAgent === null) {
            return;
        }

        await onChange({ assignee_id: currentAgent.id });
    };

    const handleUnassign = async (): Promise<void> => {
        await onChange({ assignee_id: null });
    };

    return (
        <div className="space-y-5 p-4">
            <div>
                <p className="mb-1.5 text-xs font-medium uppercase tracking-wide text-gray-500">Status</p>
                <select
                    value={ticket.status}
                    onChange={handleStatusChange}
                    className="w-full rounded-md border border-white/10 bg-[#111318] px-2 py-1.5 text-sm text-white focus:outline-none focus:ring-1 focus:ring-indigo-500"
                >
                    {statusOptions.map((s) => (
                        <option key={s} value={s}>{capitalize(s)}</option>
                    ))}
                </select>
            </div>

            <div>
                <p className="mb-1.5 text-xs font-medium uppercase tracking-wide text-gray-500">Priority</p>
                <select
                    value={ticket.priority}
                    onChange={handlePriorityChange}
                    className="w-full rounded-md border border-white/10 bg-[#111318] px-2 py-1.5 text-sm text-white focus:outline-none focus:ring-1 focus:ring-indigo-500"
                >
                    {priorityOptions.map((p) => (
                        <option key={p} value={p}>{capitalize(p)}</option>
                    ))}
                </select>
            </div>

            <div>
                <p className="mb-1.5 text-xs font-medium uppercase tracking-wide text-gray-500">Submitter</p>
                <p className="text-sm text-gray-300">{ticket.submitter?.name ?? '—'}</p>
                <p className="text-xs text-gray-600">{ticket.submitter?.email ?? ''}</p>
            </div>

            <div>
                <p className="mb-1.5 text-xs font-medium uppercase tracking-wide text-gray-500">Assignee</p>
                <p className="text-sm text-gray-300">{ticket.assignee?.name ?? 'Unassigned'}</p>
                <div className="mt-2 flex gap-2">
                    {currentAgent !== null && (
                        <button
                            type="button"
                            onClick={() => void handleAssignToMe()}
                            className="rounded-md border border-white/10 px-3 py-1.5 text-xs font-medium text-gray-300 transition-colors hover:bg-white/10 hover:text-white"
                        >
                            Assign to me
                        </button>
                    )}
                    {ticket.assignee !== null && (
                        <button
                            type="button"
                            onClick={() => void handleUnassign()}
                            className="rounded-md border border-white/10 px-3 py-1.5 text-xs font-medium text-gray-400 transition-colors hover:bg-white/10 hover:text-white"
                        >
                            Unassign
                        </button>
                    )}
                </div>
            </div>

            <div>
                <p className="mb-1.5 text-xs font-medium uppercase tracking-wide text-gray-500">Created</p>
                <p className="text-xs text-gray-500">{new Date(ticket.created_at).toLocaleString()}</p>
            </div>

            <div>
                <p className="mb-1.5 text-xs font-medium uppercase tracking-wide text-gray-500">Updated</p>
                <p className="text-xs text-gray-500">{new Date(ticket.updated_at).toLocaleString()}</p>
            </div>
        </div>
    );
}

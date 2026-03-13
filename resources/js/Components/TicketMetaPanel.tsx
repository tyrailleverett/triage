import { Calendar, Clock, Hash, User } from 'lucide-react';
import Badge from '@/Components/Badge';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
import { Separator } from '@/Components/ui/separator';
import type { Ticket, TicketPriority, TicketStatus } from '@/types';

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

function formatTicketId(id: string): string {
    const num = parseInt(id, 10);

    return isNaN(num) ? id : `TKT-${String(num).padStart(4, '0')}`;
}

function formatDateShort(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
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

function getInitials(name: string): string {
    return name
        .split(' ')
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}

export default function TicketMetaPanel({ ticket, onChange, currentAgent = null }: TicketMetaPanelProps): React.JSX.Element {
    const handleStatusChange = async (e: React.ChangeEvent<HTMLSelectElement>): Promise<void> => {
        await onChange({ status: e.target.value as TicketStatus });
    };

    const handlePriorityChange = async (e: React.ChangeEvent<HTMLSelectElement>): Promise<void> => {
        await onChange({ priority: e.target.value as TicketPriority });
    };

    const handleAssignToMe = async (): Promise<void> => {
        if (currentAgent === null) { return; }

        await onChange({ assignee_id: currentAgent.id });
    };

    const handleUnassign = async (): Promise<void> => {
        await onChange({ assignee_id: null });
    };

    const assigneeName = ticket.assignee?.name ?? null;

    return (
        <div className="p-5">
            <p className="mb-4 text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">
                Ticket Details
            </p>

            {/* Status */}
            <div className="mb-4">
                <p className="mb-1.5 text-xs font-medium text-muted-foreground">Status</p>
                <select
                    value={ticket.status}
                    onChange={handleStatusChange}
                    className="mb-2 w-full rounded-md border border-border bg-card px-2.5 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                >
                    {statusOptions.map((s) => (
                        <option key={s} value={s}>{capitalize(s)}</option>
                    ))}
                </select>
                <Badge type="status" value={ticket.status} />
            </div>

            <Separator className="my-4" />

            {/* Priority */}
            <div className="mb-4">
                <p className="mb-1.5 text-xs font-medium text-muted-foreground">Priority</p>
                <select
                    value={ticket.priority}
                    onChange={handlePriorityChange}
                    className="mb-2 w-full rounded-md border border-border bg-card px-2.5 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                >
                    {priorityOptions.map((p) => (
                        <option key={p} value={p}>{capitalize(p)}</option>
                    ))}
                </select>
                <Badge type="priority" value={ticket.priority} />
            </div>

            <Separator className="my-4" />

            {/* Assignee */}
            <div className="mb-4">
                <p className="mb-2 text-xs font-medium text-muted-foreground">Assignee</p>
                <select
                    value={ticket.assignee_id ?? ''}
                    onChange={(e) => void onChange({ assignee_id: e.target.value || null })}
                    className="mb-2 w-full rounded-md border border-border bg-card px-2.5 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                >
                    <option value="">Unassigned</option>
                    {currentAgent !== null && (
                        <option value={currentAgent.id}>{currentAgent.name}</option>
                    )}
                    {ticket.assignee !== null && ticket.assignee.id !== currentAgent?.id && (
                        <option value={ticket.assignee.id}>{ticket.assignee.name}</option>
                    )}
                </select>
                {assigneeName !== null && (
                    <div className="flex items-center gap-2">
                        <Avatar className="h-6 w-6">
                            <AvatarFallback className={`text-[10px] font-medium text-white ${avatarColor(assigneeName)}`}>
                                {getInitials(assigneeName)}
                            </AvatarFallback>
                        </Avatar>
                        <span className="text-sm text-foreground">{assigneeName}</span>
                    </div>
                )}
                <div className="mt-2 flex gap-2">
                    {currentAgent !== null && (
                        <button
                            type="button"
                            onClick={() => void handleAssignToMe()}
                            className="rounded-md border border-border px-2.5 py-1 text-xs font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                        >
                            Assign to me
                        </button>
                    )}
                    {ticket.assignee !== null && (
                        <button
                            type="button"
                            onClick={() => void handleUnassign()}
                            className="rounded-md border border-border px-2.5 py-1 text-xs font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                        >
                            Unassign
                        </button>
                    )}
                </div>
            </div>

            <Separator className="my-4" />

            {/* Submitter */}
            <div className="mb-4">
                <p className="mb-2 text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">
                    Submitter
                </p>
                <div className="flex items-center gap-2.5">
                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-muted">
                        <User className="h-4 w-4 text-muted-foreground" />
                    </div>
                    <div>
                        <p className="text-sm font-medium text-foreground">{ticket.submitter?.name ?? ticket.submitter_name}</p>
                        <p className="text-xs text-muted-foreground">{ticket.submitter?.email ?? ticket.submitter_email}</p>
                    </div>
                </div>
            </div>

            <Separator className="my-4" />

            {/* Timestamps */}
            <div className="mb-4">
                <p className="mb-3 text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">
                    Timestamps
                </p>
                <div className="space-y-2">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <Calendar className="h-3.5 w-3.5" />
                            <span>Created</span>
                        </div>
                        <span className="text-xs text-foreground">{formatDateShort(ticket.created_at)}</span>
                    </div>
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <Clock className="h-3.5 w-3.5" />
                            <span>Updated</span>
                        </div>
                        <span className="text-xs text-foreground">{formatDateShort(ticket.updated_at)}</span>
                    </div>
                </div>
            </div>

            <Separator className="my-4" />

            {/* Ticket ID */}
            <div>
                <p className="mb-1 text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">
                    Ticket ID
                </p>
                <div className="flex items-center gap-1.5">
                    <Hash className="h-3.5 w-3.5 text-muted-foreground" />
                    <span className="font-mono text-sm font-medium text-foreground">{formatTicketId(ticket.id)}</span>
                </div>
            </div>
        </div>
    );
}

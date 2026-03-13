import { ChevronRight } from 'lucide-react';
import Badge from '@/Components/Badge';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import type { Ticket } from '@/types';

interface TicketTableProps {
    tickets: Ticket[];
    onRowClick: (ticket: Ticket) => void;
}

function getInitials(name: string): string {
    return name
        .split(' ')
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}

function formatTicketId(id: string): string {
    const num = parseInt(id, 10);

    return isNaN(num) ? id : `TKT-${String(num).padStart(4, '0')}`;
}

function timeAgo(dateString: string): string {
    const now = new Date();
    const date = new Date(dateString);
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffMins < 60) { return `${diffMins} minute${diffMins !== 1 ? 's' : ''} ago`; }
    if (diffHours < 24) { return `about ${diffHours} hour${diffHours !== 1 ? 's' : ''} ago`; }
    if (diffDays === 1) { return '1 day ago'; }

    return `${diffDays} days ago`;
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

export default function TicketTable({ tickets, onRowClick }: TicketTableProps): React.JSX.Element {
    if (tickets.length === 0) {
        return (
            <div className="flex h-32 items-center justify-center rounded-lg border border-border bg-card text-sm text-muted-foreground">
                No tickets found.
            </div>
        );
    }

    return (
        <>
            {/* Desktop table */}
            <div className="hidden overflow-hidden rounded-lg border border-border lg:block">
                <Table>
                    <TableHeader>
                        <TableRow className="border-border hover:bg-transparent">
                            <TableHead className="text-xs font-medium uppercase tracking-wide text-muted-foreground">ID</TableHead>
                            <TableHead className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Subject</TableHead>
                            <TableHead className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Status</TableHead>
                            <TableHead className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Priority</TableHead>
                            <TableHead className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Submitter</TableHead>
                            <TableHead className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Assignee</TableHead>
                            <TableHead className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Created</TableHead>
                            <TableHead className="w-8" />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {tickets.map((ticket) => {
                            const assigneeName = ticket.assignee?.name ?? null;

                            return (
                                <TableRow
                                    key={ticket.id}
                                    onClick={() => onRowClick(ticket)}
                                    className="cursor-pointer border-border transition-colors hover:bg-accent/30"
                                >
                                    <TableCell className="font-mono text-xs text-muted-foreground">
                                        {formatTicketId(ticket.id)}
                                    </TableCell>
                                    <TableCell>
                                        <p className="max-w-xs truncate font-medium text-foreground">
                                            {ticket.subject}
                                        </p>
                                        <p className="mt-0.5 text-xs text-muted-foreground">
                                            {ticket.submitter?.email ?? ticket.submitter_email}
                                        </p>
                                    </TableCell>
                                    <TableCell>
                                        <Badge type="status" value={ticket.status} />
                                    </TableCell>
                                    <TableCell>
                                        <Badge type="priority" value={ticket.priority} />
                                    </TableCell>
                                    <TableCell className="text-sm text-foreground">
                                        {ticket.submitter?.name ?? ticket.submitter_name}
                                    </TableCell>
                                    <TableCell>
                                        {assigneeName !== null ? (
                                            <div className="flex items-center gap-2">
                                                <Avatar className="h-6 w-6">
                                                    <AvatarFallback className={`text-[10px] font-medium text-white ${avatarColor(assigneeName)}`}>
                                                        {getInitials(assigneeName)}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <span className="text-sm text-foreground">{assigneeName}</span>
                                            </div>
                                        ) : (
                                            <span className="text-sm italic text-muted-foreground">Unassigned</span>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-xs text-muted-foreground">
                                        {timeAgo(ticket.created_at)}
                                    </TableCell>
                                    <TableCell>
                                        <ChevronRight className="h-4 w-4 text-muted-foreground" />
                                    </TableCell>
                                </TableRow>
                            );
                        })}
                    </TableBody>
                </Table>
            </div>

            {/* Mobile cards */}
            <div className="divide-y divide-border rounded-lg border border-border lg:hidden">
                {tickets.map((ticket) => (
                    <button
                        key={ticket.id}
                        type="button"
                        onClick={() => onRowClick(ticket)}
                        className="block w-full space-y-3 px-4 py-4 text-left transition-colors hover:bg-accent/30"
                    >
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <p className="font-mono text-xs text-muted-foreground">{formatTicketId(ticket.id)}</p>
                                <p className="mt-1 text-sm font-medium text-foreground">{ticket.subject}</p>
                            </div>
                            <Badge type="status" value={ticket.status} />
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Badge type="priority" value={ticket.priority} />
                        </div>
                        <div className="grid gap-2 text-xs sm:grid-cols-3">
                            <div>
                                <p className="uppercase tracking-wide text-muted-foreground">Submitter</p>
                                <p className="mt-1 text-sm text-foreground">{ticket.submitter?.name ?? ticket.submitter_name}</p>
                            </div>
                            <div>
                                <p className="uppercase tracking-wide text-muted-foreground">Assignee</p>
                                <p className="mt-1 text-sm text-foreground">{ticket.assignee?.name ?? 'Unassigned'}</p>
                            </div>
                            <div>
                                <p className="uppercase tracking-wide text-muted-foreground">Created</p>
                                <p className="mt-1 text-sm text-foreground">{timeAgo(ticket.created_at)}</p>
                            </div>
                        </div>
                    </button>
                ))}
            </div>
        </>
    );
}

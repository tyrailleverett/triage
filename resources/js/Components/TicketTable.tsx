import Badge from '@/Components/Badge';
import type { Ticket } from '@/types';

interface TicketTableProps {
    tickets: Ticket[];
    onRowClick: (ticket: Ticket) => void;
}

export default function TicketTable({ tickets, onRowClick }: TicketTableProps): React.JSX.Element {
    if (tickets.length === 0) {
        return (
            <div className="flex h-32 items-center justify-center rounded-lg border border-white/10 bg-white/5 text-sm text-gray-500">
                No tickets found.
            </div>
        );
    }

    return (
        <div className="overflow-hidden rounded-lg border border-white/10">
            <div className="hidden overflow-x-auto lg:block">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b border-white/10 bg-white/5">
                            <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">ID</th>
                            <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Subject</th>
                            <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                            <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Priority</th>
                            <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Submitter</th>
                            <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Assignee</th>
                            <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Created</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-white/5">
                        {tickets.map((ticket) => (
                            <tr
                                key={ticket.id}
                                onClick={() => onRowClick(ticket)}
                                className="cursor-pointer transition-colors hover:bg-white/5"
                            >
                                <td className="px-4 py-3 text-gray-500">#{ticket.id}</td>
                                <td className="px-4 py-3 font-medium text-white">{ticket.subject}</td>
                                <td className="px-4 py-3">
                                    <Badge type="status" value={ticket.status} />
                                </td>
                                <td className="px-4 py-3">
                                    <Badge type="priority" value={ticket.priority} />
                                </td>
                                <td className="px-4 py-3 text-gray-400">{ticket.submitter?.name ?? ticket.submitter_name}</td>
                                <td className="px-4 py-3 text-gray-400">{ticket.assignee?.name ?? '—'}</td>
                                <td className="px-4 py-3 text-xs text-gray-600">
                                    {new Date(ticket.created_at).toLocaleDateString()}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="divide-y divide-white/5 lg:hidden">
                {tickets.map((ticket) => (
                    <button
                        key={ticket.id}
                        type="button"
                        onClick={() => onRowClick(ticket)}
                        className="block w-full space-y-3 px-4 py-4 text-left transition-colors hover:bg-white/5"
                    >
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <p className="text-xs uppercase tracking-wide text-gray-500">#{ticket.id}</p>
                                <p className="mt-1 text-sm font-medium text-white">{ticket.subject}</p>
                            </div>
                            <Badge type="status" value={ticket.status} />
                        </div>

                        <div className="flex flex-wrap gap-2">
                            <Badge type="priority" value={ticket.priority} />
                        </div>

                        <div className="grid gap-2 text-xs text-gray-400 sm:grid-cols-3">
                            <div>
                                <p className="uppercase tracking-wide text-gray-500">Submitter</p>
                                <p className="mt-1 text-sm text-gray-300">{ticket.submitter?.name ?? ticket.submitter_name}</p>
                            </div>
                            <div>
                                <p className="uppercase tracking-wide text-gray-500">Assignee</p>
                                <p className="mt-1 text-sm text-gray-300">{ticket.assignee?.name ?? 'Unassigned'}</p>
                            </div>
                            <div>
                                <p className="uppercase tracking-wide text-gray-500">Created</p>
                                <p className="mt-1 text-sm text-gray-300">{new Date(ticket.created_at).toLocaleDateString()}</p>
                            </div>
                        </div>
                    </button>
                ))}
            </div>
        </div>
    );
}

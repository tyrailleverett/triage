import { useCallback, useEffect, useRef, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { api } from '@/lib/api';
import type { ApiError } from '@/lib/api';
import type { PaginatedResponse, Ticket, TicketFilters, TicketPriority, TicketStatus } from '@/types';
import Pagination from '@/Components/Pagination';
import TicketTable from '@/Components/TicketTable';

export default function TicketsIndex(): React.JSX.Element {
    const [searchParams, setSearchParams] = useSearchParams();
    const navigate = useNavigate();
    const currentAgent = window.TriageConfig?.currentAgent ?? null;

    const [tickets, setTickets] = useState<PaginatedResponse<Ticket> | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [searchInputValue, setSearchInputValue] = useState(searchParams.get('search') ?? '');

    const debounceTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    const filters: TicketFilters = {
        status: (searchParams.get('status') as TicketStatus) || undefined,
        priority: (searchParams.get('priority') as TicketPriority) || undefined,
        search: searchParams.get('search') || undefined,
        page: Number(searchParams.get('page') || 1),
        assignee_id: searchParams.get('assignee_id') || undefined,
    };

    const fetchTickets = useCallback(async (currentFilters: TicketFilters): Promise<void> => {
        setIsLoading(true);
        setError(null);

        try {
            const params = new URLSearchParams();
            if (currentFilters.status) { params.set('status', currentFilters.status); }
            if (currentFilters.priority) { params.set('priority', currentFilters.priority); }
            if (currentFilters.search) { params.set('search', currentFilters.search); }
            if (currentFilters.assignee_id) { params.set('assignee_id', currentFilters.assignee_id); }
            if (currentFilters.page && currentFilters.page > 1) { params.set('page', String(currentFilters.page)); }

            const query = params.toString();
            const data = await api.get<PaginatedResponse<Ticket>>(`/tickets${query ? `?${query}` : ''}`);
            setTickets(data);
        } catch (err) {
            const apiErr = err as ApiError;
            setError(apiErr.message ?? 'Failed to load tickets.');
        } finally {
            setIsLoading(false);
        }
    }, []);

    useEffect(() => {
        void fetchTickets(filters);
    }, [searchParams]);

    useEffect(() => {
        setSearchInputValue(filters.search ?? '');
    }, [searchParams]);

    useEffect(() => {
        return () => {
            if (debounceTimerRef.current !== null) {
                clearTimeout(debounceTimerRef.current);
            }
        };
    }, []);

    const updateFilters = (updates: Partial<TicketFilters>): void => {
        const next = new URLSearchParams(searchParams);
        Object.entries(updates).forEach(([key, value]) => {
            if (value !== undefined && value !== '') {
                next.set(key, String(value));
            } else {
                next.delete(key);
            }
        });

        if (!Object.prototype.hasOwnProperty.call(updates, 'page')) {
            next.delete('page');
        }

        setSearchParams(next);
    };

    const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>): void => {
        const value = e.target.value;
        setSearchInputValue(value);

        if (debounceTimerRef.current) { clearTimeout(debounceTimerRef.current); }

        debounceTimerRef.current = setTimeout(() => {
            updateFilters({ search: value });
        }, 300);
    };

    const statusOptions: Array<{ label: string; value: TicketStatus | '' }> = [
        { label: 'All', value: '' },
        { label: 'Open', value: 'open' },
        { label: 'Pending', value: 'pending' },
        { label: 'Resolved', value: 'resolved' },
        { label: 'Closed', value: 'closed' },
    ];

    const priorityOptions: Array<{ label: string; value: TicketPriority | '' }> = [
        { label: 'Any Priority', value: '' },
        { label: 'Low', value: 'low' },
        { label: 'Normal', value: 'normal' },
        { label: 'High', value: 'high' },
        { label: 'Urgent', value: 'urgent' },
    ];

    const heading = currentAgent?.id !== undefined && filters.assignee_id === currentAgent.id
        ? 'My Queue'
        : 'All Tickets';

    return (
        <div className="p-4 md:p-6">
            <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h1 className="text-xl font-semibold text-white">{heading}</h1>
                <button
                    onClick={() => navigate('/tickets/create')}
                    className="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500"
                >
                    New Ticket
                </button>
            </div>

            <div className="mb-4 flex flex-col gap-3 xl:flex-row xl:items-center">
                <input
                    type="search"
                    placeholder="Search tickets…"
                    value={searchInputValue}
                    onChange={handleSearchChange}
                    className="w-full rounded-md border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-gray-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 xl:max-w-xs"
                />

                <div className="flex flex-wrap gap-1">
                    {statusOptions.map((opt) => (
                        <button
                            key={opt.value}
                            onClick={() => updateFilters({ status: opt.value || undefined })}
                            className={[
                                'rounded-full px-3 py-1 text-xs font-medium transition-colors',
                                (filters.status ?? '') === opt.value
                                    ? 'bg-white/20 text-white'
                                    : 'text-gray-400 hover:bg-white/10 hover:text-white',
                            ].join(' ')}
                        >
                            {opt.label}
                        </button>
                    ))}
                </div>

                <select
                    value={filters.priority ?? ''}
                    onChange={(e) => updateFilters({ priority: (e.target.value as TicketPriority) || undefined })}
                    className="rounded-md border border-white/10 bg-[#111318] px-3 py-2 text-sm text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 xl:min-w-44"
                >
                    {priorityOptions.map((option) => (
                        <option key={option.label} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>
            </div>

            {error !== null && (
                <div className="mb-4 rounded-md bg-red-900/50 px-4 py-3 text-sm text-red-300">
                    {error}
                </div>
            )}

            {isLoading ? (
                <div className="flex h-40 items-center justify-center text-gray-500">Loading…</div>
            ) : (
                <>
                    <TicketTable
                        tickets={tickets?.data ?? []}
                        onRowClick={(ticket) => navigate(`/tickets/${ticket.id}`)}
                    />
                    {tickets !== null && (
                        <div className="mt-4">
                            <Pagination
                                currentPage={tickets.meta.current_page}
                                lastPage={tickets.meta.last_page}
                                onPageChange={(page) => updateFilters({ page })}
                            />
                        </div>
                    )}
                </>
            )}
        </div>
    );
}

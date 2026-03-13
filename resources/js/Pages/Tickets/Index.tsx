import { useCallback, useEffect, useRef, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { ChevronDown, Plus, Search } from 'lucide-react';
import { api } from '@/lib/api';
import type { ApiError } from '@/lib/api';
import type { PaginatedResponse, Ticket, TicketFilters, TicketPriority, TicketStatus } from '@/types';
import Pagination from '@/Components/Pagination';
import TicketTable from '@/Components/TicketTable';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { cn } from '@/lib/utils';

export default function TicketsIndex(): React.JSX.Element {
    const [searchParams, setSearchParams] = useSearchParams();
    const navigate = useNavigate();
    const currentAgent = window.TriageConfig?.currentAgent ?? null;

    const [tickets, setTickets] = useState<PaginatedResponse<Ticket> | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [searchInputValue, setSearchInputValue] = useState(searchParams.get('search') ?? '');
    const [priorityOpen, setPriorityOpen] = useState(false);

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

    const statusTabs: Array<{ label: string; value: TicketStatus | '' }> = [
        { label: 'All', value: '' },
        { label: 'Open', value: 'open' },
        { label: 'Pending', value: 'pending' },
        { label: 'Resolved', value: 'resolved' },
        { label: 'Closed', value: 'closed' },
    ];

    const priorityOptions: Array<{ label: string; value: TicketPriority | '' }> = [
        { label: 'All priorities', value: '' },
        { label: 'Low', value: 'low' },
        { label: 'Normal', value: 'normal' },
        { label: 'High', value: 'high' },
        { label: 'Urgent', value: 'urgent' },
    ];

    const heading = currentAgent?.id !== undefined && filters.assignee_id === currentAgent.id
        ? 'My Queue'
        : 'Tickets';

    const totalCount = tickets?.meta.total ?? 0;

    const activePriorityLabel = priorityOptions.find((o) => o.value === (filters.priority ?? ''))?.label ?? 'All priorities';

    return (
        <div className="flex h-full flex-col">
            {/* Page header */}
            <div className="flex items-center justify-between border-b border-border px-6 py-4">
                <div>
                    <h1 className="text-xl font-semibold text-foreground">{heading}</h1>
                    <p className="mt-0.5 text-sm text-muted-foreground">
                        {isLoading ? '…' : `${totalCount} total ticket${totalCount !== 1 ? 's' : ''}`}
                    </p>
                </div>
                <Button
                    onClick={() => navigate('/tickets/create')}
                    className="gap-1.5"
                >
                    <Plus className="h-4 w-4" />
                    New Ticket
                </Button>
            </div>

            {/* Status tabs */}
            <div className="border-b border-border px-6">
                <div className="flex gap-0">
                    {statusTabs.map((tab) => {
                        const isActive = (filters.status ?? '') === tab.value;

                        return (
                            <button
                                key={tab.value}
                                onClick={() => updateFilters({ status: tab.value || undefined })}
                                className={cn(
                                    'relative px-4 py-3 text-sm font-medium transition-colors',
                                    isActive
                                        ? 'text-foreground after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:bg-primary'
                                        : 'text-muted-foreground hover:text-foreground',
                                )}
                            >
                                {tab.label}
                                {tab.value === '' && tickets !== null && (
                                    <span className="ml-1.5 rounded-full bg-primary/20 px-1.5 py-0.5 text-xs text-primary">
                                        {tickets.meta.total}
                                    </span>
                                )}
                            </button>
                        );
                    })}
                </div>
            </div>

            {/* Filters row */}
            <div className="flex flex-col gap-3 border-b border-border px-6 py-3 sm:flex-row sm:items-center">
                <div className="relative flex-1 sm:max-w-sm">
                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        type="search"
                        placeholder="Search tickets…"
                        value={searchInputValue}
                        onChange={handleSearchChange}
                        className="pl-9"
                    />
                </div>

                {/* Priority dropdown */}
                <div className="relative">
                    <button
                        onClick={() => setPriorityOpen((o) => !o)}
                        className="flex items-center gap-2 rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground transition-colors hover:bg-accent/50"
                    >
                        {activePriorityLabel}
                        <ChevronDown className="h-3.5 w-3.5 text-muted-foreground" />
                    </button>

                    {priorityOpen && (
                        <div className="absolute right-0 top-full z-50 mt-1 min-w-[160px] rounded-md border border-border bg-card py-1 shadow-lg">
                            {priorityOptions.map((option) => (
                                <button
                                    key={option.value}
                                    onClick={() => {
                                        updateFilters({ priority: (option.value as TicketPriority) || undefined });
                                        setPriorityOpen(false);
                                    }}
                                    className={cn(
                                        'block w-full px-3 py-1.5 text-left text-sm transition-colors hover:bg-accent/50',
                                        (filters.priority ?? '') === option.value
                                            ? 'text-foreground'
                                            : 'text-muted-foreground',
                                    )}
                                >
                                    {option.label}
                                </button>
                            ))}
                        </div>
                    )}
                </div>
            </div>

            {/* Content */}
            <div className="flex-1 overflow-auto p-6">
                {error !== null && (
                    <div className="mb-4 rounded-md bg-destructive/10 px-4 py-3 text-sm text-destructive">
                        {error}
                    </div>
                )}

                {isLoading ? (
                    <div className="flex h-40 items-center justify-center text-muted-foreground">Loading…</div>
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
        </div>
    );
}

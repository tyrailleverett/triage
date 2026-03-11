import { useCallback, useEffect, useRef, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { api } from '../../lib/api';
import type { ApiError } from '../../lib/api';
import type { PaginatedResponse, Ticket, TicketFilters, TicketPriority, TicketStatus } from '../../types';
import Badge from '../../Components/Badge';
import Pagination from '../../Components/Pagination';
import TicketTable from '../../Components/TicketTable';

export default function TicketsIndex(): React.JSX.Element {
  const [searchParams, setSearchParams] = useSearchParams();
  const navigate = useNavigate();

  const [tickets, setTickets] = useState<PaginatedResponse<Ticket> | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const searchInputRef = useRef<HTMLInputElement>(null);
  const debounceTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  const filters: TicketFilters = {
    status: (searchParams.get('status') as TicketStatus) || undefined,
    priority: (searchParams.get('priority') as TicketPriority) || undefined,
    search: searchParams.get('search') || undefined,
    page: Number(searchParams.get('page') || 1),
  };

  const fetchTickets = useCallback(async (currentFilters: TicketFilters): Promise<void> => {
    setIsLoading(true);
    setError(null);

    try {
      const params = new URLSearchParams();
      if (currentFilters.status) { params.set('status', currentFilters.status); }
      if (currentFilters.priority) { params.set('priority', currentFilters.priority); }
      if (currentFilters.search) { params.set('search', currentFilters.search); }
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

  const updateFilters = (updates: Partial<TicketFilters>): void => {
    const next = new URLSearchParams(searchParams);
    Object.entries(updates).forEach(([key, value]) => {
      if (value !== undefined && value !== '') {
        next.set(key, String(value));
      } else {
        next.delete(key);
      }
    });
    next.delete('page');
    setSearchParams(next);
  };

  const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>): void => {
    const value = e.target.value;
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

  return (
    <div className="p-6">
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-xl font-semibold text-white">All Tickets</h1>
        <button
          onClick={() => navigate('/tickets/create')}
          className="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-500"
        >
          New Ticket
        </button>
      </div>

      <div className="mb-4 flex items-center gap-3">
        <input
          ref={searchInputRef}
          type="search"
          placeholder="Search tickets…"
          defaultValue={filters.search ?? ''}
          onChange={handleSearchChange}
          className="w-64 rounded-md border border-white/10 bg-white/5 px-3 py-1.5 text-sm text-white placeholder:text-gray-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        />

        <div className="flex gap-1">
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

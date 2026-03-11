import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { api } from '../../lib/api';
import type { ApiError } from '../../lib/api';
import type { Ticket, TicketPriority } from '../../types';

interface CreateTicketResponse {
  data: Ticket;
}

export default function TicketCreate(): React.JSX.Element {
  const navigate = useNavigate();

  const [subject, setSubject] = useState('');
  const [body, setBody] = useState('');
  const [priority, setPriority] = useState<TicketPriority>('normal');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [validationErrors, setValidationErrors] = useState<Record<string, string[]>>({});

  const handleSubmit = async (e: React.FormEvent): Promise<void> => {
    e.preventDefault();
    setIsSubmitting(true);
    setValidationErrors({});

    try {
      const response = await api.post<CreateTicketResponse>('/tickets', {
        subject,
        body,
        priority,
      });
      navigate(`/tickets/${response.data.id}`);
    } catch (err) {
      const apiErr = err as ApiError;
      if (apiErr.status === 422 && apiErr.errors) {
        setValidationErrors(apiErr.errors);
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  const fieldError = (field: string): string | null =>
    validationErrors[field]?.[0] ?? null;

  const priorityOptions: Array<{ label: string; value: TicketPriority }> = [
    { label: 'Low', value: 'low' },
    { label: 'Normal', value: 'normal' },
    { label: 'High', value: 'high' },
    { label: 'Urgent', value: 'urgent' },
  ];

  return (
    <div className="mx-auto max-w-2xl p-6">
      <h1 className="mb-6 text-xl font-semibold text-white">New Ticket</h1>

      <form onSubmit={handleSubmit} className="space-y-4">
        <div>
          <label htmlFor="subject" className="mb-1 block text-sm font-medium text-gray-300">
            Subject
          </label>
          <input
            id="subject"
            type="text"
            value={subject}
            onChange={(e) => setSubject(e.target.value)}
            className="w-full rounded-md border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-gray-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            placeholder="Enter ticket subject"
          />
          {fieldError('subject') !== null && (
            <p className="mt-1 text-xs text-red-400">{fieldError('subject')}</p>
          )}
        </div>

        <div>
          <label htmlFor="body" className="mb-1 block text-sm font-medium text-gray-300">
            Description
          </label>
          <textarea
            id="body"
            value={body}
            onChange={(e) => setBody(e.target.value)}
            rows={6}
            className="w-full rounded-md border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-gray-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            placeholder="Describe the issue…"
          />
          {fieldError('body') !== null && (
            <p className="mt-1 text-xs text-red-400">{fieldError('body')}</p>
          )}
        </div>

        <div>
          <label htmlFor="priority" className="mb-1 block text-sm font-medium text-gray-300">
            Priority
          </label>
          <select
            id="priority"
            value={priority}
            onChange={(e) => setPriority(e.target.value as TicketPriority)}
            className="w-full rounded-md border border-white/10 bg-[#111318] px-3 py-2 text-sm text-white focus:outline-none focus:ring-1 focus:ring-indigo-500"
          >
            {priorityOptions.map((opt) => (
              <option key={opt.value} value={opt.value}>
                {opt.label}
              </option>
            ))}
          </select>
        </div>

        <div className="flex items-center justify-end gap-3 pt-2">
          <button
            type="button"
            onClick={() => navigate('/tickets')}
            className="rounded-md px-3 py-2 text-sm font-medium text-gray-400 hover:text-white"
          >
            Cancel
          </button>
          <button
            type="submit"
            disabled={isSubmitting}
            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50"
          >
            {isSubmitting ? 'Creating…' : 'Create Ticket'}
          </button>
        </div>
      </form>
    </div>
  );
}

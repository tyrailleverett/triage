import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { ChevronLeft, Plus } from 'lucide-react';
import { api } from '@/lib/api';
import type { ApiError } from '@/lib/api';
import type { Ticket, TicketPriority } from '@/types';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

interface CreateTicketResponse {
    data: Ticket;
}

export default function TicketCreate(): React.JSX.Element {
    const navigate = useNavigate();

    const [subject, setSubject] = useState('');
    const [body, setBody] = useState('');
    const [priority, setPriority] = useState<TicketPriority>('normal');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [validationErrors, setValidationErrors] = useState<Record<string, string[]>>({});

    const handleSubmit = async (e: React.FormEvent): Promise<void> => {
        e.preventDefault();
        setIsSubmitting(true);
        setError(null);
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
            } else {
                setError(apiErr.message ?? 'Failed to create ticket.');
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
        <div className="flex h-full flex-col">
            {/* Page header */}
            <div className="border-b border-border px-6 py-4">
                <button
                    type="button"
                    onClick={() => navigate('/tickets')}
                    className="mb-1 inline-flex items-center gap-1 text-xs text-muted-foreground transition-colors hover:text-foreground"
                >
                    <ChevronLeft className="h-3 w-3" />
                    All Tickets
                </button>
                <h1 className="text-xl font-semibold text-foreground">New Ticket</h1>
            </div>

            <div className="flex-1 overflow-auto p-6">
                <div className="mx-auto max-w-2xl">
                    <form onSubmit={handleSubmit} className="space-y-5">
                        {error !== null && (
                            <div className="rounded-md border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                                {error}
                            </div>
                        )}

                        <div>
                            <label htmlFor="subject" className="mb-1.5 block text-sm font-medium text-foreground">
                                Subject
                            </label>
                            <Input
                                id="subject"
                                type="text"
                                value={subject}
                                onChange={(e) => setSubject(e.target.value)}
                                placeholder="Enter ticket subject"
                            />
                            {fieldError('subject') !== null && (
                                <p className="mt-1.5 text-xs text-destructive">{fieldError('subject')}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="body" className="mb-1.5 block text-sm font-medium text-foreground">
                                Description
                            </label>
                            <textarea
                                id="body"
                                value={body}
                                onChange={(e) => setBody(e.target.value)}
                                rows={6}
                                className="w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                placeholder="Describe the issue…"
                            />
                            {fieldError('body') !== null && (
                                <p className="mt-1.5 text-xs text-destructive">{fieldError('body')}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="priority" className="mb-1.5 block text-sm font-medium text-foreground">
                                Priority
                            </label>
                            <select
                                id="priority"
                                value={priority}
                                onChange={(e) => setPriority(e.target.value as TicketPriority)}
                                className="w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                            >
                                {priorityOptions.map((opt) => (
                                    <option key={opt.value} value={opt.value}>
                                        {opt.label}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="flex items-center justify-end gap-3 pt-2">
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={() => navigate('/tickets')}
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={isSubmitting}
                                className="gap-1.5"
                            >
                                <Plus className="h-4 w-4" />
                                {isSubmitting ? 'Creating…' : 'Create Ticket'}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}

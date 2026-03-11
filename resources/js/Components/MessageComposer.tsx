import { useState } from 'react';
import type { ApiError } from '../lib/api';

interface MessageComposerProps {
    label: string;
    variant?: 'reply' | 'note';
    onSubmit: (body: string) => Promise<void>;
}

export default function MessageComposer({ label, variant = 'reply', onSubmit }: MessageComposerProps): React.JSX.Element {
    const [body, setBody] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [isOpen, setIsOpen] = useState(false);

    const handleSubmit = async (e: React.FormEvent): Promise<void> => {
        e.preventDefault();
        if (body.trim() === '') { return; }

        setIsSubmitting(true);
        setError(null);

        try {
            await onSubmit(body.trim());
            setBody('');
            setIsOpen(false);
        } catch (err) {
            const apiErr = err as ApiError;
            setError(apiErr.message ?? 'Failed to submit.');
        } finally {
            setIsSubmitting(false);
        }
    };

    if (!isOpen) {
        return (
            <button
                onClick={() => setIsOpen(true)}
                className={[
                    'text-xs font-medium transition-colors',
                    variant === 'note'
                        ? 'text-yellow-500 hover:text-yellow-400'
                        : 'text-indigo-400 hover:text-indigo-300',
                ].join(' ')}
            >
                + {label}
            </button>
        );
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-2">
            <textarea
                value={body}
                onChange={(e) => setBody(e.target.value)}
                rows={3}
                autoFocus
                placeholder={variant === 'note' ? 'Add an internal note…' : 'Write a reply…'}
                className={[
                    'w-full rounded-md border px-3 py-2 text-sm text-white placeholder:text-gray-500 focus:outline-none focus:ring-1',
                    variant === 'note'
                        ? 'border-yellow-800/50 bg-yellow-900/20 focus:ring-yellow-500'
                        : 'border-white/10 bg-white/5 focus:ring-indigo-500',
                ].join(' ')}
            />
            {error !== null && (
                <p className="text-xs text-red-400">{error}</p>
            )}
            <div className="flex items-center gap-2">
                <button
                    type="submit"
                    disabled={isSubmitting || body.trim() === ''}
                    className="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-500 disabled:opacity-50"
                >
                    {isSubmitting ? 'Sending…' : label}
                </button>
                <button
                    type="button"
                    onClick={() => { setIsOpen(false); setBody(''); setError(null); }}
                    className="text-xs text-gray-500 hover:text-gray-300"
                >
                    Cancel
                </button>
            </div>
        </form>
    );
}

import { useState } from 'react';
import { FileText, Send } from 'lucide-react';
import type { ApiError } from '@/lib/api';
import { Button } from '@/Components/ui/button';
import { cn } from '@/lib/utils';

interface MessageComposerProps {
    onReplySubmit: (body: string) => Promise<void>;
    onNoteSubmit: (body: string) => Promise<void>;
}

type ActiveTab = 'reply' | 'note';

export default function MessageComposer({ onReplySubmit, onNoteSubmit }: MessageComposerProps): React.JSX.Element {
    const [activeTab, setActiveTab] = useState<ActiveTab>('reply');
    const [body, setBody] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const handleSubmit = async (): Promise<void> => {
        if (body.trim() === '') { return; }

        setIsSubmitting(true);
        setError(null);

        try {
            if (activeTab === 'reply') {
                await onReplySubmit(body.trim());
            } else {
                await onNoteSubmit(body.trim());
            }

            setBody('');
        } catch (err) {
            const apiErr = err as ApiError;
            setError(apiErr.message ?? 'Failed to submit.');
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div className="rounded-lg border border-border bg-card">
            {/* Tabs */}
            <div className="flex border-b border-border">
                <button
                    type="button"
                    onClick={() => setActiveTab('reply')}
                    className={cn(
                        'flex items-center gap-2 px-4 py-2.5 text-sm font-medium transition-colors',
                        activeTab === 'reply'
                            ? 'border-b-2 border-primary text-foreground'
                            : 'text-muted-foreground hover:text-foreground',
                    )}
                >
                    <Send className="h-3.5 w-3.5" />
                    Reply
                </button>
                <button
                    type="button"
                    onClick={() => setActiveTab('note')}
                    className={cn(
                        'flex items-center gap-2 px-4 py-2.5 text-sm font-medium transition-colors',
                        activeTab === 'note'
                            ? 'border-b-2 border-yellow-500 text-foreground'
                            : 'text-muted-foreground hover:text-foreground',
                    )}
                >
                    <FileText className="h-3.5 w-3.5" />
                    Internal Note
                </button>
            </div>

            {/* Textarea */}
            <textarea
                value={body}
                onChange={(e) => setBody(e.target.value)}
                rows={4}
                placeholder={
                    activeTab === 'reply'
                        ? 'Write a reply to the customer…'
                        : 'Add an internal note…'
                }
                className={cn(
                    'w-full resize-none bg-transparent px-4 py-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none',
                    activeTab === 'note' && 'text-yellow-300 placeholder:text-yellow-900',
                )}
            />

            {error !== null && (
                <p className="px-4 pb-2 text-xs text-destructive">{error}</p>
            )}

            {/* Footer */}
            <div className="flex items-center justify-between border-t border-border px-4 py-2.5">
                <p className="text-xs text-muted-foreground">
                    {activeTab === 'reply' ? 'This will be sent to the customer' : 'Only visible to agents'}
                </p>
                <Button
                    onClick={() => void handleSubmit()}
                    disabled={isSubmitting || body.trim() === ''}
                    size="sm"
                    className={cn(
                        'gap-1.5',
                        activeTab === 'note' && 'bg-yellow-600 hover:bg-yellow-500 text-white',
                    )}
                >
                    <Send className="h-3.5 w-3.5" />
                    {isSubmitting ? 'Sending…' : activeTab === 'reply' ? 'Send Reply' : 'Add Note'}
                </Button>
            </div>
        </div>
    );
}

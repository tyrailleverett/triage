import { cn } from '@/lib/utils';
import type { TicketPriority, TicketStatus } from '@/types';

interface StatusBadgeProps {
    type: 'status';
    value: TicketStatus;
}

interface PriorityBadgeProps {
    type: 'priority';
    value: TicketPriority;
}

type BadgeProps = StatusBadgeProps | PriorityBadgeProps;

const statusConfig: Record<TicketStatus, { dot: string; pill: string; label: string }> = {
    open: {
        dot: 'bg-green-500',
        pill: 'border-green-500/30 bg-green-500/10 text-green-400',
        label: 'Open',
    },
    pending: {
        dot: 'bg-yellow-500',
        pill: 'border-yellow-500/30 bg-yellow-500/10 text-yellow-400',
        label: 'Pending',
    },
    resolved: {
        dot: 'bg-blue-500',
        pill: 'border-blue-500/30 bg-blue-500/10 text-blue-400',
        label: 'Resolved',
    },
    closed: {
        dot: 'bg-gray-500',
        pill: 'border-gray-500/30 bg-gray-500/10 text-gray-400',
        label: 'Closed',
    },
};

const priorityConfig: Record<TicketPriority, { pill: string; label: string }> = {
    urgent: {
        pill: 'border-red-500/40 bg-red-500/10 text-red-400',
        label: 'Urgent',
    },
    high: {
        pill: 'border-orange-500/40 bg-orange-500/10 text-orange-400',
        label: 'High',
    },
    normal: {
        pill: 'border-blue-500/40 bg-blue-500/10 text-blue-400',
        label: 'Normal',
    },
    low: {
        pill: 'border-gray-500/40 bg-gray-500/10 text-gray-400',
        label: 'Low',
    },
};

export default function Badge(props: BadgeProps): React.JSX.Element {
    if (props.type === 'status') {
        const config = statusConfig[props.value];

        return (
            <span
                className={cn(
                    'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium',
                    config.pill,
                )}
            >
                <span className={cn('h-1.5 w-1.5 rounded-full', config.dot)} />
                {config.label}
            </span>
        );
    }

    const config = priorityConfig[props.value];

    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium',
                config.pill,
            )}
        >
            {config.label}
        </span>
    );
}

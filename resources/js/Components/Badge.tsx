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

const statusClasses: Record<TicketStatus, string> = {
    open: 'border border-green-500 text-green-400',
    pending: 'border border-yellow-500 text-yellow-400',
    resolved: 'border border-blue-500 text-blue-400',
    closed: 'border border-gray-500 text-gray-400',
};

const priorityClasses: Record<TicketPriority, string> = {
    urgent: 'bg-red-600 text-white',
    high: 'bg-orange-600 text-white',
    normal: 'border border-blue-500 text-blue-400',
    low: 'border border-gray-500 text-gray-400',
};

const capitalize = (s: string): string =>
    s.charAt(0).toUpperCase() + s.slice(1);

export default function Badge(props: BadgeProps): React.JSX.Element {
    const className =
        props.type === 'status'
            ? statusClasses[props.value]
            : priorityClasses[props.value];

    return (
        <span
            className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${className}`}
        >
            {capitalize(props.value)}
        </span>
    );
}

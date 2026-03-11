export type TicketStatus = 'open' | 'pending' | 'resolved' | 'closed';

export type TicketPriority = 'low' | 'normal' | 'high' | 'urgent';

export type MessageDirection = 'inbound' | 'outbound';

export interface User {
    id: number;
    name: string;
    email: string;
}

export interface Ticket {
    id: number;
    subject: string;
    status: TicketStatus;
    priority: TicketPriority;
    submitter: User | null;
    assignee: User | null;
    created_at: string;
    updated_at: string;
}

export interface TicketMessage {
    id: number;
    ticket_id: number;
    body: string;
    direction: MessageDirection;
    author: User | null;
    created_at: string;
}

export interface TicketNote {
    id: number;
    ticket_id: number;
    body: string;
    author: User | null;
    created_at: string;
}

export interface PaginatedResponse<T> {
    data: T[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
}

export interface TicketFilters {
    status?: TicketStatus;
    priority?: TicketPriority;
    search?: string;
    page?: number;
    assignee_id?: string;
}

export interface AgentPreferences {
    notify_ticket_assigned: boolean;
    notify_ticket_replied: boolean;
    notify_note_added: boolean;
    notify_status_changed: boolean;
    daily_digest: boolean;
    email_notifications: boolean;
}

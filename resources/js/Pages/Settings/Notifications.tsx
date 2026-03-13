import { useEffect, useState } from 'react';
import { Save } from 'lucide-react';
import { api } from '@/lib/api';
import type { ApiError } from '@/lib/api';
import type { AgentPreferences } from '@/types';
import SettingsNav from '@/Components/SettingsNav';
import { Button } from '@/Components/ui/button';
import { Switch } from '@/Components/ui/switch';

interface NotificationsResponse {
    data: AgentPreferences;
}

interface NotificationSetting {
    key: keyof AgentPreferences;
    label: string;
    description: string;
}

type NotificationFieldErrors = Partial<Record<keyof AgentPreferences, string[]>>;

const notificationSettings: NotificationSetting[] = [
    {
        key: 'notify_ticket_assigned',
        label: 'New ticket assigned',
        description: 'Get notified when a ticket is assigned to you',
    },
    {
        key: 'notify_ticket_replied',
        label: 'Ticket replied',
        description: 'Get notified when a customer replies to your ticket',
    },
    {
        key: 'notify_note_added',
        label: 'Internal note added',
        description: 'Get notified when a teammate adds an internal note',
    },
    {
        key: 'notify_status_changed',
        label: 'Status changed',
        description: 'Get notified when a ticket status is updated',
    },
    {
        key: 'daily_digest',
        label: 'Daily digest',
        description: 'Receive a daily summary of your queue activity',
    },
    {
        key: 'email_notifications',
        label: 'Email notifications',
        description: 'Send notifications to your email address',
    },
];

export default function Notifications(): React.JSX.Element {
    const [preferences, setPreferences] = useState<AgentPreferences | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [isSaving, setIsSaving] = useState(false);
    const [saveSuccess, setSaveSuccess] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [fieldErrors, setFieldErrors] = useState<NotificationFieldErrors>({});

    useEffect(() => {
        const load = async (): Promise<void> => {
            setIsLoading(true);

            try {
                const data = await api.get<NotificationsResponse>('/settings/notifications');
                setPreferences(data.data);
                setFieldErrors({});
            } catch (err) {
                const apiErr = err as ApiError;
                setError(apiErr.message ?? 'Failed to load settings.');
            } finally {
                setIsLoading(false);
            }
        };

        void load();
    }, []);

    const handleToggle = (key: keyof AgentPreferences, value: boolean): void => {
        if (preferences === null) { return; }

        setPreferences({ ...preferences, [key]: value });
        setSaveSuccess(false);
        setFieldErrors((currentErrors) => ({
            ...currentErrors,
            [key]: undefined,
        }));
    };

    const handleSave = async (): Promise<void> => {
        if (preferences === null) { return; }

        setIsSaving(true);
        setError(null);
        setSaveSuccess(false);
        setFieldErrors({});

        try {
            const data = await api.patch<NotificationsResponse>('/settings/notifications', preferences);
            setPreferences(data.data);
            setSaveSuccess(true);
        } catch (err) {
            const apiErr = err as ApiError;

            if (apiErr.status === 422) {
                setError(apiErr.message ?? 'Please correct the highlighted fields.');
                setFieldErrors((apiErr.errors as NotificationFieldErrors | undefined) ?? {});
            } else {
                setError(apiErr.message ?? 'Failed to save settings.');
            }
        } finally {
            setIsSaving(false);
        }
    };

    return (
        <div className="flex h-full flex-col">
            {/* Page header */}
            <div className="flex items-center justify-between border-b border-border px-6 py-4">
                <div>
                    <h1 className="text-xl font-semibold text-foreground">Settings</h1>
                    <p className="mt-0.5 text-sm text-muted-foreground">
                        Manage your account and workspace preferences
                    </p>
                </div>
                <Button
                    onClick={() => void handleSave()}
                    disabled={isSaving || isLoading}
                    className="gap-1.5"
                >
                    <Save className="h-4 w-4" />
                    {isSaving ? 'Saving…' : 'Save Changes'}
                </Button>
            </div>

            {saveSuccess && (
                <div className="mx-6 mt-4 rounded-md border border-green-800/50 bg-green-950/50 px-4 py-3 text-sm text-green-400">
                    Preferences saved.
                </div>
            )}

            {error !== null && (
                <div className="mx-6 mt-4 rounded-md border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                    {error}
                </div>
            )}

            <div className="flex flex-1 flex-col overflow-hidden md:flex-row">
                <SettingsNav active="notifications" />

                <div className="flex-1 overflow-y-auto p-6">
                    {isLoading ? (
                        <div className="flex h-40 items-center justify-center text-muted-foreground">
                            Loading…
                        </div>
                    ) : (
                        <div className="rounded-lg border border-border bg-card">
                            <div className="border-b border-border px-5 py-4">
                                <h2 className="text-base font-semibold text-foreground">Notification Preferences</h2>
                            </div>
                            <div className="divide-y divide-border">
                                {notificationSettings.map((setting) => (
                                    <div
                                        key={setting.key}
                                        className="flex items-center justify-between px-5 py-4"
                                    >
                                        <div className="flex-1 pr-6">
                                            <p className="text-sm font-medium text-foreground">{setting.label}</p>
                                            <p className="mt-0.5 text-xs text-muted-foreground">{setting.description}</p>
                                            {fieldErrors[setting.key] !== undefined && (
                                                <p className="mt-1.5 text-xs text-destructive">{fieldErrors[setting.key]?.[0]}</p>
                                            )}
                                        </div>
                                        <Switch
                                            checked={preferences?.[setting.key] ?? false}
                                            onCheckedChange={(value) => handleToggle(setting.key, value)}
                                            disabled={isSaving}
                                            aria-label={setting.label}
                                        />
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

import { useEffect, useState } from 'react';
import { api } from '@/lib/api';
import type { ApiError } from '@/lib/api';
import type { AgentPreferences } from '@/types';
import SettingsNav from '@/Components/SettingsNav';
import Toggle from '@/Components/Toggle';

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
                <div className="flex flex-col gap-3 border-b border-white/10 px-4 py-4 md:flex-row md:items-center md:justify-between md:px-6">
                    <div>
                        <h1 className="text-xl font-semibold text-white">Settings</h1>
                        <p className="mt-0.5 text-sm text-gray-400">
                        Manage your account and workspace preferences
                    </p>
                </div>
                <button
                    type="button"
                    onClick={() => void handleSave()}
                    disabled={isSaving || isLoading}
                    className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {isSaving ? 'Saving…' : 'Save Changes'}
                </button>
            </div>

            {saveSuccess && (
                <div className="mx-6 mt-4 rounded-md bg-green-900/50 px-4 py-3 text-sm text-green-300">
                    Preferences saved.
                </div>
            )}

            {error !== null && (
                <div className="mx-6 mt-4 rounded-md bg-red-900/50 px-4 py-3 text-sm text-red-300">
                    {error}
                </div>
            )}

                <div className="flex flex-1 flex-col overflow-hidden md:flex-row">
                    <SettingsNav active="notifications" />

                    <div className="flex-1 overflow-y-auto p-4 md:p-6">
                        {isLoading ? (
                            <div className="flex h-40 items-center justify-center text-gray-500">
                                Loading…
                        </div>
                    ) : (
                        <div className="rounded-lg border border-white/10 bg-white/5">
                            <div className="border-b border-white/10 px-5 py-4">
                                <h2 className="text-base font-semibold text-white">Notification Preferences</h2>
                            </div>
                            <div className="divide-y divide-white/5">
                                {notificationSettings.map((setting) => (
                                    <div key={setting.key} className="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <p className="text-sm font-medium text-white">{setting.label}</p>
                                            <p className="mt-0.5 text-xs text-gray-500">{setting.description}</p>
                                            {fieldErrors[setting.key] !== undefined && (
                                                <p className="mt-2 text-xs text-red-300">{fieldErrors[setting.key]?.[0]}</p>
                                            )}
                                        </div>
                                        <Toggle
                                            checked={preferences?.[setting.key] ?? false}
                                            onChange={(value) => handleToggle(setting.key, value)}
                                            disabled={isSaving}
                                            label={setting.label}
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

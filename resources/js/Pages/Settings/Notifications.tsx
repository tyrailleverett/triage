import { useEffect, useState } from 'react';
import { api } from '../../lib/api';
import type { ApiError } from '../../lib/api';
import type { AgentPreferences } from '../../types';
import SettingsNav from '../../Components/SettingsNav';
import Toggle from '../../Components/Toggle';

interface NotificationsResponse {
  data: AgentPreferences;
}

interface NotificationSetting {
  key: keyof AgentPreferences;
  label: string;
  description: string;
}

const notificationSettings: NotificationSetting[] = [
  {
    key: 'notify_on_new_ticket',
    label: 'New Tickets',
    description: 'Receive a notification when a new ticket is submitted.',
  },
  {
    key: 'notify_on_reply',
    label: 'Replies',
    description: 'Receive a notification when a customer replies to a ticket.',
  },
  {
    key: 'notify_on_assignment',
    label: 'Assignments',
    description: 'Receive a notification when a ticket is assigned to you.',
  },
  {
    key: 'notify_on_status_change',
    label: 'Status Changes',
    description: 'Receive a notification when a ticket status changes.',
  },
];

export default function Notifications(): React.JSX.Element {
  const [preferences, setPreferences] = useState<AgentPreferences | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [savedAt, setSavedAt] = useState<Date | null>(null);

  useEffect(() => {
    const load = async (): Promise<void> => {
      setIsLoading(true);
      try {
        const data = await api.get<NotificationsResponse>('/settings/notifications');
        setPreferences(data.data);
      } catch (err) {
        const apiErr = err as ApiError;
        setError(apiErr.message ?? 'Failed to load settings.');
      } finally {
        setIsLoading(false);
      }
    };

    void load();
  }, []);

  const handleToggle = async (key: keyof AgentPreferences, value: boolean): Promise<void> => {
    if (preferences === null) { return; }

    const updated = { ...preferences, [key]: value };
    setPreferences(updated);
    setIsSaving(true);
    setError(null);

    try {
      await api.patch('/settings/notifications', updated);
      setSavedAt(new Date());
    } catch (err) {
      const apiErr = err as ApiError;
      setError(apiErr.message ?? 'Failed to save settings.');
      setPreferences(preferences);
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <div className="flex h-full">
      <SettingsNav />

      <div className="flex-1 p-6">
        <div className="mb-6 flex items-center justify-between">
          <div>
            <h1 className="text-xl font-semibold text-white">Notifications</h1>
            <p className="mt-1 text-sm text-gray-400">
              Manage your notification preferences.
            </p>
          </div>
          {isSaving && (
            <span className="text-xs text-gray-500">Saving…</span>
          )}
          {!isSaving && savedAt !== null && (
            <span className="text-xs text-gray-600">
              Saved at {savedAt.toLocaleTimeString()}
            </span>
          )}
        </div>

        {error !== null && (
          <div className="mb-4 rounded-md bg-red-900/50 px-4 py-3 text-sm text-red-300">
            {error}
          </div>
        )}

        {isLoading ? (
          <div className="flex h-40 items-center justify-center text-gray-500">
            Loading…
          </div>
        ) : (
          <div className="space-y-4">
            {notificationSettings.map((setting) => (
              <div
                key={setting.key}
                className="flex items-center justify-between rounded-lg border border-white/10 bg-white/5 px-5 py-4"
              >
                <div>
                  <p className="text-sm font-medium text-white">{setting.label}</p>
                  <p className="mt-0.5 text-xs text-gray-500">{setting.description}</p>
                </div>
                <Toggle
                  checked={preferences?.[setting.key] ?? false}
                  onChange={(value) => void handleToggle(setting.key, value)}
                  disabled={isSaving}
                />
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}

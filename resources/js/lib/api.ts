export interface ApiError {
    message: string;
    errors?: Record<string, string[]>;
    status: number;
}

function getApiBasePath(): string {
    return window.TriageConfig?.apiBasePath ?? '/triage/api';
}

function getCsrfToken(): string {
    return window.TriageConfig?.csrfToken ?? '';
}

async function request<T>(
    method: string,
    path: string,
    body?: unknown,
): Promise<T> {
    const isMutating = ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method.toUpperCase());

    const headers: Record<string, string> = {
        Accept: 'application/json',
    };

    if (isMutating) {
        headers['Content-Type'] = 'application/json';
        headers['X-CSRF-TOKEN'] = getCsrfToken();
    }

    let response: Response;

    try {
        response = await fetch(`${getApiBasePath()}${path}`, {
            method: method.toUpperCase(),
            headers,
            body: isMutating && body !== undefined ? JSON.stringify(body) : undefined,
        });
    } catch {
        throw {
            message: 'Network request failed.',
            status: 0,
        } satisfies ApiError;
    }

    if (!response.ok) {
        const json = await response.json().catch(() => ({ message: response.statusText }));

        const error: ApiError = {
            message: json.message ?? response.statusText,
            status: response.status,
            errors: json.errors,
        };

        throw error;
    }

    if (response.status === 204) {
        return undefined as T;
    }

    return response.json() as Promise<T>;
}

export const api = {
    get: <T>(path: string): Promise<T> => request<T>('GET', path),
    post: <T>(path: string, body: unknown): Promise<T> => request<T>('POST', path, body),
    patch: <T>(path: string, body: unknown): Promise<T> => request<T>('PATCH', path, body),
    delete: <T>(path: string): Promise<T> => request<T>('DELETE', path),
};

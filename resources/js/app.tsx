import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import '../css/app.css';
import ErrorBoundary from './Components/ErrorBoundary';
import AppRoutes from './routes';

declare global {
    interface Window {
        TriageConfig: {
            dashboardPath: string;
            apiBasePath: string;
            csrfToken: string;
            currentAgent?: {
                id: string;
                name: string;
                email: string;
                role: string;
            } | null;
        };
    }
}

const rootElement = document.getElementById('triage-app');

if (rootElement) {
    const basePath = window.TriageConfig?.dashboardPath ?? '/triage';

    createRoot(rootElement).render(
        <ErrorBoundary>
            <BrowserRouter basename={basePath}>
                <AppRoutes />
            </BrowserRouter>
        </ErrorBoundary>,
    );
}

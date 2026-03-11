import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import '../css/app.css';
import AppRoutes from './routes';

declare global {
  interface Window {
    TriageConfig: {
      dashboardPath: string;
      apiBasePath: string;
      csrfToken: string;
    };
  }
}

const rootElement = document.getElementById('triage-app');

if (rootElement) {
  const basePath = window.TriageConfig?.dashboardPath ?? '/triage';

  createRoot(rootElement).render(
    <BrowserRouter basename={basePath}>
      <AppRoutes />
    </BrowserRouter>,
  );
}

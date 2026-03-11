import { Navigate, Route, Routes } from 'react-router-dom';
import TriageLayout from './Layouts/TriageLayout';
import TicketsIndex from './Pages/Tickets/Index';
import TicketShow from './Pages/Tickets/Show';
import TicketCreate from './Pages/Tickets/Create';
import Notifications from './Pages/Settings/Notifications';

export default function AppRoutes(): React.JSX.Element {
  return (
    <Routes>
      <Route element={<TriageLayout />}>
        <Route index element={<TicketsIndex />} />
        <Route path="tickets" element={<TicketsIndex />} />
        <Route path="tickets/create" element={<TicketCreate />} />
        <Route path="tickets/:ticketId" element={<TicketShow />} />
        <Route path="settings" element={<Navigate to="/settings/notifications" replace />} />
        <Route path="settings/notifications" element={<Notifications />} />
      </Route>
    </Routes>
  );
}

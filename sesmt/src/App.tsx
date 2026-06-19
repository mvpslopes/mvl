import { Navigate, Route, Routes } from 'react-router-dom';
import RequireAuth from './routes/RequireAuth';
import LoginPage from './pages/LoginPage';
import DashboardPage from './pages/DashboardPage';
import UsersPage from './pages/UsersPage';
import CertificadosListaPage from './pages/CertificadosListaPage';
import CertificadosNovoPage from './pages/CertificadosNovoPage';
import CertificadosPage from './pages/CertificadosPage';
import EmpresasPage from './pages/EmpresasPage';

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route
        path="/"
        element={
          <RequireAuth>
            <DashboardPage />
          </RequireAuth>
        }
      />
      <Route
        path="/certificados"
        element={
          <RequireAuth>
            <CertificadosListaPage />
          </RequireAuth>
        }
      />
      <Route
        path="/certificados/novo"
        element={
          <RequireAuth>
            <CertificadosNovoPage />
          </RequireAuth>
        }
      />
      <Route
        path="/certificados/:id/editar"
        element={
          <RequireAuth>
            <CertificadosPage />
          </RequireAuth>
        }
      />
      <Route
        path="/empresas"
        element={
          <RequireAuth>
            <EmpresasPage />
          </RequireAuth>
        }
      />
      <Route
        path="/usuarios"
        element={
          <RequireAuth requiredRole="root">
            <UsersPage />
          </RequireAuth>
        }
      />
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}

import { Navigate, Route, Routes } from 'react-router-dom';
import Dashboard from './components/Dashboard';
import Login from './components/Login';
import HomePage from './pages/HomePage';
import RequireAuth from './routes/RequireAuth';
import ThemeScope from './routes/ThemeScope';

function App() {
  return (
    <Routes>
      <Route path="/" element={<HomePage />} />
      <Route
        path="/login"
        element={
          <ThemeScope theme="dark">
            <Login />
          </ThemeScope>
        }
      />
      <Route
        path="/dashboard"
        element={
          <ThemeScope theme="dark">
            <RequireAuth requiredRole="root">
              <Dashboard />
            </RequireAuth>
          </ThemeScope>
        }
      />
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}

export default App;

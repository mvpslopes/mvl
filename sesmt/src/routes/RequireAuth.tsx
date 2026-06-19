import type { ReactNode } from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { getRole, getToken, type UserRole } from '../lib/auth';

type Props = {
  children: ReactNode;
  requiredRole?: UserRole;
};

export default function RequireAuth({ children, requiredRole }: Props) {
  const location = useLocation();
  const token = getToken();
  const role = getRole();

  if (!token) {
    return <Navigate to="/login" replace state={{ from: location.pathname }} />;
  }

  if (requiredRole && role !== requiredRole) {
    return <Navigate to="/" replace />;
  }

  return <>{children}</>;
}

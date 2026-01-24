import type { ReactNode } from 'react';
import { Navigate, useLocation } from 'react-router-dom';

type RequireAuthProps = {
  children: ReactNode;
  requiredRole?: string;
};

function getStored(key: string) {
  return localStorage.getItem(key) || sessionStorage.getItem(key);
}

export default function RequireAuth({ children, requiredRole }: RequireAuthProps) {
  const location = useLocation();

  const token = getStored('auth_token');
  const role = getStored('user_role');

  const hasToken = Boolean(token && token.trim().length > 0);
  const roleOk = requiredRole ? role === requiredRole : true;

  if (!hasToken || !roleOk) {
    return <Navigate to="/login" replace state={{ from: location.pathname }} />;
  }

  return <>{children}</>;
}


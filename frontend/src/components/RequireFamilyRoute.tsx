import { Navigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import type { ReactNode } from 'react';

export default function RequireFamilyRoute({ children }: { children: ReactNode }) {
  const { user } = useAuth();

  if (!user?.family_id) {
    return <Navigate to="/family/create" replace />;
  }

  return <>{children}</>;
}

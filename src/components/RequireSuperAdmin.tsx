import { Navigate, useLocation } from "react-router-dom";
import { getSuperAdminToken } from "@/lib/api";

export default function RequireSuperAdmin({ children }: { children: React.ReactNode }) {
  const location = useLocation();
  const token = getSuperAdminToken();

  if (!token) {
    return <Navigate to="/super-admin/login" state={{ from: location }} replace />;
  }

  return <>{children}</>;
}

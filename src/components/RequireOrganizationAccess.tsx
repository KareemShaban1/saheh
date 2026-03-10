import { Navigate, useLocation } from "react-router-dom";
import { getOrganizationToken, getOrganizationUser } from "@/lib/api";
import {
  hasOrganizationAccess,
  normalizeOrganizationGuard,
  type OrganizationGuard,
} from "@/lib/organizationAccess";

type RequireOrganizationAccessProps = {
  children: React.ReactNode;
  allowedGuards: OrganizationGuard[];
  requiredPermissions?: string[];
  permissionPrefixes?: string[];
};

const DASHBOARD_HOME: Record<OrganizationGuard, string> = {
  clinic: "/clinic-dashboard",
  medical_laboratory: "/lab-dashboard",
  radiology_center: "/radiology-dashboard",
};

export default function RequireOrganizationAccess({
  children,
  allowedGuards,
  requiredPermissions,
  permissionPrefixes,
}: RequireOrganizationAccessProps) {
  const location = useLocation();
  const token = getOrganizationToken();
  const user = (getOrganizationUser() as Record<string, unknown> | null) ?? null;

  if (!token) {
    return <Navigate to="/dashboard-login" state={{ from: location }} replace />;
  }

  const guard = normalizeOrganizationGuard(user?.organization_guard ?? user?.organization_type);
  if (!guard || !allowedGuards.includes(guard)) {
    const fallback = guard ? DASHBOARD_HOME[guard] : "/dashboard-login";
    return <Navigate to={fallback} replace />;
  }

  if (!hasOrganizationAccess(user, { requiredPermissions, permissionPrefixes })) {
    return <Navigate to={DASHBOARD_HOME[guard]} replace />;
  }

  return <>{children}</>;
}

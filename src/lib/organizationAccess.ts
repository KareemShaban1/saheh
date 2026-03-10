type OrganizationGuard = "clinic" | "medical_laboratory" | "radiology_center";

type PermissionInput = {
  requiredPermissions?: string[];
  permissionPrefixes?: string[];
};

function toLower(value: string): string {
  return value.trim().toLowerCase();
}

export function normalizeOrganizationGuard(value: unknown): OrganizationGuard | null {
  if (typeof value !== "string") return null;
  const v = value.trim();

  if (v === "clinic" || v.endsWith("\\Clinic") || v.endsWith("/Clinic")) return "clinic";
  if (
    v === "medical_laboratory" ||
    v === "medicalLaboratory" ||
    v.endsWith("\\MedicalLaboratory") ||
    v.endsWith("/MedicalLaboratory")
  ) {
    return "medical_laboratory";
  }
  if (
    v === "radiology_center" ||
    v === "radiologyCenter" ||
    v.endsWith("\\RadiologyCenter") ||
    v.endsWith("/RadiologyCenter")
  ) {
    return "radiology_center";
  }
  return null;
}

export function extractPermissions(user: Record<string, unknown> | null): string[] {
  if (!user) return [];

  const raw = (user.permissions ?? user.effective_permissions ?? []) as unknown;
  if (!Array.isArray(raw)) return [];

  return raw
    .map((entry) => {
      if (typeof entry === "string") return entry;
      if (entry && typeof entry === "object" && "name" in entry && typeof entry.name === "string") {
        return entry.name;
      }
      return "";
    })
    .filter(Boolean);
}

export function hasOrganizationAccess(
  user: Record<string, unknown> | null,
  opts: PermissionInput,
): boolean {
  const requiredPermissions = opts.requiredPermissions ?? [];
  const permissionPrefixes = opts.permissionPrefixes ?? [];
  if (requiredPermissions.length === 0 && permissionPrefixes.length === 0) return true;

  const permissions = extractPermissions(user).map(toLower);
  // Backward compatible mode if user payload has no permissions yet.
  if (permissions.length === 0) return true;

  if (permissions.includes("*") || permissions.includes("all")) return true;

  const required = requiredPermissions.map(toLower);
  if (required.some((permission) => permissions.includes(permission))) return true;

  const prefixes = permissionPrefixes.map(toLower);
  if (
    prefixes.some((prefix) =>
      permissions.some((permission) => {
        return (
          permission === prefix ||
          permission.startsWith(prefix + ".") ||
          permission.startsWith(prefix + ":") ||
          permission.startsWith("manage " + prefix) ||
          permission.includes(" " + prefix)
        );
      }),
    )
  ) {
    return true;
  }

  return false;
}

export { type OrganizationGuard };

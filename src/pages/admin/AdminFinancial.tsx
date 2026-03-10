import FinancialModulePage from "@/pages/shared/FinancialModulePage";
import { adminApi } from "@/lib/api";

export default function AdminFinancial() {
  return (
    <FinancialModulePage
      title="Financial Module"
      description="Financial overview across clinics, laboratories, and radiology centers."
      queryKeyBase={["admin", "financial"]}
      queryFn={(months) => adminApi.financial({ months: String(months) })}
    />
  );
}

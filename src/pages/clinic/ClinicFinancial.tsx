import FinancialModulePage from "@/pages/shared/FinancialModulePage";
import { clinicApi } from "@/lib/api";

export default function ClinicFinancial() {
  return (
    <FinancialModulePage
      title="Clinic Financial"
      description="Revenue, due amounts, and payment trends for this clinic."
      queryKeyBase={["clinic", "financial"]}
      queryFn={(months) => clinicApi.financial({ months: String(months) })}
    />
  );
}

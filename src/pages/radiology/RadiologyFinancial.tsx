import FinancialModulePage from "@/pages/shared/FinancialModulePage";
import { radiologyApi } from "@/lib/api";

export default function RadiologyFinancial() {
  return (
    <FinancialModulePage
      title="Radiology Financial"
      description="Revenue, due amounts, and payment trends for this radiology center."
      queryKeyBase={["radiology", "financial"]}
      queryFn={(months) => radiologyApi.financial({ months: String(months) })}
    />
  );
}

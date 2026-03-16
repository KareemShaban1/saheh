import FinancialModulePage from "@/pages/shared/FinancialModulePage";
import { clinicApi } from "@/lib/api";
import { useLanguage } from "@/contexts/LanguageContext";

export default function ClinicFinancial() {
  const { t } = useLanguage();
  return (
    <FinancialModulePage
      title={t("financial.title")}
      description={t("financial.description")}
      queryKeyBase={["clinic", "financial"]}
      queryFn={(months) => clinicApi.financial({ months: String(months) })}
    />
  );
}

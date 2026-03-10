import FinancialModulePage from "@/pages/shared/FinancialModulePage";
import { labApi } from "@/lib/api";
import { useLanguage } from "@/contexts/LanguageContext";


export default function LabFinancial() {
 const { t } = useLanguage();	
  return (
    <FinancialModulePage
      title={t('lab.financial.title')}
      description={t('lab.financial.description')}
      queryKeyBase={["lab", "financial"]}
      queryFn={(months) => labApi.financial({ months: String(months) })}
    />
  );
}

import OrganizationQuestionnairesPage from "@/pages/shared/OrganizationQuestionnairesPage";
import { useLanguage } from "@/contexts/LanguageContext";
export default function ClinicQuestionnaires() {
	const { t } = useLanguage();
	return (
		<OrganizationQuestionnairesPage
			scope="clinic"
			title={t("clinic.questionnaires")}
		/>
	);
}


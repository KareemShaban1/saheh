import { useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Edit, Eye, Plus, Search, Trash2, Users } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Badge } from "@/components/ui/badge";
import { useToast } from "@/hooks/use-toast";
import { clinicApi, labApi, radiologyApi, type QuestionnaireInput, type QuestionnaireQuestionType } from "@/lib/api";
import { useLanguage } from "@/contexts/LanguageContext";

type Scope = "clinic" | "lab" | "radiology";

type QuestionnaireListRow = {
	id: number | string;
	title?: string;
	description?: string | null;
	is_active?: boolean;
	questions_count?: number;
	created_at?: string;
};

type QuestionnaireQuestion = {
	id?: number | string;
	question_text: string;
	question_type: QuestionnaireQuestionType;
	is_required?: boolean;
	sort_order?: number;
	placeholder?: string | null;
	options?: string[];
	meta?: Record<string, unknown>;
};

type QuestionnaireDetails = {
	id: number | string;
	title?: string;
	description?: string | null;
	is_active?: boolean;
	questions?: QuestionnaireQuestion[];
};

type EditableQuestion = {
	question_text: string;
	question_type: QuestionnaireQuestionType;
	is_required: boolean;
	sort_order: number;
	placeholder: string;
	optionsText: string;
};

type Props = {
	scope: Scope;
	title: string;
	description: string;
};

const QUESTION_TYPES: Array<{ value: QuestionnaireQuestionType; label: string }> = [
	{ value: "short_text", label: "Short text" },
	{ value: "long_text", label: "Long text" },
	{ value: "number", label: "Number" },
	{ value: "boolean", label: "Yes / No" },
	{ value: "date", label: "Date" },
	{ value: "single_choice", label: "Single choice" },
	{ value: "multiple_choice", label: "Multiple choice" },
];

function toEditableQuestion(question: QuestionnaireQuestion, index: number): EditableQuestion {
	return {
		question_text: String(question.question_text ?? ""),
		question_type: question.question_type ?? "short_text",
		is_required: Boolean(question.is_required),
		sort_order: Number(question.sort_order ?? index + 1),
		placeholder: String(question.placeholder ?? ""),
		optionsText: Array.isArray(question.options) ? question.options.join(", ") : "",
	};
}

export default function OrganizationQuestionnairesPage({ scope, title, description }: Props) {
	const queryClient = useQueryClient();
	const { toast } = useToast();
	const { t } = useLanguage();
	const [search, setSearch] = useState("");
	const [dialogMode, setDialogMode] = useState<"add" | "edit" | "show" | null>(null);
	const [activeId, setActiveId] = useState<string>("");
	const [answersOpen, setAnswersOpen] = useState(false);
	const [answersQuestionnaireId, setAnswersQuestionnaireId] = useState<string>("");
	const [form, setForm] = useState({
		title: "",
		description: "",
		is_active: true,
		questions: [{ question_text: "", question_type: "short_text" as QuestionnaireQuestionType, is_required: false, sort_order: 1, placeholder: "", optionsText: "" }],
	});

	const api = useMemo(() => {
		if (scope === "clinic") return clinicApi;
		if (scope === "lab") return labApi;
		return radiologyApi;
	}, [scope]);

	const questionnairesQuery = useQuery({
		queryKey: [scope, "questionnaires"],
		queryFn: () => api.questionnaires(),
	});

	const questionnaireDetailsQuery = useQuery({
		queryKey: [scope, "questionnaire", activeId],
		queryFn: () => api.questionnaire(activeId),
		enabled: Boolean(activeId) && dialogMode !== "add",
	});

	const answersQuery = useQuery({
		queryKey: [scope, "questionnaire-answers", answersQuestionnaireId],
		queryFn: () => api.questionnaireAnswers(answersQuestionnaireId),
		enabled: Boolean(answersQuestionnaireId) && answersOpen,
	});

	const rows = useMemo<QuestionnaireListRow[]>(() => {
		const root = (questionnairesQuery.data as { data?: unknown })?.data ?? questionnairesQuery.data;
		const data = (root as { data?: unknown })?.data ?? root;
		return Array.isArray(data) ? (data as QuestionnaireListRow[]) : [];
	}, [questionnairesQuery.data]);

	const filtered = useMemo(
		() => rows.filter((row) => `${row.title ?? ""} ${row.description ?? ""}`.toLowerCase().includes(search.toLowerCase())),
		[rows, search],
	);

	const details = useMemo<QuestionnaireDetails | null>(() => {
		const root = (questionnaireDetailsQuery.data as { data?: unknown })?.data ?? questionnaireDetailsQuery.data;
		const data = (root as { data?: unknown })?.data ?? root;
		if (!data || typeof data !== "object") return null;
		return data as QuestionnaireDetails;
	}, [questionnaireDetailsQuery.data]);

	const answersRows = useMemo<Array<{ patient?: { id?: number; name?: string; phone?: string }; answers?: Array<Record<string, unknown>> }>>(() => {
		const root = (answersQuery.data as { data?: unknown })?.data ?? answersQuery.data;
		const data = (root as { data?: unknown })?.data ?? root;
		const rowsData = (data as { answers?: unknown })?.answers;
		return Array.isArray(rowsData)
			? (rowsData as Array<{ patient?: { id?: number; name?: string; phone?: string }; answers?: Array<Record<string, unknown>> }>)
			: [];
	}, [answersQuery.data]);

	const createMutation = useMutation({
		mutationFn: (payload: QuestionnaireInput) => api.createQuestionnaire(payload),
		onSuccess: async () => {
			await queryClient.invalidateQueries({ queryKey: [scope, "questionnaires"] });
			toast({ title: t("clinic.questionnaires.questionnaire_created") });
			setDialogMode(null);
		},
		onError: (e) => toast({ title: e instanceof Error ? e.message : "Failed to create questionnaire", variant: "destructive" }),
	});

	const updateMutation = useMutation({
		mutationFn: (payload: QuestionnaireInput) => api.updateQuestionnaire(activeId, payload),
		onSuccess: async () => {
			await queryClient.invalidateQueries({ queryKey: [scope, "questionnaires"] });
			toast({ title: t("clinic.questionnaires.questionnaire_updated") });
			setDialogMode(null);
		},
		onError: (e) => toast({ title: e instanceof Error ? e.message : "Failed to update questionnaire", variant: "destructive" }),
	});

	const deleteMutation = useMutation({
		mutationFn: (id: string) => api.deleteQuestionnaire(id),
		onSuccess: async () => {
			await queryClient.invalidateQueries({ queryKey: [scope, "questionnaires"] });
			toast({ title: t("clinic.questionnaires.questionnaire_deleted") });
		},
		onError: (e) => toast({ title: e instanceof Error ? e.message : t("clinic.questionnaires.failed_to_delete_questionnaire"), variant: "destructive" }),
	});

	const openAdd = () => {
		setActiveId("");
		setForm({
			title: "",
			description: "",
			is_active: true,
			questions: [{ question_text: "", question_type: "short_text", is_required: false, sort_order: 1, placeholder: "", optionsText: "" }],
		});
		setDialogMode("add");
	};

	const openShowOrEdit = async (row: QuestionnaireListRow, mode: "show" | "edit") => {
		const id = String(row.id);
		setActiveId(id);
		setDialogMode(mode);
		const res = await api.questionnaire(id);
		const root = (res as { data?: unknown })?.data ?? res;
		const data = ((root as { data?: unknown })?.data ?? root) as QuestionnaireDetails;

		setForm({
			title: String(data?.title ?? ""),
			description: String(data?.description ?? ""),
			is_active: Boolean(data?.is_active ?? true),
			questions: (data?.questions ?? []).map(toEditableQuestion).length > 0
				? (data?.questions ?? []).map(toEditableQuestion)
				: [{ question_text: "", question_type: "short_text", is_required: false, sort_order: 1, placeholder: "", optionsText: "" }],
		});
	};

	const addQuestion = () => {
		setForm((prev) => ({
			...prev,
			questions: [...prev.questions, { question_text: "", question_type: "short_text", is_required: false, sort_order: prev.questions.length + 1, placeholder: "", optionsText: "" }],
		}));
	};

	const removeQuestion = (index: number) => {
		setForm((prev) => ({
			...prev,
			questions: prev.questions.filter((_, i) => i !== index).map((q, i) => ({ ...q, sort_order: i + 1 })),
		}));
	};

	const onSave = () => {
		if (!form.title.trim()) {
			toast({ title: t("clinic.questionnaires.title_is_required"), variant: "destructive" });
			return;
		}
		if (form.questions.length === 0) {
			toast({ title: t("clinic.questionnaires.at_least_one_question_is_required"), variant: "destructive" });
			return;
		}
		for (const question of form.questions) {
			if (!question.question_text.trim()) {
				toast({ title: t("clinic.questionnaires.all_question_texts_are_required"), variant: "destructive" });
				return;
			}
			if ((question.question_type === "single_choice" || question.question_type === "multiple_choice") && !question.optionsText.trim()) {
				toast({ title: t("clinic.questionnaires.choice_questions_require_options"), variant: "destructive" });
				return;
			}
		}

		const payload: QuestionnaireInput = {
			title: form.title.trim(),
			description: form.description.trim() || undefined,
			is_active: form.is_active,
			questions: form.questions.map((question, index) => ({
				question_text: question.question_text.trim(),
				question_type: question.question_type,
				is_required: question.is_required,
				sort_order: question.sort_order || index + 1,
				placeholder: question.placeholder.trim() || undefined,
				options:
					question.question_type === "single_choice" || question.question_type === "multiple_choice"
						? question.optionsText.split(",").map((item) => item.trim()).filter(Boolean)
						: undefined,
			})),
		};

		if (dialogMode === "add") {
			createMutation.mutate(payload);
			return;
		}
		updateMutation.mutate(payload);
	};

	return (
		<div>
			<div className="mb-6 flex items-center justify-between gap-3">
				<div>
					<h2 className="text-2xl font-bold">{title}</h2>
				</div>
				<Button onClick={openAdd} className="gradient-primary text-primary-foreground border-0 gap-2">
					<Plus className="h-4 w-4" />
					{t("clinic.questionnaires.add_questionnaire")}
				</Button>
			</div>

			<div className="relative max-w-sm mb-4">
				<Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
				<Input value={search} onChange={(e) => setSearch(e.target.value)} className="pl-10" placeholder={t("clinic.questionnaires.search_questionnaires")} />
			</div>

			<div className="bg-card rounded-xl border shadow-card overflow-hidden">
				<div className="overflow-x-auto">
					<table className="w-full text-sm">
						<thead>
							<tr className="border-b bg-muted/50">
								<th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.questionnaires.title")}</th>
								<th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.questionnaires.questions")}</th>
								<th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.questionnaires.status")}</th>
								<th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.questionnaires.actions")}</th>
							</tr>
						</thead>
						<tbody className="divide-y">
							{questionnairesQuery.isLoading && (
								<tr>
									<td className="p-4 text-muted-foreground" colSpan={4}>{t("clinic.questionnaires.loading_questionnaires")}</td>
								</tr>
							)}
							{questionnairesQuery.error && (
								<tr>
									<td className="p-4 text-destructive" colSpan={4}>
										{questionnairesQuery.error instanceof Error ? questionnairesQuery.error.message : "Failed to load questionnaires"}
									</td>
								</tr>
							)}
							{!questionnairesQuery.isLoading && !questionnairesQuery.error && filtered.length === 0 && (
								<tr>
									<td className="p-4 text-muted-foreground" colSpan={4}>No questionnaires found.</td>
								</tr>
							)}
							{filtered.map((row) => (
								<tr key={String(row.id)} className="hover:bg-muted/30 transition-colors">
									<td className="p-4">
										<p className="font-medium">{row.title ?? `Questionnaire #${row.id}`}</p>
										{row.description && <p className="text-xs text-muted-foreground mt-1">{row.description}</p>}
									</td>
									<td className="p-4 text-muted-foreground">{row.questions_count ?? 0}</td>
									<td className="p-4">
										<Badge variant="outline" className={row.is_active ? "bg-success/10 text-success border-success/20" : "bg-muted text-muted-foreground"}>
											{row.is_active ? t("clinic.questionnaires.active") : t("clinic.questionnaires.inactive")}
										</Badge>
									</td>
									<td className="p-4">
										<div className="flex flex-wrap gap-2">
											<Button variant="outline" size="sm" className="gap-2" onClick={() => void openShowOrEdit(row, "show")}>
												<Eye className="h-4 w-4" />
												{t("clinic.questionnaires.show")}
											</Button>
											<Button variant="outline" size="sm" className="gap-2" onClick={() => void openShowOrEdit(row, "edit")}>
												<Edit className="h-4 w-4" />
												{t("clinic.questionnaires.edit")}
											</Button>
											<Button
												variant="outline"
												size="sm"
												className="gap-2"
												onClick={() => {
													setAnswersQuestionnaireId(String(row.id));
													setAnswersOpen(true);
												}}
											>
												<Users className="h-4 w-4" />
												{t("clinic.questionnaires.answers")}
											</Button>
											<Button
												variant="outline"
												size="sm"
												className="gap-2"
												disabled={deleteMutation.isPending}
												onClick={() => {
													if (!window.confirm(`Delete "${row.title ?? row.id}"?`)) return;
													deleteMutation.mutate(String(row.id));
												}}
											>
												<Trash2 className="h-4 w-4" />
												{t("clinic.questionnaires.delete")}
											</Button>
										</div>
									</td>
								</tr>
							))}
						</tbody>
					</table>
				</div>
			</div>

			<Dialog open={dialogMode !== null} onOpenChange={(open) => !open && setDialogMode(null)}>
				<DialogContent className="sm:max-w-4xl max-h-[90vh] overflow-y-auto">
					<DialogHeader>
						<DialogTitle>
							{dialogMode === "add" ? t("clinic.questionnaires.add_questionnaire") : dialogMode === "edit" ? t("clinic.questionnaires.edit_questionnaire") : t("clinic.questionnaires.questionnaire_details")}
						</DialogTitle>
					</DialogHeader>

					{dialogMode !== "add" && questionnaireDetailsQuery.isLoading ? (
						<p className="text-sm text-muted-foreground">{t("clinic.questionnaires.loading_questionnaire")}</p>
					) : (
						<div className="space-y-4">
							<div className="grid sm:grid-cols-2 gap-3">
								<div className="space-y-1">
									<Label>{t("clinic.questionnaires.title")}</Label>
									<Input
										value={form.title}
										disabled={dialogMode === "show"}
										onChange={(e) => setForm((prev) => ({ ...prev, title: e.target.value }))}
									/>
								</div>
								<div className="space-y-1 flex items-end pb-2">
									<label className="flex items-center gap-2 text-sm">
										<input
											type="checkbox"
											checked={form.is_active}
											disabled={dialogMode === "show"}
											onChange={(e) => setForm((prev) => ({ ...prev, is_active: e.target.checked }))}
										/>
										{t("clinic.questionnaires.active")}
									</label>
								</div>
							</div>
							<div className="space-y-1">
								<Label>{t("clinic.questionnaires.description")}</Label>
								<Textarea
									rows={2}
									value={form.description}
									disabled={dialogMode === "show"}
									onChange={(e) => setForm((prev) => ({ ...prev, description: e.target.value }))}
								/>
							</div>

							<div className="space-y-2">
								<div className="flex items-center justify-between">
									<Label>{t("clinic.questionnaires.questions")}</Label>
									{dialogMode !== "show" && (
										<Button type="button" variant="outline" size="sm" onClick={addQuestion}>
											{t("clinic.questionnaires.add_question")}
										</Button>
									)}
								</div>
								{form.questions.map((question, index) => (
									<div key={`${index}-${question.sort_order}`} className="border rounded-lg p-3 space-y-2">
										<div className="grid lg:grid-cols-6 gap-2 items-end">
											<div className="space-y-1 lg:col-span-2">
												<Label>{t("clinic.questionnaires.question")}</Label>
												<Input
													value={question.question_text}
													disabled={dialogMode === "show"}
													onChange={(e) =>
														setForm((prev) => ({
															...prev,
															questions: prev.questions.map((item, i) => (i === index ? { ...item, question_text: e.target.value } : item)),
														}))
													}
												/>
											</div>
											<div className="space-y-1">
												<Label>{t("clinic.questionnaires.type")}</Label>
												<select
													title="Question type"
													className="w-full rounded-md border bg-background px-3 py-2 text-sm"
													disabled={dialogMode === "show"}
													value={question.question_type}
													onChange={(e) =>
														setForm((prev) => ({
															...prev,
															questions: prev.questions.map((item, i) =>
																i === index ? { ...item, question_type: e.target.value as QuestionnaireQuestionType } : item,
															),
														}))
													}
												>
													{QUESTION_TYPES.map((item) => (
														<option key={item.value} value={item.value}>{item.label}</option>
													))}
												</select>
											</div>
											<div className="space-y-1">
												<Label>{t("clinic.questionnaires.sort")}</Label>
												<Input
													type="number"
													value={question.sort_order}
													disabled={dialogMode === "show"}
													onChange={(e) =>
														setForm((prev) => ({
															...prev,
															questions: prev.questions.map((item, i) =>
																i === index ? { ...item, sort_order: Number(e.target.value || index + 1) } : item,
															),
														}))
													}
												/>
											</div>
											<div className="space-y-1">
												<Label>{t("clinic.questionnaires.placeholder")}</Label>
												<Input
													value={question.placeholder}
													disabled={dialogMode === "show"}
													onChange={(e) =>
														setForm((prev) => ({
															...prev,
															questions: prev.questions.map((item, i) => (i === index ? { ...item, placeholder: e.target.value } : item)),
														}))
													}
												/>
											</div>
											<div className="flex items-center gap-2">
												<label className="flex items-center gap-2 text-sm">
													<input
														type="checkbox"
														disabled={dialogMode === "show"}
														checked={question.is_required}
														onChange={(e) =>
															setForm((prev) => ({
																...prev,
																questions: prev.questions.map((item, i) =>
																	i === index ? { ...item, is_required: e.target.checked } : item,
																),
															}))
														}
													/>
													{t("clinic.questionnaires.required")}
												</label>
												{dialogMode !== "show" && (
													<Button type="button" size="sm" variant="ghost" onClick={() => removeQuestion(index)} disabled={form.questions.length <= 1}>
														<Trash2 className="h-4 w-4 text-destructive" />
													</Button>
												)}
											</div>
										</div>
										{(question.question_type === "single_choice" || question.question_type === "multiple_choice") && (
											<div className="space-y-1">
												<Label>{t("clinic.questionnaires.options_comma_separated")}</Label>
												<Input
													value={question.optionsText}
													disabled={dialogMode === "show"}
													onChange={(e) =>
														setForm((prev) => ({
															...prev,
															questions: prev.questions.map((item, i) => (i === index ? { ...item, optionsText: e.target.value } : item)),
														}))
													}
												/>
											</div>
										)}
									</div>
								))}
							</div>
						</div>
					)}

					<DialogFooter>
						<Button variant="outline" onClick={() => setDialogMode(null)}>{t("clinic.questionnaires.close")}</Button>
						{dialogMode !== "show" && (
							<Button
								onClick={onSave}
								disabled={createMutation.isPending || updateMutation.isPending}
								className="gradient-primary text-primary-foreground border-0"
							>
								{createMutation.isPending || updateMutation.isPending ? t("clinic.questionnaires.saving") : t("clinic.questionnaires.save")}
							</Button>
						)}
					</DialogFooter>
				</DialogContent>
			</Dialog>

			<Dialog open={answersOpen} onOpenChange={setAnswersOpen}>
				<DialogContent className="sm:max-w-3xl max-h-[90vh] overflow-y-auto">
					<DialogHeader>
						<DialogTitle>{t("clinic.questionnaires.questionnaire_answers")}</DialogTitle>
					</DialogHeader>
					{answersQuery.isLoading ? (
						<p className="text-sm text-muted-foreground">{t("clinic.questionnaires.loading_answers")}</p>
					) : answersRows.length === 0 ? (
						<p className="text-sm text-muted-foreground">{t("clinic.questionnaires.no_answers_yet")}</p>
					) : (
						<div className="space-y-3">
							{answersRows.map((row, idx) => (
								<div key={`${row.patient?.id ?? "p"}-${idx}`} className="border rounded-lg p-3 space-y-2">
									<p className="text-sm font-medium">
										{row.patient?.name ?? t("clinic.questionnaires.unknown_patient")} {row.patient?.phone ? `- ${row.patient.phone}` : ""}
									</p>
									<div className="space-y-2">
										{(row.answers ?? []).map((answer, answerIdx) => (
											<div key={String(answer.question_id ?? answerIdx)} className="bg-muted/40 rounded p-2">
												<p className="text-xs font-medium">{String(answer.question_text ?? t("clinic.questionnaires.question"))}</p>
												<p className="text-xs text-muted-foreground mt-1">
													{answer.answer_text != null
														? String(answer.answer_text)
														: answer.answer_number != null
															? String(answer.answer_number)
															: answer.answer_boolean != null
																? (answer.answer_boolean ? t("clinic.questionnaires.yes") : t("clinic.questionnaires.no"))
																: answer.answer_date != null
																	? String(answer.answer_date)
																	: Array.isArray(answer.answer_json)
																		? answer.answer_json.join(", ")
																		: t("clinic.questionnaires.unknown")}
												</p>
											</div>
										))}
									</div>
								</div>
							))}
						</div>
					)}
					<DialogFooter>
						<Button variant="outline" onClick={() => setAnswersOpen(false)}>{t("clinic.questionnaires.close")}</Button>
					</DialogFooter>
				</DialogContent>
			</Dialog>
		</div>
	);
}


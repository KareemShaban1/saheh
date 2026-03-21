import { useMemo, useState } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import { FileText, Send } from "lucide-react";
import { useAuth } from "@/contexts/AuthContext";
import { patientApi } from "@/lib/api";
import { useToast } from "@/hooks/use-toast";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Badge } from "@/components/ui/badge";

type OrganizationType = "clinic" | "medical_laboratory" | "radiology_center";

type OrgOption = {
	id: number | string;
	name?: string;
};

type QuestionRow = {
	id: number;
	question_text: string;
	question_type: "short_text" | "long_text" | "number" | "boolean" | "date" | "single_choice" | "multiple_choice";
	is_required?: boolean;
	placeholder?: string | null;
	options?: string[];
};

type QuestionnaireRow = {
	id: number;
	title: string;
	description?: string | null;
	questions: QuestionRow[];
};

type AnswerDraft = {
	answer_text?: string;
	answer_number?: string;
	answer_boolean?: boolean;
	answer_date?: string;
	answer_json?: string[];
};

export default function PatientQuestionnaires() {
	const { token } = useAuth();
	const { toast } = useToast();

	const [organizationType, setOrganizationType] = useState<OrganizationType>("clinic");
	const [organizationId, setOrganizationId] = useState<string>("");
	const [activeQuestionnaireId, setActiveQuestionnaireId] = useState<string>("");
	const [answers, setAnswers] = useState<Record<string, AnswerDraft>>({});

	const clinicsQuery = useQuery({
		queryKey: ["patient", "questionnaires", "clinics", token],
		queryFn: () => patientApi.clinics(token!),
		enabled: Boolean(token),
	});
	const labsQuery = useQuery({
		queryKey: ["patient", "questionnaires", "labs", token],
		queryFn: () => patientApi.medicalLabs(token!),
		enabled: Boolean(token),
	});
	const radiologyQuery = useQuery({
		queryKey: ["patient", "questionnaires", "radiology", token],
		queryFn: () => patientApi.radiologyCenters(token!),
		enabled: Boolean(token),
	});

	const questionnairesQuery = useQuery({
		queryKey: ["patient", "questionnaires", token, organizationType, organizationId],
		queryFn: () =>
			patientApi.questionnaires(token!, {
				organization_type: organizationType,
				organization_id: organizationId,
			}),
		enabled: Boolean(token) && Boolean(organizationId),
	});

	const myAnswersQuery = useQuery({
		queryKey: ["patient", "questionnaire-answers", token, activeQuestionnaireId],
		queryFn: () => patientApi.questionnaireAnswers(token!, activeQuestionnaireId),
		enabled: Boolean(token) && Boolean(activeQuestionnaireId),
	});

	const organizations = useMemo<OrgOption[]>(() => {
		const source =
			organizationType === "clinic" ? clinicsQuery.data : organizationType === "medical_laboratory" ? labsQuery.data : radiologyQuery.data;
		const root = (source as { data?: unknown })?.data ?? source;
		const data = (root as { data?: unknown })?.data ?? root;
		return Array.isArray(data) ? (data as OrgOption[]) : [];
	}, [organizationType, clinicsQuery.data, labsQuery.data, radiologyQuery.data]);

	const questionnaires = useMemo<QuestionnaireRow[]>(() => {
		const root = (questionnairesQuery.data as { data?: unknown })?.data ?? questionnairesQuery.data;
		const data = (root as { data?: unknown })?.data ?? root;
		return Array.isArray(data) ? (data as QuestionnaireRow[]) : [];
	}, [questionnairesQuery.data]);

	const activeQuestionnaire = useMemo(
		() => questionnaires.find((item) => String(item.id) === activeQuestionnaireId) ?? null,
		[questionnaires, activeQuestionnaireId],
	);

	const hydratedAnswers = useMemo<Record<string, AnswerDraft>>(() => {
		const root = (myAnswersQuery.data as { data?: unknown })?.data ?? myAnswersQuery.data;
		const data = (root as { data?: unknown })?.data ?? root;
		const rows = Array.isArray((data as { answers?: unknown[] })?.answers) ? ((data as { answers: unknown[] }).answers as Array<Record<string, unknown>>) : [];
		const map: Record<string, AnswerDraft> = {};
		for (const row of rows) {
			const questionId = String(row.question_id ?? "");
			if (!questionId) continue;
			map[questionId] = {
				answer_text: row.answer_text != null ? String(row.answer_text) : undefined,
				answer_number: row.answer_number != null ? String(row.answer_number) : undefined,
				answer_boolean: typeof row.answer_boolean === "boolean" ? row.answer_boolean : undefined,
				answer_date: row.answer_date != null ? String(row.answer_date) : undefined,
				answer_json: Array.isArray(row.answer_json) ? (row.answer_json as string[]) : undefined,
			};
		}
		return map;
	}, [myAnswersQuery.data]);

	const submitMutation = useMutation({
		mutationFn: async () => {
			if (!activeQuestionnaireId || !activeQuestionnaire) throw new Error("Select questionnaire first.");

			const payload = {
				answers: activeQuestionnaire.questions.map((question) => {
					const draft = answers[String(question.id)] ?? hydratedAnswers[String(question.id)] ?? {};
					const base = { question_id: Number(question.id) };

					if (question.question_type === "short_text" || question.question_type === "long_text" || question.question_type === "single_choice") {
						return { ...base, answer_text: draft.answer_text ?? "" };
					}
					if (question.question_type === "number") {
						return { ...base, answer_number: draft.answer_number !== undefined && draft.answer_number !== "" ? Number(draft.answer_number) : undefined };
					}
					if (question.question_type === "boolean") {
						return { ...base, answer_boolean: draft.answer_boolean };
					}
					if (question.question_type === "date") {
						return { ...base, answer_date: draft.answer_date ?? "" };
					}
					return { ...base, answer_json: draft.answer_json ?? [] };
				}),
			};

			for (const question of activeQuestionnaire.questions) {
				if (!question.is_required) continue;
				const draft = answers[String(question.id)] ?? hydratedAnswers[String(question.id)] ?? {};
				const missing =
					(question.question_type === "short_text" ||
						question.question_type === "long_text" ||
						question.question_type === "single_choice") &&
					!String(draft.answer_text ?? "").trim();
				const missingNumber = question.question_type === "number" && String(draft.answer_number ?? "").trim() === "";
				const missingBoolean = question.question_type === "boolean" && typeof draft.answer_boolean !== "boolean";
				const missingDate = question.question_type === "date" && !String(draft.answer_date ?? "").trim();
				const missingArray = question.question_type === "multiple_choice" && (draft.answer_json ?? []).length === 0;

				if (missing || missingNumber || missingBoolean || missingDate || missingArray) {
					throw new Error(`Question "${question.question_text}" is required.`);
				}
			}

			return patientApi.submitQuestionnaireAnswers(token!, activeQuestionnaireId, payload);
		},
		onSuccess: async () => {
			toast({ title: "Answers saved successfully" });
			await myAnswersQuery.refetch();
		},
		onError: (e) =>
			toast({ title: e instanceof Error ? e.message : "Failed to submit answers", variant: "destructive" }),
	});

	const activeAnswer = (questionId: number): AnswerDraft => answers[String(questionId)] ?? hydratedAnswers[String(questionId)] ?? {};

	return (
		<div>
			<div className="mb-6">
				<h2 className="text-2xl font-bold">Questionnaires</h2>
				<p className="text-muted-foreground text-sm mt-1">Answer organization questionnaires assigned to you.</p>
			</div>

			<div className="grid lg:grid-cols-3 gap-4 mb-4">
				<div className="space-y-1">
					<Label>Organization type</Label>
					<select
						title="Organization type"
						className="w-full rounded-md border bg-background px-3 py-2 text-sm"
						value={organizationType}
						onChange={(e) => {
							setOrganizationType(e.target.value as OrganizationType);
							setOrganizationId("");
							setActiveQuestionnaireId("");
							setAnswers({});
						}}
					>
						<option value="clinic">Clinic</option>
						<option value="medical_laboratory">Medical laboratory</option>
						<option value="radiology_center">Radiology center</option>
					</select>
				</div>
				<div className="space-y-1">
					<Label>Organization</Label>
					<select
						title="Organization"
						className="w-full rounded-md border bg-background px-3 py-2 text-sm"
						value={organizationId}
						onChange={(e) => {
							setOrganizationId(e.target.value);
							setActiveQuestionnaireId("");
							setAnswers({});
						}}
					>
						<option value="">Select organization</option>
						{organizations.map((item) => (
							<option key={String(item.id)} value={String(item.id)}>
								{item.name ?? `Organization ${item.id}`}
							</option>
						))}
					</select>
				</div>
				<div className="space-y-1">
					<Label>Questionnaire</Label>
					<select
						title="Questionnaire"
						className="w-full rounded-md border bg-background px-3 py-2 text-sm"
						value={activeQuestionnaireId}
						onChange={(e) => {
							setActiveQuestionnaireId(e.target.value);
							setAnswers({});
						}}
						disabled={!organizationId}
					>
						<option value="">Select questionnaire</option>
						{questionnaires.map((item) => (
							<option key={String(item.id)} value={String(item.id)}>
								{item.title}
							</option>
						))}
					</select>
				</div>
			</div>

			{questionnairesQuery.isLoading && <p className="text-sm text-muted-foreground">Loading questionnaires...</p>}
			{questionnairesQuery.error && (
				<p className="text-sm text-destructive">{questionnairesQuery.error instanceof Error ? questionnairesQuery.error.message : "Failed to load questionnaires"}</p>
			)}

			{activeQuestionnaire ? (
				<div className="bg-card rounded-xl border shadow-card p-4 space-y-4">
					<div>
						<div className="flex items-center gap-2">
							<FileText className="h-4 w-4 text-muted-foreground" />
							<h3 className="font-semibold">{activeQuestionnaire.title}</h3>
						</div>
						{activeQuestionnaire.description && <p className="text-sm text-muted-foreground mt-1">{activeQuestionnaire.description}</p>}
					</div>

					<div className="space-y-3">
						{activeQuestionnaire.questions.map((question) => {
							const draft = activeAnswer(question.id);
							const isChoice = question.question_type === "single_choice" || question.question_type === "multiple_choice";
							return (
								<div key={question.id} className="border rounded-lg p-3 space-y-2">
									<div className="flex items-center gap-2">
										<p className="text-sm font-medium">{question.question_text}</p>
										<Badge variant="outline">{question.question_type}</Badge>
										{question.is_required && <Badge variant="outline" className="bg-destructive/10 text-destructive border-destructive/20">Required</Badge>}
									</div>

									{(question.question_type === "short_text" || question.question_type === "single_choice") && (
										<Input
											placeholder={question.placeholder ?? ""}
											value={draft.answer_text ?? ""}
											onChange={(e) =>
												setAnswers((prev) => ({
													...prev,
													[String(question.id)]: { ...prev[String(question.id)], answer_text: e.target.value },
												}))
											}
										/>
									)}

									{question.question_type === "long_text" && (
										<Textarea
											rows={3}
											placeholder={question.placeholder ?? ""}
											value={draft.answer_text ?? ""}
											onChange={(e) =>
												setAnswers((prev) => ({
													...prev,
													[String(question.id)]: { ...prev[String(question.id)], answer_text: e.target.value },
												}))
											}
										/>
									)}

									{question.question_type === "number" && (
										<Input
											type="number"
											value={draft.answer_number ?? ""}
											onChange={(e) =>
												setAnswers((prev) => ({
													...prev,
													[String(question.id)]: { ...prev[String(question.id)], answer_number: e.target.value },
												}))
											}
										/>
									)}

									{question.question_type === "date" && (
										<Input
											type="date"
											value={draft.answer_date ?? ""}
											onChange={(e) =>
												setAnswers((prev) => ({
													...prev,
													[String(question.id)]: { ...prev[String(question.id)], answer_date: e.target.value },
												}))
											}
										/>
									)}

									{question.question_type === "boolean" && (
										<div className="flex items-center gap-4">
											<label className="flex items-center gap-2 text-sm">
												<input
													type="radio"
													name={`q-${question.id}`}
													checked={draft.answer_boolean === true}
													onChange={() =>
														setAnswers((prev) => ({
															...prev,
															[String(question.id)]: { ...prev[String(question.id)], answer_boolean: true },
														}))
													}
												/>
												Yes
											</label>
											<label className="flex items-center gap-2 text-sm">
												<input
													type="radio"
													name={`q-${question.id}`}
													checked={draft.answer_boolean === false}
													onChange={() =>
														setAnswers((prev) => ({
															...prev,
															[String(question.id)]: { ...prev[String(question.id)], answer_boolean: false },
														}))
													}
												/>
												No
											</label>
										</div>
									)}

									{isChoice && Array.isArray(question.options) && question.options.length > 0 && (
										<div className="space-y-1">
											{question.options.map((option) => {
												if (question.question_type === "single_choice") {
													return (
														<label key={option} className="flex items-center gap-2 text-sm">
															<input
																type="radio"
																name={`single-${question.id}`}
																checked={(draft.answer_text ?? "") === option}
																onChange={() =>
																	setAnswers((prev) => ({
																		...prev,
																		[String(question.id)]: { ...prev[String(question.id)], answer_text: option },
																	}))
																}
															/>
															{option}
														</label>
													);
												}

												const values = draft.answer_json ?? [];
												const checked = values.includes(option);
												return (
													<label key={option} className="flex items-center gap-2 text-sm">
														<input
															type="checkbox"
															checked={checked}
															onChange={(e) =>
																setAnswers((prev) => {
																	const existing = prev[String(question.id)]?.answer_json ?? [];
																	const next = e.target.checked ? [...existing, option] : existing.filter((item) => item !== option);
																	return { ...prev, [String(question.id)]: { ...prev[String(question.id)], answer_json: next } };
																})
															}
														/>
														{option}
													</label>
												);
											})}
										</div>
									)}
								</div>
							);
						})}
					</div>

					<div className="flex justify-end">
						<Button
							onClick={() => submitMutation.mutate()}
							disabled={submitMutation.isPending}
							className="gradient-primary text-primary-foreground border-0 gap-2"
						>
							<Send className="h-4 w-4" />
							{submitMutation.isPending ? "Submitting..." : "Submit Answers"}
						</Button>
					</div>
				</div>
			) : (
				<p className="text-sm text-muted-foreground">Choose an organization and questionnaire to start answering.</p>
			)}
		</div>
	);
}


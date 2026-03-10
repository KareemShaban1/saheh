import { useEffect, useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Link, useNavigate, useParams, useSearchParams } from "react-router-dom";
import { ArrowLeft, Plus, Trash2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { clinicApi } from "@/lib/api";
import { useToast } from "@/hooks/use-toast";

type ReservationForm = {
	patient_id: string;
	doctor_id: string;
	date: string;
	reservation_value: string;
	status: "waiting" | "entered" | "finished" | "cancelled";
	payment: "paid" | "not_paid" | "unpaid";
	acceptance: "pending" | "approved" | "not_approved";
	month: string;
};

type ServiceRow = {
	service_fee_id: string;
	fee: string;
	notes: string;
};

function monthFromDate(date: string): string {
	if (!date) return "";
	const parts = date.split("-");
	return parts[1] ?? "";
}

export default function ClinicReservationFormPage() {
	const navigate = useNavigate();
	const queryClient = useQueryClient();
	const { toast } = useToast();
	const { id } = useParams<{ id: string }>();
	const [searchParams] = useSearchParams();
	const isEdit = Boolean(id);
	const patientIdFromQuery = searchParams.get("patient_id") ?? "";

	const [form, setForm] = useState<ReservationForm>({
		patient_id: patientIdFromQuery,
		doctor_id: "",
		date: "",
		reservation_value: "",
		status: "waiting",
		payment: "not_paid",
		acceptance: "approved",
		month: "",
	});
	const [reservationMode, setReservationMode] = useState<"numbers" | "slots">("numbers");
	const [Services, setServices] = useState<ServiceRow[]>([]);

	const reservationDetailsQuery = useQuery({
		queryKey: ["clinic", "reservation", id],
		queryFn: () => clinicApi.reservation(id!),
		enabled: Boolean(isEdit && id),
	});

	const patientsQuery = useQuery({
		queryKey: ["clinic", "patients", "all"],
		queryFn: () => clinicApi.patients({ per_page: "500" }),
	});

	const doctorsQuery = useQuery({
		queryKey: ["clinic", "doctors", "all"],
		queryFn: () => clinicApi.doctors(),
	});

	const reservationOptionsQuery = useQuery({
		queryKey: ["clinic", "reservation-options", form.doctor_id, form.date, id],
		queryFn: () =>
			clinicApi.reservationOptions({
				doctor_id: form.doctor_id,
				date: form.date,
				...(id ? { reservation_id: id } : {}),
			}),
		enabled: Boolean(form.doctor_id && form.date),
	});

	const doctorServicesQuery = useQuery({
		queryKey: ["clinic", "doctor-service-fees", form.doctor_id],
		queryFn: () => clinicApi.doctorServices(form.doctor_id),
		enabled: Boolean(form.doctor_id),
	});

	const patients = useMemo(() => {
		const root = (patientsQuery.data as { data?: unknown })?.data ?? patientsQuery.data;
		return ((root as { data?: Array<{ id: number | string; name?: string }> })?.data ?? []) as Array<{
			id: number | string;
			name?: string;
		}>;
	}, [patientsQuery.data]);

	const doctors = useMemo(() => {
		const root = (doctorsQuery.data as { data?: unknown })?.data ?? doctorsQuery.data;
		return (Array.isArray(root) ? root : []) as Array<{ id: number | string; name?: string }>;
	}, [doctorsQuery.data]);

	const availableServices = useMemo(() => {
		const root = (doctorServicesQuery.data as { data?: unknown })?.data ?? doctorServicesQuery.data;
		return (Array.isArray(root) ? root : []) as Array<{ id: number | string; service_name?: string; fee?: number; notes?: string }>;
	}, [doctorServicesQuery.data]);

	const reservationOptions = useMemo(() => {
		const root = (reservationOptionsQuery.data as { data?: unknown })?.data ?? reservationOptionsQuery.data;
		const options = (root ?? {}) as {
			mode?: "numbers" | "slots";
			all_values?: string[];
			reserved_values?: string[];
			available_values?: string[];
		};
		return {
			mode: options.mode ?? "numbers",
			allValues: options.all_values ?? [],
			reservedValues: options.reserved_values ?? [],
			values: options.available_values ?? [],
		};
	}, [reservationOptionsQuery.data]);

	const selectedPatient = useMemo(
		() => patients.find((p) => String(p.id) === String(form.patient_id)),
		[patients, form.patient_id],
	);

	useEffect(() => {
		setReservationMode(reservationOptions.mode);
	}, [reservationOptions.mode]);

	useEffect(() => {
		if (!form.date) return;
		const m = monthFromDate(form.date);
		setForm((prev) => (prev.month === m ? prev : { ...prev, month: m }));
	}, [form.date]);

	useEffect(() => {
		if (!isEdit || !reservationDetailsQuery.data) return;
		const root = (reservationDetailsQuery.data as { data?: unknown })?.data ?? reservationDetailsQuery.data;
		const details = root as {
			patient_id?: number;
			doctor_id?: number;
			date?: string;
			reservation_number?: string | number | null;
			slot?: string | null;
			status?: ReservationForm["status"];
			payment?: ReservationForm["payment"];
			acceptance?: ReservationForm["acceptance"];
			month?: string;
			reservation_mode?: "numbers" | "slots";
			services?: Array<{ service_fee_id?: number; fee?: number; notes?: string }>;
		};

		const reservationValue =
			details.reservation_number != null && details.reservation_number !== ""
				? String(details.reservation_number)
				: details.slot ?? "";

		setForm({
			patient_id: String(details.patient_id ?? ""),
			doctor_id: String(details.doctor_id ?? ""),
			date: details.date ?? "",
			reservation_value: reservationValue,
			status: details.status ?? "waiting",
			payment: details.payment ?? "not_paid",
			acceptance: details.acceptance ?? "approved",
			month: details.month ?? monthFromDate(details.date ?? ""),
		});
		setReservationMode(details.reservation_mode ?? (details.reservation_number ? "numbers" : "slots"));
		setServices(
			(details.services ?? []).map((sf) => ({
				service_fee_id: String(sf.service_fee_id ?? ""),
				fee: String(sf.fee ?? 0),
				notes: sf.notes ?? "",
			})),
		);
	}, [isEdit, reservationDetailsQuery.data]);

	useEffect(() => {
		// Wait until options request resolves; otherwise edit value can be wiped before options load.
		if (!reservationOptionsQuery.isSuccess) return;
		if (!form.reservation_value) return;

		const optionPool = reservationOptions.allValues.length > 0
			? reservationOptions.allValues
			: reservationOptions.values;

		if (optionPool.includes(form.reservation_value)) return;
		if (isEdit) return;

		setForm((prev) => ({ ...prev, reservation_value: "" }));
	}, [
		reservationOptionsQuery.isSuccess,
		reservationOptions.allValues,
		reservationOptions.values,
		form.reservation_value,
		isEdit,
	]);

	const mutation = useMutation({
		mutationFn: async () => {
			const payload = {
				patient_id: Number(form.patient_id),
				doctor_id: Number(form.doctor_id),
				date: form.date,
				status: form.status,
				payment: form.payment,
				acceptance: form.acceptance,
				month: form.month,
				reservation_number: reservationMode === "numbers" ? form.reservation_value : null,
				slot: reservationMode === "slots" ? form.reservation_value : null,
				services: Services
					.filter((sf) => sf.service_fee_id)
					.map((sf) => ({
						service_fee_id: Number(sf.service_fee_id),
						fee: Number(sf.fee || 0),
						notes: sf.notes || undefined,
					})),
			};
			if (isEdit && id) {
				return clinicApi.updateReservation(id, payload);
			}
			return clinicApi.createReservation(payload);
		},
		onSuccess: async () => {
			await queryClient.invalidateQueries({ queryKey: ["clinic", "reservations"] });
			toast({ title: isEdit ? "Reservation updated" : "Reservation created" });
			navigate("/clinic-dashboard/reservations");
		},
		onError: (e) => {
			toast({
				title: "Failed to save reservation",
				description: e instanceof Error ? e.message : "Unknown error",
				variant: "destructive",
			});
		},
	});

	const onAddService = () => {
		setServices((prev) => [...prev, { service_fee_id: "", fee: "0", notes: "" }]);
	};

	const onRemoveService = (idx: number) => {
		setServices((prev) => prev.filter((_, i) => i !== idx));
	};

	const onPickService = (idx: number, feeId: string) => {
		const selected = availableServices.find((f) => String(f.id) === feeId);
		setServices((prev) =>
			prev.map((row, i) =>
				i === idx
					? { ...row, service_fee_id: feeId, fee: String(selected?.fee ?? 0), notes: row.notes || selected?.notes || "" }
					: row,
			),
		);
	};

	const onSubmit = () => {
		if (!form.patient_id || !form.doctor_id || !form.date || !form.reservation_value) {
			toast({
				title: "Missing required fields",
				description: "Patient, doctor, date and reservation value are required.",
				variant: "destructive",
			});
			return;
		}
		mutation.mutate();
	};

	if (!isEdit && !patientIdFromQuery) {
		return (
			<div className="space-y-3">
				<h2 className="text-2xl font-bold">Add Reservation</h2>
				<p className="text-muted-foreground text-sm">
					Choose a patient first from the patients table to create a reservation with patient name locked.
				</p>
				<Button asChild variant="outline">
					<Link to="/clinic-dashboard/patients">Go to Patients</Link>
				</Button>
			</div>
		);
	}

	return (
		<div>
			<div className="mb-6 flex items-center justify-between gap-3">
				<div>
					<h2 className="text-2xl font-bold">{isEdit ? "Edit Reservation" : "Add Reservation"}</h2>
					<p className="text-muted-foreground text-sm mt-1">
						{isEdit ? "Update reservation details and services" : "Create a new reservation for selected patient"}
					</p>
				</div>
				<Button asChild variant="outline" className="gap-2">
					<Link to="/clinic-dashboard/reservations">
						<ArrowLeft className="h-4 w-4" />
						Back
					</Link>
				</Button>
			</div>

			<div className="rounded-xl border bg-card p-4 space-y-5">
				<div className="grid sm:grid-cols-2 gap-4">
					<div className="space-y-2">
						<Label>Patient Name *</Label>
						<Input value={selectedPatient?.name ?? "Loading..."} disabled />
					</div>
					<div className="space-y-2">
						<Label>Doctor *</Label>
						<Select
							value={form.doctor_id}
							onValueChange={(v) => {
								setForm((f) => ({ ...f, doctor_id: v, reservation_value: "" }));
								setServices([]);
							}}
						>
							<SelectTrigger><SelectValue placeholder="Select doctor" /></SelectTrigger>
							<SelectContent>
								{doctors.map((d) => (
									<SelectItem key={String(d.id)} value={String(d.id)}>{d.name ?? `Doctor ${d.id}`}</SelectItem>
								))}
							</SelectContent>
						</Select>
					</div>
				</div>

				<div className="grid sm:grid-cols-3 gap-4">
					<div className="space-y-2">
						<Label>Date *</Label>
						<Input type="date" value={form.date} onChange={(e) => setForm((f) => ({ ...f, date: e.target.value, reservation_value: "" }))} />
					</div>
					<div className="space-y-2">
						<Label>{reservationMode === "numbers" ? "Reservation Number *" : "Slot *"}</Label>
						<Select
							value={form.reservation_value}
							onValueChange={(v) => setForm((f) => ({ ...f, reservation_value: v }))}
							disabled={!form.doctor_id || !form.date || reservationOptionsQuery.isLoading}
						>
							<SelectTrigger>
								<SelectValue placeholder={reservationOptionsQuery.isLoading ? "Loading options..." : "Select value"} />
							</SelectTrigger>
							<SelectContent>
								{(reservationOptions.allValues.length > 0 ? reservationOptions.allValues : reservationOptions.values).map((item) => (
									<SelectItem key={item} value={item} disabled={reservationOptions.reservedValues.includes(item)}>
										{item}
									</SelectItem>
								))}
							</SelectContent>
						</Select>
					</div>
					<div className="space-y-2">
						<Label>Month *</Label>
						<Input
							value={form.month}
							onChange={(e) => setForm((f) => ({ ...f, month: e.target.value }))}
							placeholder="MM"
							maxLength={2}
						/>
					</div>
				</div>

				<div className="grid sm:grid-cols-3 gap-4">
					<div className="space-y-2">
						<Label>Payment *</Label>
						<Select value={form.payment} onValueChange={(v) => setForm((f) => ({ ...f, payment: v as ReservationForm["payment"] }))}>
							<SelectTrigger><SelectValue /></SelectTrigger>
							<SelectContent>
								<SelectItem value="paid">paid</SelectItem>
								<SelectItem value="not_paid">unpaid</SelectItem>
							</SelectContent>
						</Select>
					</div>
					<div className="space-y-2">
						<Label>Status *</Label>
						<Select value={form.status} onValueChange={(v) => setForm((f) => ({ ...f, status: v as ReservationForm["status"] }))}>
							<SelectTrigger><SelectValue /></SelectTrigger>
							<SelectContent>
								<SelectItem value="waiting">waiting</SelectItem>
								<SelectItem value="entered">entered</SelectItem>
								<SelectItem value="finished">finished</SelectItem>
								<SelectItem value="cancelled">cancelled</SelectItem>
							</SelectContent>
						</Select>
					</div>
					<div className="space-y-2">
						<Label>Acceptance *</Label>
						<Select value={form.acceptance} onValueChange={(v) => setForm((f) => ({ ...f, acceptance: v as ReservationForm["acceptance"] }))}>
							<SelectTrigger><SelectValue /></SelectTrigger>
							<SelectContent>
								<SelectItem value="pending">pending</SelectItem>
								<SelectItem value="approved">approved</SelectItem>
								<SelectItem value="not_approved">not approved</SelectItem>
							</SelectContent>
						</Select>
					</div>
				</div>

				<div className="space-y-2">
					<div className="flex items-center justify-between">
						<Label>Services</Label>
						<Button type="button" size="sm" variant="outline" className="gap-2" onClick={onAddService} disabled={!form.doctor_id}>
							<Plus className="h-4 w-4" />
							Add Service
						</Button>
					</div>
					{doctorServicesQuery.isLoading && form.doctor_id && (
						<p className="text-xs text-muted-foreground">Loading doctor services...</p>
					)}
					{Services.length === 0 && (
						<p className="text-xs text-muted-foreground">No service rows yet.</p>
					)}
					{Services.map((sf, idx) => (
						<div key={`${idx}-${sf.service_fee_id}`} className="grid grid-cols-12 gap-2 items-start border rounded-lg p-2">
							<div className="col-span-12 md:col-span-5 space-y-1">
								<Label className="text-xs">Service</Label>
								<Select value={sf.service_fee_id} onValueChange={(v) => onPickService(idx, v)}>
									<SelectTrigger><SelectValue placeholder="Select service" /></SelectTrigger>
									<SelectContent>
										{availableServices.map((item) => (
											<SelectItem key={String(item.id)} value={String(item.id)}>
												{item.service_name ?? `Service ${item.id}`}
											</SelectItem>
										))}
									</SelectContent>
								</Select>
							</div>
							<div className="col-span-12 md:col-span-3 space-y-1">
								<Label className="text-xs">Cost</Label>
								<Input
									type="number"
									value={sf.fee}
									onChange={(e) =>
										setServices((prev) =>
											prev.map((row, i) => (i === idx ? { ...row, fee: e.target.value } : row)),
										)
									}
								/>
							</div>
							<div className="col-span-12 md:col-span-3 space-y-1">
								<Label className="text-xs">Notes</Label>
								<Textarea
									rows={1}
									value={sf.notes}
									onChange={(e) =>
										setServices((prev) =>
											prev.map((row, i) => (i === idx ? { ...row, notes: e.target.value } : row)),
										)
									}
								/>
							</div>
							<div className="col-span-12 md:col-span-1 pt-5">
								<Button type="button" size="icon" variant="ghost" onClick={() => onRemoveService(idx)}>
									<Trash2 className="h-4 w-4 text-destructive" />
								</Button>
							</div>
						</div>
					))}
				</div>

				<div className="flex justify-end gap-2">
					<Button variant="outline" onClick={() => navigate("/clinic-dashboard/reservations")} disabled={mutation.isPending}>
						Cancel
					</Button>
					<Button onClick={onSubmit} disabled={mutation.isPending} className="gradient-primary text-primary-foreground border-0">
						{mutation.isPending ? "Saving..." : isEdit ? "Save Changes" : "Create Reservation"}
					</Button>
				</div>
			</div>
		</div>
	);
}

const BASE_URL = import.meta.env.VITE_BASE_URL || "http://localhost:8000/api/v1";

interface RequestOptions {
	method?: string;
	body?: unknown;
	headers?: Record<string, string>;
	token?: string;
}

async function request<T>(endpoint: string, options: RequestOptions = {}): Promise<T> {
	const { method = "GET", body, headers = {}, token } = options;
	const isFormData = typeof FormData !== "undefined" && body instanceof FormData;

	const config: RequestInit = {
		method,
		headers: {
			Accept: "application/json",
			...(isFormData ? {} : { "Content-Type": "application/json" }),
			...headers,
			...(token ? { Authorization: `Bearer ${token}` } : {}),
		},
		credentials: "include",
	};

	if (body) {
		config.body = isFormData ? (body as FormData) : JSON.stringify(body);
	}

	const url = endpoint.startsWith("http") ? endpoint : `${BASE_URL.replace(/\/$/, "")}${endpoint.startsWith("/") ? "" : "/"}${endpoint}`;
	const res = await fetch(url, config);
	if (!res.ok) {
		const body = await res.json().catch(() => ({ message: res.statusText }));
		const err = new Error((body as { message?: string }).message || "Request failed") as Error & { errors?: Record<string, string[]> };
		if ((body as { errors?: Record<string, string[]> }).errors) err.errors = (body as { errors: Record<string, string[]> }).errors;
		throw err;
	}
	return res.json();
}

// Auth (patient)
export const authApi = {
	login: (email: string, password: string) =>
		request<{ status: boolean; token?: string; patient?: unknown; message?: string }>("/patient/login", {
			method: "POST",
			body: { email, password },
		}),
	register: (data: {
		name: string;
		email: string;
		password: string;
		age: number;
		phone: string;
		address: string;
		gender: "male" | "female";
		blood_group: string;
		whatsapp_number?: string;
	}) =>
		request<{ status: boolean; token?: string; patient?: unknown; message?: string; errors?: unknown }>("/patient/register", {
			method: "POST",
			body: data,
		}),
	logout: (token: string) =>
		request<{ status: boolean }>("/patient/logout", { method: "POST", token }),
	getProfile: (token: string) =>
		request<{ status: boolean; patient?: unknown }>("/patient/profile", { token }),
};

// Public endpoints (no auth)
export const publicApi = {
	home: () => request("/patient/home"),
	clinics: (params?: Record<string, string>) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/patient/clinics${q}`);
	},
	clinic: (id: string) => request(`/patient/clinic/${id}`),
	doctor: (id: string) => request(`/patient/doctor/${id}`),
	medicalLabs: () => request("/patient/medical_laboratories"),
	medicalLab: (id: string) => request(`/patient/medical_laboratory/${id}`),
	radiologyCenters: () => request("/patient/radiology_centers"),
	radiologyCenter: (id: string) => request(`/patient/radiology_center/${id}`),
};

// Patient endpoints (Bearer token)
export const patientApi = {
	reservations: (token: string) => request("/patient/reservations", { token }),
	doctors: (token: string, params?: Record<string, string>) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/patient/doctors${q}`, { token });
	},
	doctorReservationOptions: (
		token: string,
		params: { clinic_id: number | string; doctor_id: number | string; reservation_date: string },
	) => {
		const q = new URLSearchParams({
			clinic_id: String(params.clinic_id),
			doctor_id: String(params.doctor_id),
			reservation_date: params.reservation_date,
		}).toString();
		return request(`/patient/doctor_reservation_slots_number?${q}`, { token });
	},
	doctorServices: (token: string, doctorId: number | string) => request(`/patient/doctor_services/${doctorId}`, { token }),
	clinics: (token: string) => request("/patient/clinics", { token }),
	medicalLabs: (token: string) => request("/patient/medical_laboratories", { token }),
	radiologyCenters: (token: string) => request("/patient/radiology_centers", { token }),
	storeReservation: (token: string, data: unknown) =>
		request("/patient/store_reservation", { method: "POST", body: data, token }),
	reservation: (token: string, id: string) => request(`/patient/reservation/${id}`, { token }),
	changeReservationStatus: (token: string, id: string, status: string) =>
		request(`/patient/change_reservation_status/${id}/${status}`, { method: "POST", token }),
	profile: (token: string) => request("/patient/profile", { token }),
	updateProfile: (token: string, data: unknown) =>
		request("/patient/update-profile", { method: "POST", body: data, token }),
	changePassword: (token: string, data: unknown) =>
		request("/patient/change-password", { method: "POST", body: data, token }),
	prescriptions: (token: string) => request("/patient/prescriptions", { token }),
	rays: (token: string) => request("/patient/rays", { token }),
	medicalAnalyses: (token: string) => request("/patient/medical_analyses", { token }),
	chronicDiseases: (token: string) => request("/patient/chronic_diseases", { token }),
	glassesDistance: (token: string) => request("/patient/glasses_distances", { token }),
	reviews: (token: string) => request("/patient/reviews", { token }),
	postReview: (token: string, data: unknown) =>
		request("/patient/reviews", { method: "POST", body: data, token }),
	updateReview: (token: string, id: string, data: unknown) =>
		request(`/patient/reviews/${id}`, { method: "PUT", body: data, token }),
	deleteReview: (token: string, id: string) =>
		request(`/patient/reviews/${id}`, { method: "DELETE", token }),
	home: (token: string) => request("/patient/home", { token }),
	chatContacts: (token: string) => request("/patient/chat/contacts", { token }),
	chatConversations: (token: string) => request("/patient/chat/conversations", { token }),
	openChatConversation: (token: string, data: { target_type: "user"; target_id: number }) =>
		request("/patient/chat/open", { method: "POST", body: data, token }),
	chatMessages: (token: string, chatId: string | number) =>
		request(`/patient/chat/conversations/${chatId}/messages`, { token }),
	sendChatMessage: (
		token: string,
		chatId: string | number,
		data: { message?: string } | FormData,
	) => request(`/patient/chat/conversations/${chatId}/messages`, { method: "POST", body: data, token }),
};

// Admin dashboard (session-based)
export const adminApi = {
	dashboard: () => request("/admin/dashboard", { token: getSuperAdminToken() ?? undefined }),
	financial: (params?: Record<string, string>) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/admin/financial${q}`, { token: getSuperAdminToken() ?? undefined });
	},
	clinics: (params?: Record<string, string>) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/admin/clinics${q}`, { token: getSuperAdminToken() ?? undefined });
	},
	clinic: (id: string | number) =>
		request(`/admin/clinics/${id}`, { token: getSuperAdminToken() ?? undefined }),
	createClinic: (data: {
		name: string;
		email?: string | null;
		phone?: string | null;
		address?: string | null;
		description?: string | null;
		website?: string | null;
		status?: "active" | "inactive";
		specialty_id?: number | null;
		governorate_id?: number | null;
		city_id?: number | null;
		area_id?: number | null;
	}) => request("/admin/clinics", { method: "POST", body: data, token: getSuperAdminToken() ?? undefined }),
	updateClinicStatus: (id: string | number, status: "active" | "inactive") =>
		request(`/admin/clinics/${id}/status`, { method: "PUT", body: { status }, token: getSuperAdminToken() ?? undefined }),
	deleteClinic: (id: string | number) =>
		request(`/admin/clinics/${id}`, { method: "DELETE", token: getSuperAdminToken() ?? undefined }),
	medicalLabs: (params?: Record<string, string>) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/admin/medical-laboratories${q}`, { token: getSuperAdminToken() ?? undefined });
	},
	medicalLab: (id: string | number) =>
		request(`/admin/medical-laboratories/${id}`, { token: getSuperAdminToken() ?? undefined }),
	createMedicalLab: (data: {
		name: string;
		email?: string | null;
		phone?: string | null;
		address?: string | null;
		description?: string | null;
		website?: string | null;
		status?: "active" | "inactive";
		governorate_id?: number | null;
		city_id?: number | null;
		area_id?: number | null;
	}) => request("/admin/medical-laboratories", { method: "POST", body: data, token: getSuperAdminToken() ?? undefined }),
	updateMedicalLabStatus: (id: string | number, status: "active" | "inactive") =>
		request(`/admin/medical-laboratories/${id}/status`, { method: "PUT", body: { status }, token: getSuperAdminToken() ?? undefined }),
	deleteMedicalLab: (id: string | number) =>
		request(`/admin/medical-laboratories/${id}`, { method: "DELETE", token: getSuperAdminToken() ?? undefined }),
	radiologyCenters: (params?: Record<string, string>) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/admin/radiology-centers${q}`, { token: getSuperAdminToken() ?? undefined });
	},
	radiologyCenter: (id: string | number) =>
		request(`/admin/radiology-centers/${id}`, { token: getSuperAdminToken() ?? undefined }),
	createRadiologyCenter: (data: {
		name: string;
		email?: string | null;
		phone?: string | null;
		address?: string | null;
		description?: string | null;
		website?: string | null;
		status?: "active" | "inactive";
		governorate_id?: number | null;
		city_id?: number | null;
		area_id?: number | null;
	}) => request("/admin/radiology-centers", { method: "POST", body: data, token: getSuperAdminToken() ?? undefined }),
	updateRadiologyCenterStatus: (id: string | number, status: "active" | "inactive") =>
		request(`/admin/radiology-centers/${id}/status`, { method: "PUT", body: { status }, token: getSuperAdminToken() ?? undefined }),
	deleteRadiologyCenter: (id: string | number) =>
		request(`/admin/radiology-centers/${id}`, { method: "DELETE", token: getSuperAdminToken() ?? undefined }),
	specialties: () => request("/admin/specialties", { token: getSuperAdminToken() ?? undefined }),
	specialty: (id: string | number) =>
		request(`/admin/specialties/${id}`, { token: getSuperAdminToken() ?? undefined }),
	createSpecialty: (data: { name_en: string; name_ar: string; description?: string | null; status?: "active" | "inactive" }) =>
		request("/admin/specialties", { method: "POST", body: data, token: getSuperAdminToken() ?? undefined }),
	updateSpecialty: (id: string | number, data: { name_en: string; name_ar: string; description?: string | null }) =>
		request(`/admin/specialties/${id}`, { method: "PUT", body: data, token: getSuperAdminToken() ?? undefined }),
	updateSpecialtyStatus: (id: string | number, status: "active" | "inactive") =>
		request(`/admin/specialties/${id}/status`, { method: "PUT", body: { status }, token: getSuperAdminToken() ?? undefined }),
	deleteSpecialty: (id: string | number) =>
		request(`/admin/specialties/${id}`, { method: "DELETE", token: getSuperAdminToken() ?? undefined }),
	governorates: () => request("/admin/governorates", { token: getSuperAdminToken() ?? undefined }),
	governorate: (id: string | number) =>
		request(`/admin/governorates/${id}`, { token: getSuperAdminToken() ?? undefined }),
	createGovernorate: (data: { name: string; status?: "active" | "inactive" }) =>
		request("/admin/governorates", { method: "POST", body: data, token: getSuperAdminToken() ?? undefined }),
	updateGovernorate: (id: string | number, data: { name: string }) =>
		request(`/admin/governorates/${id}`, { method: "PUT", body: data, token: getSuperAdminToken() ?? undefined }),
	updateGovernorateStatus: (id: string | number, status: "active" | "inactive") =>
		request(`/admin/governorates/${id}/status`, { method: "PUT", body: { status }, token: getSuperAdminToken() ?? undefined }),
	deleteGovernorate: (id: string | number) =>
		request(`/admin/governorates/${id}`, { method: "DELETE", token: getSuperAdminToken() ?? undefined }),
	cities: (params?: Record<string, string>) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/admin/cities${q}`, { token: getSuperAdminToken() ?? undefined });
	},
	city: (id: string | number) =>
		request(`/admin/cities/${id}`, { token: getSuperAdminToken() ?? undefined }),
	createCity: (data: { name: string; governorate_id: number; status?: "active" | "inactive" }) =>
		request("/admin/cities", { method: "POST", body: data, token: getSuperAdminToken() ?? undefined }),
	updateCity: (id: string | number, data: { name: string; governorate_id: number }) =>
		request(`/admin/cities/${id}`, { method: "PUT", body: data, token: getSuperAdminToken() ?? undefined }),
	updateCityStatus: (id: string | number, status: "active" | "inactive") =>
		request(`/admin/cities/${id}/status`, { method: "PUT", body: { status }, token: getSuperAdminToken() ?? undefined }),
	deleteCity: (id: string | number) =>
		request(`/admin/cities/${id}`, { method: "DELETE", token: getSuperAdminToken() ?? undefined }),
	areas: (params?: Record<string, string>) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/admin/areas${q}`, { token: getSuperAdminToken() ?? undefined });
	},
	area: (id: string | number) =>
		request(`/admin/areas/${id}`, { token: getSuperAdminToken() ?? undefined }),
	createArea: (data: { name: string; city_id: number; governorate_id: number; status?: "active" | "inactive" }) =>
		request("/admin/areas", { method: "POST", body: data, token: getSuperAdminToken() ?? undefined }),
	updateArea: (id: string | number, data: { name: string; city_id: number; governorate_id: number }) =>
		request(`/admin/areas/${id}`, { method: "PUT", body: data, token: getSuperAdminToken() ?? undefined }),
	updateAreaStatus: (id: string | number, status: "active" | "inactive") =>
		request(`/admin/areas/${id}/status`, { method: "PUT", body: { status }, token: getSuperAdminToken() ?? undefined }),
	deleteArea: (id: string | number) =>
		request(`/admin/areas/${id}`, { method: "DELETE", token: getSuperAdminToken() ?? undefined }),
	users: (params?: Record<string, string>) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/admin/users${q}`, { token: getSuperAdminToken() ?? undefined });
	},
	roles: () => request("/admin/roles", { token: getSuperAdminToken() ?? undefined }),
	reviews: (params?: Record<string, string>) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/admin/reviews${q}`, { token: getSuperAdminToken() ?? undefined });
	},
	review: (id: string | number) =>
		request(`/admin/reviews/${id}`, { token: getSuperAdminToken() ?? undefined }),
	createReview: (data: { patient_id: number; rating: number; comment: string; is_active?: boolean }) =>
		request("/admin/reviews", { method: "POST", body: data, token: getSuperAdminToken() ?? undefined }),
	updateReview: (id: string | number, data: { rating: number; comment: string }) =>
		request(`/admin/reviews/${id}`, { method: "PUT", body: data, token: getSuperAdminToken() ?? undefined }),
	updateReviewStatus: (id: string | number, status: "active" | "inactive") =>
		request(`/admin/reviews/${id}/status`, { method: "PUT", body: { status }, token: getSuperAdminToken() ?? undefined }),
	deleteReview: (id: string | number) =>
		request(`/admin/reviews/${id}`, { method: "DELETE", token: getSuperAdminToken() ?? undefined }),
	announcements: (params?: Record<string, string>) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/admin/announcements${q}`, { token: getSuperAdminToken() ?? undefined });
	},
	announcement: (id: string | number) =>
		request(`/admin/announcements/${id}`, { token: getSuperAdminToken() ?? undefined }),
	createAnnouncement: (data: {
		title: string;
		body: string;
		type?: "text" | "banner";
		is_active?: boolean;
		start_date?: string | null;
		end_date?: string | null;
	}) => request("/admin/announcements", { method: "POST", body: data, token: getSuperAdminToken() ?? undefined }),
	updateAnnouncement: (id: string | number, data: {
		title: string;
		body: string;
		type?: "text" | "banner";
		start_date?: string | null;
		end_date?: string | null;
	}) => request(`/admin/announcements/${id}`, { method: "PUT", body: data, token: getSuperAdminToken() ?? undefined }),
	updateAnnouncementStatus: (id: string | number, status: "active" | "inactive") =>
		request(`/admin/announcements/${id}/status`, { method: "PUT", body: { status }, token: getSuperAdminToken() ?? undefined }),
	deleteAnnouncement: (id: string | number) =>
		request(`/admin/announcements/${id}`, { method: "DELETE", token: getSuperAdminToken() ?? undefined }),
};

// Super admin auth – token stored in localStorage
const SUPER_ADMIN_TOKEN_KEY = "super_admin_token";
const SUPER_ADMIN_USER_KEY = "super_admin_user";

export function getSuperAdminToken(): string | null {
	return typeof window !== "undefined" ? localStorage.getItem(SUPER_ADMIN_TOKEN_KEY) : null;
}

export function setSuperAdminToken(token: string): void {
	if (typeof window !== "undefined") localStorage.setItem(SUPER_ADMIN_TOKEN_KEY, token);
}

export function clearSuperAdminToken(): void {
	if (typeof window !== "undefined") localStorage.removeItem(SUPER_ADMIN_TOKEN_KEY);
}

export function getSuperAdminUser(): unknown | null {
	if (typeof window === "undefined") return null;
	const raw = localStorage.getItem(SUPER_ADMIN_USER_KEY);
	if (!raw) return null;
	try {
		return JSON.parse(raw);
	} catch {
		return null;
	}
}

export function setSuperAdminUser(user: unknown): void {
	if (typeof window !== "undefined") localStorage.setItem(SUPER_ADMIN_USER_KEY, JSON.stringify(user));
}

export function clearSuperAdminUser(): void {
	if (typeof window !== "undefined") localStorage.removeItem(SUPER_ADMIN_USER_KEY);
}

export const superAdminAuthApi = {
	login: (email: string, password: string) =>
		request<{ status: boolean; token?: string; admin?: unknown; message?: string }>("/admin/login", {
			method: "POST",
			body: { email, password },
		}),
	logout: () => {
		const token = getSuperAdminToken();
		if (token) {
			return request<{ status: boolean }>("/admin/logout", { method: "POST", token });
		}
		return Promise.resolve({ status: true });
	},
	profile: () => {
		const token = getSuperAdminToken();
		return request<{ status: boolean; admin?: unknown }>("/admin/profile", { token: token ?? undefined });
	},
	updateProfile: (data: { name: string; email: string; password?: string }) => {
		const token = getSuperAdminToken();
		return request<{ status: boolean; admin?: unknown; message?: string }>("/admin/profile", {
			method: "PUT",
			body: data,
			token: token ?? undefined,
		});
	},
};

// Organization (clinic / lab / radiology) auth – token stored in localStorage
const ORG_TOKEN_KEY = "organization_token";
const ORG_USER_KEY = "organization_user";

export function getOrganizationToken(): string | null {
	return typeof window !== "undefined" ? localStorage.getItem(ORG_TOKEN_KEY) : null;
}

export function setOrganizationToken(token: string): void {
	if (typeof window !== "undefined") localStorage.setItem(ORG_TOKEN_KEY, token);
}

export function clearOrganizationToken(): void {
	if (typeof window !== "undefined") localStorage.removeItem(ORG_TOKEN_KEY);
}

export function getOrganizationUser(): unknown | null {
	if (typeof window === "undefined") return null;
	const raw = localStorage.getItem(ORG_USER_KEY);
	if (!raw) return null;
	try {
		return JSON.parse(raw);
	} catch {
		return null;
	}
}

export function setOrganizationUser(user: unknown): void {
	if (typeof window !== "undefined") localStorage.setItem(ORG_USER_KEY, JSON.stringify(user));
}

export function clearOrganizationUser(): void {
	if (typeof window !== "undefined") localStorage.removeItem(ORG_USER_KEY);
}

export const organizationAuthApi = {
	clinicLogin: (email: string, password: string) =>
		request<{ status: boolean; token?: string; user?: unknown; message?: string }>("/clinic/login", {
			method: "POST",
			body: { email, password },
		}),
	clinicRegister: (data: Record<string, unknown>) =>
		request<{ success?: boolean; message?: string }>("/clinic/register", { method: "POST", body: data }),
	medicalLaboratoryLogin: (email: string, password: string) =>
		request<{ status: boolean; token?: string; user?: unknown; message?: string }>("/medicalLaboratory/login", {
			method: "POST",
			body: { email, password },
		}),
	medicalLaboratoryRegister: (data: Record<string, unknown>) =>
		request<{ success?: boolean; message?: string }>("/medicalLaboratory/register", { method: "POST", body: data }),
	radiologyCenterLogin: (email: string, password: string) =>
		request<{ status: boolean; token?: string; user?: unknown; message?: string }>("/radiologyCenter/login", {
			method: "POST",
			body: { email, password },
		}),
	radiologyCenterRegister: (data: Record<string, unknown>) =>
		request<{ success?: boolean; message?: string }>("/radiologyCenter/register", { method: "POST", body: data }),
	logout: () => {
		const token = getOrganizationToken();
		if (token) {
			return request<{ status: boolean }>("/organization/logout", { method: "POST", token });
		}
		return Promise.resolve({ status: true });
	},
	profile: () => {
		const token = getOrganizationToken();
		return request<{ status: boolean; user?: unknown }>("/organization/profile", { token: token ?? undefined });
	},
	updateProfile: (data: { name: string; email: string; phone?: string; job_title?: string; password?: string }) => {
		const token = getOrganizationToken();
		return request<{ status: boolean; user?: unknown; message?: string }>("/organization/profile", {
			method: "PUT",
			body: data,
			token: token ?? undefined,
		});
	},
};

// Shared organization chat (clinic / lab / radiology)
export const organizationChatApi = {
	contacts: (token?: string | null) =>
		request("/organization/chat/contacts", { token: token ?? getOrganizationToken() ?? undefined }),
	conversations: (token?: string | null) =>
		request("/organization/chat/conversations", { token: token ?? getOrganizationToken() ?? undefined }),
	openConversation: (
		data: { target_type: "user" | "patient"; target_id: number },
		token?: string | null,
	) => request("/organization/chat/open", { method: "POST", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	messages: (chatId: string | number, token?: string | null) =>
		request(`/organization/chat/conversations/${chatId}/messages`, { token: token ?? getOrganizationToken() ?? undefined }),
	sendMessage: (
		chatId: string | number,
		data: { message?: string } | FormData,
		token?: string | null,
	) => request(`/organization/chat/conversations/${chatId}/messages`, { method: "POST", body: data, token: token ?? getOrganizationToken() ?? undefined }),
};

// Clinic dashboard (Bearer token from localStorage)
export const clinicApi = {
	dashboard: (token?: string | null) =>
		request("/clinic/dashboard", { token: token ?? getOrganizationToken() ?? undefined }),
	financial: (params?: Record<string, string>, token?: string | null) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/clinic/financial${q}`, { token: token ?? getOrganizationToken() ?? undefined });
	},
	reservations: (params?: Record<string, string>, token?: string | null) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/clinic/reservations/data${q}`, { token: token ?? getOrganizationToken() ?? undefined });
	},
	reservation: (id: string | number, token?: string | null) =>
		request(`/clinic/reservations/${id}`, { token: token ?? getOrganizationToken() ?? undefined }),
	createReservation: (
		data:
			| {
					patient_id: number;
					doctor_id: number;
					date: string;
					time?: string | null;
					reservation_number?: string | null;
					slot?: string | null;
					status?: "waiting" | "entered" | "finished" | "cancelled";
					acceptance: "pending" | "approved" | "not_approved";
					payment: "paid" | "not_paid" | "unpaid";
					month?: string;
					first_diagnosis?: string | null;
					final_diagnosis?: string | null;
					services?: Array<{ service_fee_id: number; fee?: number; notes?: string }>;
			  }
			| FormData,
		token?: string | null,
	) => request("/clinic/reservations", { method: "POST", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	updateReservation: (
		id: string | number,
		data:
			| {
					patient_id: number;
					doctor_id: number;
					date: string;
					time?: string | null;
					reservation_number?: string | null;
					slot?: string | null;
					status?: "waiting" | "entered" | "finished" | "cancelled";
					acceptance: "pending" | "approved" | "not_approved";
					payment: "paid" | "not_paid" | "unpaid";
					month?: string;
					first_diagnosis?: string | null;
					final_diagnosis?: string | null;
					remove_voice_record_ids?: number[];
					services?: Array<{ service_fee_id: number; fee?: number; notes?: string }>;
			  }
			| FormData,
		token?: string | null,
	) => request(`/clinic/reservations/${id}`, { method: "PUT", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	reservationPrescription: (id: string | number, token?: string | null) =>
		request(`/clinic/reservations/${id}/prescription`, { token: token ?? getOrganizationToken() ?? undefined }),
	saveReservationPrescription: (
		id: string | number,
		data:
			| {
					title?: string;
					notes?: string;
					drugs: Array<{
						name: string;
						type: string;
						dose: string;
						frequency: string;
						period: string;
						notes?: string;
					}>;
			  }
			| FormData,
		token?: string | null,
	) => request(`/clinic/reservations/${id}/prescription`, { method: "POST", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	reservationGlassesDistances: (id: string | number, token?: string | null) =>
		request(`/clinic/reservations/${id}/glasses-distances`, { token: token ?? getOrganizationToken() ?? undefined }),
	createReservationGlassesDistance: (
		id: string | number,
		data: {
			SPH_R_D?: string;
			CYL_R_D?: string;
			AX_R_D?: string;
			SPH_L_D?: string;
			CYL_L_D?: string;
			AX_L_D?: string;
			SPH_R_N?: string;
			CYL_R_N?: string;
			AX_R_N?: string;
			SPH_L_N?: string;
			CYL_L_N?: string;
			AX_L_N?: string;
		},
		token?: string | null,
	) => request(`/clinic/reservations/${id}/glasses-distances`, { method: "POST", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	reservationTeeth: (id: string | number, token?: string | null) =>
		request(`/clinic/reservations/${id}/teeth`, { token: token ?? getOrganizationToken() ?? undefined }),
	saveReservationTeeth: (
		id: string | number,
		data: {
			general_note?: string;
			next_session_plan?: string;
			teeth: Array<{ tooth_number: number; tooth_note?: string }>;
		},
		token?: string | null,
	) => request(`/clinic/reservations/${id}/teeth`, { method: "POST", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	reservationRays: (id: string | number, token?: string | null) =>
		request(`/clinic/reservations/${id}/rays`, { token: token ?? getOrganizationToken() ?? undefined }),
	createReservationRay: (
		id: string | number,
		data:
			| {
					date: string;
					payment: "paid" | "not_paid";
					report?: string;
					cost?: number | null;
			  }
			| FormData,
		token?: string | null,
	) => request(`/clinic/reservations/${id}/rays`, { method: "POST", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	doctors: (token?: string | null) =>
		request("/clinic/doctors", { token: token ?? getOrganizationToken() ?? undefined }),
	doctor: (id: string | number, token?: string | null) =>
		request(`/clinic/doctors/${id}`, { token: token ?? getOrganizationToken() ?? undefined }),
	createDoctor: (
		data: {
			name: string;
			email: string;
			password: string;
			phone: string;
			certifications: string;
			specialty_id: number;
		},
		token?: string | null,
	) => request("/clinic/doctors", { method: "POST", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	updateDoctor: (
		id: string | number,
		data: {
			name: string;
			email: string;
			password?: string;
			phone: string;
			certifications: string;
			specialty_id: number;
		},
		token?: string | null,
	) => request(`/clinic/doctors/${id}`, { method: "PUT", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	specialties: (token?: string | null) =>
		request("/clinic/specialties", { token: token ?? getOrganizationToken() ?? undefined }),
	doctorServices: (doctorId: string | number, token?: string | null) =>
		request(`/clinic/doctors/${doctorId}/service-fees`, { token: token ?? getOrganizationToken() ?? undefined }),
	patients: (params?: Record<string, string>, token?: string | null) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/clinic/patients${q}`, { token: token ?? getOrganizationToken() ?? undefined });
	},
	patient: (id: string | number, token?: string | null) =>
		request(`/clinic/patients/${id}`, { token: token ?? getOrganizationToken() ?? undefined }),
	createPatient: (
		data: {
			doctor_id?: number;
			doctor_ids?: number[];
			name: string;
			address: string;
			email?: string;
			password?: string;
			phone: string;
			whatsapp_number?: string;
			age?: string;
			gender: "male" | "female";
			blood_group?: "A+" | "A-" | "B+" | "B-" | "O+" | "O-" | "AB+" | "AB-";
			height?: string;
			weight?: string;
		},
		token?: string | null,
	) => request("/clinic/patients", { method: "POST", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	updatePatient: (
		id: string | number,
		data: {
			doctor_id?: number;
			doctor_ids?: number[];
			name: string;
			address: string;
			email?: string;
			password?: string;
			phone: string;
			whatsapp_number?: string;
			age?: string;
			gender: "male" | "female";
			blood_group?: "A+" | "A-" | "B+" | "B-" | "O+" | "O-" | "AB+" | "AB-";
			height?: string;
			weight?: string;
		},
		token?: string | null,
	) => request(`/clinic/patients/${id}`, { method: "PUT", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	assignPatientByCode: (
		data: {
			patient_code?: string;
			qr_value?: string;
			doctor_id?: number;
			doctor_ids?: number[];
		},
		token?: string | null,
	) => request("/clinic/patients/assign", { method: "POST", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	patientGlassesDistances: (id: string | number, token?: string | null) =>
		request(`/clinic/patients/${id}/glasses-distances`, { token: token ?? getOrganizationToken() ?? undefined }),
	patientHistory: (id: string | number, token?: string | null) =>
		request(`/clinic/patients/${id}/history`, { token: token ?? getOrganizationToken() ?? undefined }),
	createPatientGlassesDistance: (
		id: string | number,
		data: {
			reservation_id?: number;
			SPH_R_D?: string;
			CYL_R_D?: string;
			AX_R_D?: string;
			SPH_L_D?: string;
			CYL_L_D?: string;
			AX_L_D?: string;
			SPH_R_N?: string;
			CYL_R_N?: string;
			AX_R_N?: string;
			SPH_L_N?: string;
			CYL_L_N?: string;
			AX_L_N?: string;
		},
		token?: string | null,
	) => request(`/clinic/patients/${id}/glasses-distances`, { method: "POST", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	reservationOptions: (
		params: { doctor_id: string | number; date: string; reservation_id?: string | number },
		token?: string | null,
	) => {
		const q = new URLSearchParams({
			doctor_id: String(params.doctor_id),
			date: params.date,
			...(params.reservation_id ? { reservation_id: String(params.reservation_id) } : {}),
		}).toString();
		return request(`/clinic/reservations/options?${q}`, { token: token ?? getOrganizationToken() ?? undefined });
	},
	roles: (token?: string | null) =>
		request("/clinic/roles", { token: token ?? getOrganizationToken() ?? undefined }),
	role: (id: string | number, token?: string | null) =>
		request(`/clinic/roles/${id}`, { token: token ?? getOrganizationToken() ?? undefined }),
	createRole: (
		data: { name: string; permission_ids: number[] },
		token?: string | null,
	) => request("/clinic/roles", { method: "POST", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	updateRole: (
		id: string | number,
		data: { name: string; permission_ids: number[] },
		token?: string | null,
	) => request(`/clinic/roles/${id}`, { method: "PUT", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	permissions: (token?: string | null) =>
		request("/clinic/permissions", { token: token ?? getOrganizationToken() ?? undefined }),
	reservationNumbers: (params?: Record<string, string>, token?: string | null) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/clinic/reservation-numbers${q}`, { token: token ?? getOrganizationToken() ?? undefined });
	},
	reservationSlots: (params?: Record<string, string>, token?: string | null) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/clinic/reservation-slots${q}`, { token: token ?? getOrganizationToken() ?? undefined });
	},
	reviews: (token?: string | null) =>
		request("/clinic/reviews", { token: token ?? getOrganizationToken() ?? undefined }),
	announcements: (token?: string | null) =>
		request("/clinic/announcements", { token: token ?? getOrganizationToken() ?? undefined }),
	notifications: (params?: Record<string, string>, token?: string | null) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/clinic/notifications${q}`, { token: token ?? getOrganizationToken() ?? undefined });
	},
	users: (params?: Record<string, string>, token?: string | null) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/clinic/users${q}`, { token: token ?? getOrganizationToken() ?? undefined });
	},
	user: (id: string | number, token?: string | null) =>
		request(`/clinic/users/${id}`, { token: token ?? getOrganizationToken() ?? undefined }),
	createUser: (
		data: {
			name: string;
			email: string;
			password: string;
			phone?: string;
			job_title?: string;
			role_id: number;
			permission_ids?: number[];
		},
		token?: string | null,
	) => request("/clinic/users", { method: "POST", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	updateUser: (
		id: string | number,
		data: {
			name: string;
			email: string;
			password?: string;
			phone?: string;
			job_title?: string;
			role_id: number;
			permission_ids?: number[];
		},
		token?: string | null,
	) => request(`/clinic/users/${id}`, { method: "PUT", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	deactivateUser: (id: string | number, token?: string | null) =>
		request(`/clinic/users/${id}`, { method: "DELETE", token: token ?? getOrganizationToken() ?? undefined }),
	restoreUser: (id: string | number, token?: string | null) =>
		request(`/clinic/users/${id}/restore`, { method: "PUT", token: token ?? getOrganizationToken() ?? undefined }),
	services: (token?: string | null) =>
		request("/clinic/services", { token: token ?? getOrganizationToken() ?? undefined }),
	service: (id: string | number, token?: string | null) =>
		request(`/clinic/services/${id}`, { token: token ?? getOrganizationToken() ?? undefined }),
	createService: (
		data: {
			service_name: string;
			doctor_id?: number | null;
			price: number;
			type: "main" | "sub";
			notes?: string | null;
		},
		token?: string | null,
	) => request("/clinic/services", { method: "POST", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	updateService: (
		id: string | number,
		data: {
			service_name: string;
			doctor_id?: number | null;
			price: number;
			type: "main" | "sub";
			notes?: string | null;
		},
		token?: string | null,
	) => request(`/clinic/services/${id}`, { method: "PUT", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	settings: (token?: string | null) =>
		request("/clinic/settings", { token: token ?? getOrganizationToken() ?? undefined }),
	updateSettings: (
		data:
			| {
					name: string;
					email?: string | null;
					phone?: string | null;
					address?: string | null;
					description?: string | null;
					website?: string | null;
					governorate_id?: number | null;
					city_id?: number | null;
					area_id?: number | null;
					specialty_id?: number | null;
			  }
			| FormData,
		token?: string | null,
	) => {
		// Laravel may not reliably parse multipart form data on raw PUT requests.
		// Use POST + _method override when FormData is provided.
		if (typeof FormData !== "undefined" && data instanceof FormData) {
			data.set("_method", "PUT");
			return request("/clinic/settings", { method: "POST", body: data, token: token ?? getOrganizationToken() ?? undefined });
		}
		return request("/clinic/settings", { method: "PUT", body: data, token: token ?? getOrganizationToken() ?? undefined });
	},
	modules: (token?: string | null) =>
		request("/clinic/modules", { token: token ?? getOrganizationToken() ?? undefined }),
	inventoryCategories: (token?: string | null) =>
		request("/clinic/inventory/categories", { token: token ?? getOrganizationToken() ?? undefined }),
	inventoryMovements: (token?: string | null) =>
		request("/clinic/inventory/movements", { token: token ?? getOrganizationToken() ?? undefined }),
	chats: (token?: string | null) =>
		request("/clinic/chats", { token: token ?? getOrganizationToken() ?? undefined }),
};

// Radiology Center dashboard (Bearer token)
export const radiologyApi = {
	dashboard: (token?: string | null) =>
		request("/radiologyCenter/dashboard", { token: token ?? getOrganizationToken() ?? undefined }),
	financial: (params?: Record<string, string>, token?: string | null) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/radiologyCenter/financial${q}`, { token: token ?? getOrganizationToken() ?? undefined });
	},
	notifications: (params?: Record<string, string>, token?: string | null) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/radiologyCenter/notifications${q}`, { token: token ?? getOrganizationToken() ?? undefined });
	},
	rays: (params?: Record<string, string>, token?: string | null) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/radiologyCenter/rays${q}`, { token: token ?? getOrganizationToken() ?? undefined });
	},
	rayCategories: (params?: Record<string, string>, token?: string | null) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/radiologyCenter/ray-categories${q}`, { token: token ?? getOrganizationToken() ?? undefined });
	},
	rayCategory: (id: string | number, token?: string | null) =>
		request(`/radiologyCenter/ray-categories/${id}`, { token: token ?? getOrganizationToken() ?? undefined }),
	createRayCategory: (
		data: {
			name: string;
			description?: string | null;
		},
		token?: string | null,
	) => request("/radiologyCenter/ray-categories", { method: "POST", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	updateRayCategory: (
		id: string | number,
		data: {
			name: string;
			description?: string | null;
		},
		token?: string | null,
	) => request(`/radiologyCenter/ray-categories/${id}`, { method: "PUT", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	deleteRayCategory: (id: string | number, token?: string | null) =>
		request(`/radiologyCenter/ray-categories/${id}`, { method: "DELETE", token: token ?? getOrganizationToken() ?? undefined }),
	ray: (id: string | number, token?: string | null) =>
		request(`/radiologyCenter/rays/${id}`, { token: token ?? getOrganizationToken() ?? undefined }),
	createRay: (
		data:
			| {
					patient_id: number;
					reservation_id?: number | null;
					date: string;
					payment: "paid" | "not_paid";
					cost?: number | null;
					report?: string | null;
			  }
			| FormData,
		token?: string | null,
	) => request("/radiologyCenter/rays", { method: "POST", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	updateRay: (
		id: string | number,
		data:
			| {
					patient_id: number;
					reservation_id?: number | null;
					date: string;
					payment: "paid" | "not_paid";
					cost?: number | null;
					report?: string | null;
			  }
			| FormData,
		token?: string | null,
	) => request(`/radiologyCenter/rays/${id}`, { method: "PUT", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	deleteRay: (id: string | number, token?: string | null) =>
		request(`/radiologyCenter/rays/${id}`, { method: "DELETE", token: token ?? getOrganizationToken() ?? undefined }),
	users: (params?: Record<string, string>, token?: string | null) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/radiologyCenter/users${q}`, { token: token ?? getOrganizationToken() ?? undefined });
	},
	user: (id: string | number, token?: string | null) =>
		request(`/radiologyCenter/users/${id}`, { token: token ?? getOrganizationToken() ?? undefined }),
	createUser: (
		data: {
			name: string;
			email: string;
			password: string;
			phone?: string;
			job_title?: string;
			role_id: number;
			permission_ids?: number[];
		},
		token?: string | null,
	) => request("/radiologyCenter/users", { method: "POST", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	updateUser: (
		id: string | number,
		data: {
			name: string;
			email: string;
			password?: string;
			phone?: string;
			job_title?: string;
			role_id: number;
			permission_ids?: number[];
			status?: "active" | "inactive";
		},
		token?: string | null,
	) => request(`/radiologyCenter/users/${id}`, { method: "PUT", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	deleteUser: (id: string | number, token?: string | null) =>
		request(`/radiologyCenter/users/${id}`, { method: "DELETE", token: token ?? getOrganizationToken() ?? undefined }),
	patients: (params?: Record<string, string>, token?: string | null) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/radiologyCenter/patients${q}`, { token: token ?? getOrganizationToken() ?? undefined });
	},
	patient: (id: string | number, token?: string | null) =>
		request(`/radiologyCenter/patients/${id}`, { token: token ?? getOrganizationToken() ?? undefined }),
	patientHistory: (id: string | number, token?: string | null) =>
		request(`/radiologyCenter/patients/${id}/history`, { token: token ?? getOrganizationToken() ?? undefined }),
	createPatient: (
		data: {
			name: string;
			phone: string;
			email?: string;
			address?: string;
			password?: string;
			whatsapp_number?: string;
			age?: string;
			gender: "male" | "female";
			blood_group?: "A+" | "A-" | "B+" | "B-" | "O+" | "O-" | "AB+" | "AB-";
			height?: string;
			weight?: string;
		},
		token?: string | null,
	) => request("/radiologyCenter/patients", { method: "POST", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	updatePatient: (
		id: string | number,
		data: {
			name: string;
			phone: string;
			email?: string;
			address?: string;
			password?: string;
			whatsapp_number?: string;
			age?: string;
			gender: "male" | "female";
			blood_group?: "A+" | "A-" | "B+" | "B-" | "O+" | "O-" | "AB+" | "AB-";
			height?: string;
			weight?: string;
			status?: "active" | "inactive";
		},
		token?: string | null,
	) => request(`/radiologyCenter/patients/${id}`, { method: "PUT", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	deletePatient: (id: string | number, token?: string | null) =>
		request(`/radiologyCenter/patients/${id}`, { method: "DELETE", token: token ?? getOrganizationToken() ?? undefined }),
	assignPatientByCode: (
		data: { patient_code?: string; qr_value?: string },
		token?: string | null,
	) => request("/radiologyCenter/patients/assign", { method: "POST", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	unassignPatient: (id: string | number, token?: string | null) =>
		request(`/radiologyCenter/patients/${id}/unassign`, { method: "POST", token: token ?? getOrganizationToken() ?? undefined }),
	roles: (token?: string | null) =>
		request("/radiologyCenter/roles", { token: token ?? getOrganizationToken() ?? undefined }),
	role: (id: string | number, token?: string | null) =>
		request(`/radiologyCenter/roles/${id}`, { token: token ?? getOrganizationToken() ?? undefined }),
	createRole: (
		data: { name: string; permission_ids?: number[] },
		token?: string | null,
	) => request("/radiologyCenter/roles", { method: "POST", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	updateRole: (
		id: string | number,
		data: { name: string; permission_ids?: number[] },
		token?: string | null,
	) => request(`/radiologyCenter/roles/${id}`, { method: "PUT", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	deleteRole: (id: string | number, token?: string | null) =>
		request(`/radiologyCenter/roles/${id}`, { method: "DELETE", token: token ?? getOrganizationToken() ?? undefined }),
	permissions: (token?: string | null) =>
		request("/radiologyCenter/permissions", { token: token ?? getOrganizationToken() ?? undefined }),
};

// Medical Laboratory dashboard (Bearer token)
export const labApi = {
	dashboard: (token?: string | null) =>
		request("/medicalLaboratory/dashboard", { token: token ?? getOrganizationToken() ?? undefined }),
	financial: (params?: Record<string, string>, token?: string | null) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/medicalLaboratory/financial${q}`, { token: token ?? getOrganizationToken() ?? undefined });
	},
	notifications: (params?: Record<string, string>, token?: string | null) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/medicalLaboratory/notifications${q}`, { token: token ?? getOrganizationToken() ?? undefined });
	},
	reservations: (params?: Record<string, string>, token?: string | null) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/medicalLaboratory/reservations/data${q}`, { token: token ?? getOrganizationToken() ?? undefined });
	},
	patients: (params?: Record<string, string>, token?: string | null) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/medicalLaboratory/patients${q}`, { token: token ?? getOrganizationToken() ?? undefined });
	},
	patientHistory: (id: string | number, token?: string | null) =>
		request(`/medicalLaboratory/patients/${id}/history`, { token: token ?? getOrganizationToken() ?? undefined }),
	createPatient: (
		data: {
			name: string;
			phone: string;
			email?: string;
			address?: string;
			password?: string;
			whatsapp_number?: string;
			age?: string;
			gender: "male" | "female";
			blood_group?: "A+" | "A-" | "B+" | "B-" | "O+" | "O-" | "AB+" | "AB-";
			height?: string;
			weight?: string;
		},
		token?: string | null,
	) => request("/medicalLaboratory/patients", { method: "POST", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	assignPatientByCode: (
		data: { patient_code?: string; qr_value?: string },
		token?: string | null,
	) => request("/medicalLaboratory/patients/assign", { method: "POST", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	unassignPatient: (id: string | number, token?: string | null) =>
		request(`/medicalLaboratory/patients/${id}/unassign`, { method: "POST", token: token ?? getOrganizationToken() ?? undefined }),
	users: (params?: Record<string, string>, token?: string | null) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/medicalLaboratory/users${q}`, { token: token ?? getOrganizationToken() ?? undefined });
	},
	user: (id: string | number, token?: string | null) =>
		request(`/medicalLaboratory/users/${id}`, { token: token ?? getOrganizationToken() ?? undefined }),
	createUser: (
		data: {
			name: string;
			email: string;
			password: string;
			phone?: string;
			job_title?: string;
			role_id: number;
			permission_ids?: number[];
		},
		token?: string | null,
	) => request("/medicalLaboratory/users", { method: "POST", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	updateUser: (
		id: string | number,
		data: {
			name: string;
			email: string;
			password?: string;
			phone?: string;
			job_title?: string;
			role_id: number;
			permission_ids?: number[];
		},
		token?: string | null,
	) => request(`/medicalLaboratory/users/${id}`, { method: "PUT", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	deactivateUser: (id: string | number, token?: string | null) =>
		request(`/medicalLaboratory/users/${id}`, { method: "DELETE", token: token ?? getOrganizationToken() ?? undefined }),
	restoreUser: (id: string | number, token?: string | null) =>
		request(`/medicalLaboratory/users/${id}/restore`, { method: "PUT", token: token ?? getOrganizationToken() ?? undefined }),
	roles: (token?: string | null) =>
		request("/medicalLaboratory/roles", { token: token ?? getOrganizationToken() ?? undefined }),
	role: (id: string | number, token?: string | null) =>
		request(`/medicalLaboratory/roles/${id}`, { token: token ?? getOrganizationToken() ?? undefined }),
	createRole: (
		data: { name: string; permission_ids: number[] },
		token?: string | null,
	) => request("/medicalLaboratory/roles", { method: "POST", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	updateRole: (
		id: string | number,
		data: { name: string; permission_ids: number[] },
		token?: string | null,
	) => request(`/medicalLaboratory/roles/${id}`, { method: "PUT", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	deleteRole: (id: string | number, token?: string | null) =>
		request(`/medicalLaboratory/roles/${id}`, { method: "DELETE", token: token ?? getOrganizationToken() ?? undefined }),
	permissions: (token?: string | null) =>
		request("/medicalLaboratory/permissions", { token: token ?? getOrganizationToken() ?? undefined }),
	serviceCategories: (params?: Record<string, string>, token?: string | null) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/medicalLaboratory/service-categories${q}`, { token: token ?? getOrganizationToken() ?? undefined });
	},
	serviceCategory: (id: string | number, token?: string | null) =>
		request(`/medicalLaboratory/service-categories/${id}`, { token: token ?? getOrganizationToken() ?? undefined }),
	createServiceCategory: (
		data: { category_name: string; is_active: boolean },
		token?: string | null,
	) => request("/medicalLaboratory/service-categories", { method: "POST", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	updateServiceCategory: (
		id: string | number,
		data: { category_name: string; is_active: boolean },
		token?: string | null,
	) => request(`/medicalLaboratory/service-categories/${id}`, { method: "PUT", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	deleteServiceCategory: (id: string | number, token?: string | null) =>
		request(`/medicalLaboratory/service-categories/${id}`, { method: "DELETE", token: token ?? getOrganizationToken() ?? undefined }),
	services: (params?: Record<string, string>, token?: string | null) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/medicalLaboratory/services${q}`, { token: token ?? getOrganizationToken() ?? undefined });
	},
	service: (id: string | number, token?: string | null) =>
		request(`/medicalLaboratory/services/${id}`, { token: token ?? getOrganizationToken() ?? undefined }),
	createService: (
		data: {
			lab_service_category_id: number;
			name: string;
			price: number;
			unit: string;
			normal_range: string;
			notes?: string | null;
		},
		token?: string | null,
	) => request("/medicalLaboratory/services", { method: "POST", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	updateService: (
		id: string | number,
		data: {
			lab_service_category_id: number;
			name: string;
			price: number;
			unit: string;
			normal_range: string;
			notes?: string | null;
		},
		token?: string | null,
	) => request(`/medicalLaboratory/services/${id}`, { method: "PUT", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	deleteService: (id: string | number, token?: string | null) =>
		request(`/medicalLaboratory/services/${id}`, { method: "DELETE", token: token ?? getOrganizationToken() ?? undefined }),
	medicalAnalyses: (params?: Record<string, string>, token?: string | null) => {
		const q = params ? "?" + new URLSearchParams(params).toString() : "";
		return request(`/medicalLaboratory/medical-analyses${q}`, { token: token ?? getOrganizationToken() ?? undefined });
	},
	medicalAnalysis: (id: string | number, token?: string | null) =>
		request(`/medicalLaboratory/medical-analyses/${id}`, { token: token ?? getOrganizationToken() ?? undefined }),
	createMedicalAnalysis: (
		data:
			| {
					patient_id: number;
					reservation_id?: number | null;
					date: string;
					doctor_name?: string | null;
					payment: "paid" | "not_paid";
					report?: string | null;
					services?: Array<{ lab_service_id: number; value?: string | null }>;
			  }
			| FormData,
		token?: string | null,
	) => request("/medicalLaboratory/medical-analyses", { method: "POST", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	updateMedicalAnalysis: (
		id: string | number,
		data:
			| {
					patient_id: number;
					reservation_id?: number | null;
					date: string;
					doctor_name?: string | null;
					payment: "paid" | "not_paid";
					report?: string | null;
					services?: Array<{ lab_service_id: number; value?: string | null }>;
			  }
			| FormData,
		token?: string | null,
	) => request(`/medicalLaboratory/medical-analyses/${id}`, { method: "PUT", body: data, token: token ?? getOrganizationToken() ?? undefined }),
	deleteMedicalAnalysis: (id: string | number, token?: string | null) =>
		request(`/medicalLaboratory/medical-analyses/${id}`, { method: "DELETE", token: token ?? getOrganizationToken() ?? undefined }),
};

export { BASE_URL };

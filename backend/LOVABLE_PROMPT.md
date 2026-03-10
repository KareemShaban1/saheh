# Lovable prompt – Frontend + all dashboards (Laravel API backend)

Copy the block below into Lovable. Keep it intact to save tokens; add only your base URL and auth details.

---

## Prompt (copy from here)

```
Build the full frontend and all dashboard UIs for a medical/clinic SaaS. Backend: existing Laravel app (APIs only). Use one stack (React/Next or Vue), Tailwind, responsive, WCAG-friendly. Consume Laravel REST APIs below; base URL = [SET_BASE_URL e.g. http://127.0.0.1:8000].

Auth:
- Patient app: POST /api/patient/login (email, password); use Bearer token for /api/patient/*.
- Web/session: cookie session after login to Laravel (for clinic/admin dashboards if you use same origin).

Public (no auth):
- Home: hero, stats (clinics, doctors, appointments_today, patients), CTAs. GET / or use GET /api/patient/home (with auth) for counts.
- Clinics list: filters (area_id), cards (name, specialty, governorate, city, rating, reviews_count). GET /clinics (web) or GET /api/patient/clinics.
- Clinic detail: GET /clinic/{id} or GET /api/patient/clinic/{id}. Show name, specialty, address, doctors, rating, reviews; booking CTA.
- Doctor detail: GET /doctor/{id} or GET /api/patient/doctor/{id}. Show name, specialty, clinic, slots/numbers for booking.
- Medical labs list + detail: GET /medical-laboratories, GET /medical-laboratory/{id} or GET /api/patient/medical_laboratories, medical_laboratory/{id}.
- Radiology centers list + detail: GET /radiology-centers, GET /radiology-center/{id} or GET /api/patient/radiology_centers, radiology_center/{id}.

Helpers (web): GET /cities/by-governorate?governorate_id=; GET /areas/by-city?city_id=.

Patient dashboard (auth: patient_api token):
- Layout: sidebar or top nav; links to Appointments, Profile, Reservations, Prescriptions, Rays, Medical analyses, Chronic diseases, Glasses distance, Reviews.
- Appointments: list GET /api/patient/reservations; create POST /api/patient/store_reservation; show GET /api/patient/reservation/{id}; status POST change_reservation_status/{id}/{status}.
- Profile: GET /api/patient/profile; POST update-profile, change-password.
- Clinics/doctors (same as public): GET /api/patient/clinics, clinic/{id}, doctors, doctor/{id}; GET doctor_reservation_slots_number (for booking).
- Prescriptions: GET /api/patient/prescriptions, prescription/{id}. Rays, medical_analyses, chronic_diseases, glasses_distances: same pattern (index + show).
- Reviews: POST /api/patient/reviews; PUT/DELETE reviews/{id}.

Clinic dashboard (auth: web session, same origin):
- Layout: sidebar (Dashboard, Reservations, Today, Reservation numbers, Reservation slots, Doctors, Patients, Roles, Settings), topbar clinic name + user.
- Dashboard: stats (today_reservations, total_patients, total_doctors, pending_approvals). Use GET /api/clinic/dashboard or existing GET /app/clinic (Inertia) – if building SPA, backend may expose GET /api/clinic/dashboard returning JSON.
- Reservations: list with filters from_date, to_date; columns id, patient, doctor, payment, status, acceptance, date, number/slot; actions view, edit, delete, swap slot/number. Data: GET {locale}/clinic/reservations/data (DataTables JSON). Actions: POST status, payment, acceptance; GET available_slots_numbers?reservation_id=; POST swap_slot_number/{id} (reservation_number or slot).
- Today: same table filtered to today. GET .../reservations/data?date=Y-m-d.
- Add reservation: form (patient, doctor, date, reservation_number or slot, payment, status, cost). GET get_res_slot_number_add?date=&doctor_id= for options; POST store.
- Edit reservation: GET edit/{id}; GET get_res_slot_number_edit?date=&res_id=; POST update/{id}.
- Trash: soft-deleted list; restore, force delete.
- Reservation numbers: per-day, per-doctor; num_of_reservations. CRUD (backend routes under reservation_numbers).
- Reservation slots: per-day, per-doctor; start_time, end_time, duration. CRUD (backend routes under reservation_slots).
- Doctors: list, add, edit (backend doctors module).
- Patients: list, show, add, edit (backend patients).
- Roles/permissions: list, add, edit (backend roles).

Admin dashboard (auth: web session):
- Layout: sidebar (Dashboard, Clinics, Medical labs, Radiology centers, Specialties, Governorates, Cities, Areas, Users, Roles, Reviews, Pending approvals, Announcements).
- Clinics: list GET .../clinics/data, CRUD.
- Medical laboratories / Radiology centers: same pattern, .../medical-laboratories/data, .../radiology-centers/data.
- Specialties, Governorates, Cities, Areas: CRUD + data endpoints.
- Users, Roles: CRUD; permissions for roles.
- Reviews: list, show.
- Pending: approve clinic/lab/radiology (temp data).
- Announcements: CRUD.

Radiology center dashboard: same structure as Clinic (reservations, patients, doctors, roles) but radiology-specific; use backend routes under radiologyCenter prefix.

Medical laboratory dashboard: same as Clinic but lab-specific; use backend routes under medicalLaboratory prefix.

Design: Primary #4338ca, secondary #2563eb; cards 8–12px radius; tables with sort/filter/pagination; forms with validation errors; modals for confirm; toasts for success/error. Mobile-first.
```

---

## After pasting in Lovable

1. Replace `[SET_BASE_URL]` with your Laravel base URL (e.g. `http://127.0.0.1:8000` or your production URL).
2. If clinic/admin dashboards are not yet exposed as JSON APIs, either add Laravel API routes that return JSON for the same data (e.g. reservations list, dashboard stats) or build the frontend to use the existing web routes with session cookies (same-origin).
3. For patient app, use `Authorization: Bearer {token}` after `POST /api/patient/login`; store token in localStorage or cookie per your stack.

---

## API reference (Laravel)

| Area | Method | Endpoint | Notes |
|------|--------|----------|--------|
| Auth | POST | /api/patient/login | email, password |
| Auth | POST | /api/patient/register | patient signup |
| Patient | GET | /api/patient/home | stats (auth) |
| Patient | GET | /api/patient/clinics | list |
| Patient | GET | /api/patient/clinic/{id} | detail |
| Patient | GET | /api/patient/doctors | list |
| Patient | GET | /api/patient/doctor/{id} | detail |
| Patient | GET | /api/patient/doctor_reservation_slots_number | query: date, doctor_id (for booking) |
| Patient | GET | /api/patient/reservations | list (auth) |
| Patient | GET | /api/patient/reservation/{id} | show |
| Patient | POST | /api/patient/store_reservation | create booking |
| Patient | GET | /api/patient/prescriptions, rays, medical_analyses, chronic_diseases, glasses_distances | index + show by id |
| Patient | GET | /api/patient/medical_laboratories, medical_laboratory/{id} | labs |
| Patient | GET | /api/patient/radiology_centers, radiology_center/{id} | radiology |
| Clinic | GET | /api/clinic/dashboard | stats (web session) |
| Web | GET | /clinics, /clinic/{id}, /doctor/{id}, /medical-laboratories, /radiology-centers | public pages (HTML or use for SSR) |
| Web | GET | /cities/by-governorate, /areas/by-city | dropdowns |

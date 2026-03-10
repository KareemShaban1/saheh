# Frontend & Dashboards – Project Prompt (for AI)

Use this file as a **single master prompt** when you ask an AI to redesign or rebuild the frontend for this Laravel project (public site + all dashboards).

Copy everything inside the fenced block below into your AI tool, then add any extra instructions you want (e.g. “focus on dark mode”, “optimize for mobile”, etc.).

---

## Master project prompt (copy from here)

```text
You are designing and implementing the frontend for an existing multi‑module medical SaaS built on **Laravel**.

### Tech stack (required)
- **Backend**: Laravel (multi-module structure under `Modules/*`, existing Blade views that will be gradually replaced).
- **Target frontend architecture for this project (use this by default)**:
  - **Laravel + Inertia.js + Vue 3 + Tailwind CSS**:
    - Inertia as the glue between Laravel routes/controllers and Vue pages.
    - Vue 3 with `<script setup>` and single-file components for all pages and shared layouts.
    - Tailwind CSS for styling (utility-first, responsive, easy to integrate with Vite).
  - Keep a thin Blade layer only for the Inertia entry points (root app shells).
- Acceptable alternatives (only if explicitly requested):
  - Hybrid: keep some legacy Blade pages while new functionality is Inertia/Vue.
  - Laravel Blade + Tailwind + Alpine.js for small, isolated views that don’t justify SPA behaviour.
- Do NOT propose React or Angular; the standard for this project is **Laravel + Inertia + Vue 3 + Tailwind**.

### Global design system (applies to all Vue/Inertia pages)
- **Brand & layout**
  - Clean, medical/clinic-friendly UI: lots of white space, clear typography, calm accent colors.
  - Use a primary accent in the blue/indigo family and a secondary accent for highlights; keep everything WCAG AA accessible.
  - Consistent border-radius (8–12px), subtle shadows for cards, clear visual hierarchy.
  - Mobile‑first responsive layout for all pages (public and backoffice dashboards).
  - Use a modern sans-serif font (e.g. `system-ui`, Inter, or similar).
- **Components**
  - Reusable layout shells for:
    - Public frontend (`frontend/layouts/app.blade.php`, `header`, `footer`).
    - Admin dashboard (`backend/dashboards/admin/*`).
    - Clinic dashboard (`backend/dashboards/clinic/*`).
    - Radiology center dashboard (`backend/dashboards/radiologyCenter/*`).
    - Medical laboratory dashboard (`backend/dashboards/medicalLaboratory/*`).
    - Patient dashboard (`backend/dashboards/patient/*`).
  - Reusable components for:
    - Navigation (topbars, sidebars, breadcrumbs).
    - Data tables (with search, sorting, paging hooks for DataTables/JS).
    - Forms (labels, inputs, selects, validation errors, inline help).
    - Modals, confirmation dialogs, alerts/toasts.
    - Stats/metric cards (for dashboards).

### Project structure & pages to respect
Design and refactor with the current Laravel structure in mind – keep routes, controllers, and modules conceptually the same, but expose them through **Inertia controllers + Vue pages** instead of direct Blade rendering. Existing Blade views should remain during migration but new work should target Vue pages.

For each area below, assume:
- There is (or will be) an **Inertia route** (`Route::get(...)->middleware('inertia')`) that returns something like `Inertia::render('Frontend/Home/Index', [...data])`.
- There is a matching **Vue page component** under `resources/js/Pages/...` with a shared layout from `resources/js/Layouts/...`.
- Any existing Blade view is legacy; its structure informs the new Vue UI but should not be the primary implementation.

#### 1. Public frontend (Inertia + Vue 3)
- **Layouts**
  - New Vue layout: `resources/js/Layouts/FrontendLayout.vue` (replaces `frontend/layouts/app.blade.php`, `header.blade.php`, `footer.blade.php`):
    - Top navigation with logo, main links (Home, Clinics, Radiology Centers, Labs, Doctors, Contact, etc.).
    - Sticky/scroll-aware header on desktop; mobile hamburger menu using Vue state/Composition API or `<script setup>` refs.
    - Footer with quick links, contact info, and social links.
- **Home page**
  - Vue page: `resources/js/Pages/Frontend/Home/Index.vue` (inspired by `frontend/pages/home/index.blade.php` + partials under `frontend/pages/home/partials/*` (`services`, `about`, `statistics`, `pricing`, `faq`, `testimonials`, `contact`)):
    - Modern marketing landing page for the platform (hero, CTA, feature sections).
    - Clear “Find a clinic / book an appointment” primary action.
    - Reusable partial components (cards for services, testimonial sliders, pricing tiers).
- **Directory & detail pages**
  - Vue pages under `resources/js/Pages/Frontend/Clinics/*`, `RadiologyCenters/*`, `MedicalLaboratories/*`, `Doctors/*` corresponding to:
    - `frontend/pages/clinics.blade.php`, `clinic-detail.blade.php`
    - `frontend/pages/radiology-centers.blade.php`, `radiology-center-detail.blade.php`
    - `frontend/pages/medical-laboratories.blade.php`, `medical-laboratory-detail.blade.php`
    - `frontend/pages/doctor-detail.blade.php`
    - Listing pages with filters (specialty, city, insurance, availability, etc.).
    - Card-based layouts with key info (name, specialty, rating, location, next available slot).
    - Detail pages with rich info: description, services, schedule/slots, booking actions.

#### 2. Clinic dashboard (Inertia + Vue 3)
Focus on a consistent backoffice shell and UX using **Vue layouts + Inertia pages**, then apply it across modules.

- **Layouts**
  - Vue layout: `resources/js/Layouts/ClinicLayout.vue` (replacing `backend/dashboards/clinic/layouts/master.blade.php` and `sidebar.blade.php`):
    - Left sidebar with grouped navigation (Dashboard, Patients, Doctors, Reservations, Settings, etc.).
    - Topbar with clinic name, user menu, notifications.
    - Use Tailwind or Bootstrap grid + utilities for responsive layout.
- **Dashboard home**
  - Vue page: `resources/js/Pages/Clinic/Dashboard/Index.vue` (inspired by `backend/dashboards/clinic/pages/dashboard/index.blade.php`):
    - Stats cards: total patients, today’s reservations, pending approvals, revenues, etc.
    - Sections for “Today’s appointments” table, quick actions, and charts (if needed).
- **Reservations module (Vue pages under `resources/js/Pages/Clinic/Reservations/*`)**
  - Index (all reservations, date filters) → `Index.vue` (replaces `...reservations/index.blade.php`).
  - Today’s reservations → `Today.vue` (replaces `...reservations/today.blade.php`).
  - Add/Edit forms → `Create.vue`, `Edit.vue` (replaces `add.blade.php`, `edit.blade.php`).
  - Trash (soft-deleted) → `Trash.vue` (replaces `trash.blade.php`).
  - Per-day reservation numbers → pages under `Clinic/ReservationNumbers/*` (replacing `num_of_reservations/*`).
  - Time-slot configuration per doctor/day → pages under `Clinic/ReservationSlots/*` (replacing `reservation_slots/*`).
    - Tables with filters, status badges, and action dropdowns (view/edit/delete, swap numbers/slots).
    - Well-structured forms for creating/editing reservations (patient, doctor, date, number/slot, payment, status, attachments).
    - Clear UX for configuring per-day reservation numbers and slots for doctors.
- **Doctors, patients, roles & settings**
  - `backend/dashboards/clinic/pages/doctors/index.blade.php`
  - `backend/dashboards/clinic/pages/patients/*` (legacy/old index files may exist).
  - `backend/dashboards/clinic/pages/roles/index.blade.php`
  - Various medical modules (tooth record, chronic diseases, glasses distance, prescriptions, etc.).
    - Use consistent tables, filters, and forms across all modules.
    - Reuse confirmation patterns and status chips.

#### 3. Other dashboards (Inertia + Vue 3)
- **Admin dashboard** (Vue layout `AdminLayout.vue`, pages under `resources/js/Pages/Admin/*`, inspired by `backend/dashboards/admin/*`)
  - Manage clinics, radiology centers, medical labs, specialties, users, roles.
  - Pages like `clinics/index`, `medical-laboratories/index`, `radiology-centers/index`, `roles/index`, `users/index`.
  - Use the same design language as clinic dashboard, but with a more “platform admin” emphasis.
- **Radiology center dashboard** (Vue layout `RadiologyCenterLayout.vue`, pages under `Pages/RadiologyCenter/*`, analogous to clinic dashboard but focused on radiology reservations, roles, and patients; based on `backend/dashboards/radiologyCenter/*`).
- **Medical laboratory dashboard** (Vue layout `MedicalLabLayout.vue`, pages under `Pages/MedicalLaboratory/*`, similar to radiology, with lab-specific reservations, patients, and results modules; based on `backend/dashboards/medicalLaboratory/*`).
- **Patient dashboard** (Vue layout `PatientLayout.vue`, pages under `Pages/Patient/*`)
  - Appointment list & booking (e.g. `Appointment/Index.vue`, `Appointment/Edit.vue`), mirroring `backend/dashboards/patient/pages/appointment/*`.
  - Simple, patient-friendly UI derived from the public site but using a dashboard shell.

### UX & implementation guidelines (Inertia + Vue)
- Keep navigation, typography, colors, and spacing consistent across all dashboards and pages.
- Use **Tailwind CSS** classes directly in Vue single-file components; create small reusable components for complex patterns (tables, forms, cards, modals).
- Use **Vue 3 + Inertia** patterns:
  - Use shared layouts (`<Layout>` components) wrapping page components.
  - Use `<Link>` from Inertia for navigation instead of `<a>` where appropriate.
  - Use Vue state/composition API for interactions (sidebars, filters, modals).
- For tabular data:
  - Either keep using existing server-side Yajra DataTables endpoints and consume them via Axios/Inertia requests, or refactor to Inertia responses that paginate via Laravel’s paginator.
- Respect existing route names and backend endpoints (e.g. `clinic.reservations.data`, `clinic.reservation_slots.data`, etc.). Convert controllers to return `Inertia::render()` instead of `view()` gradually, but do not change core business logic unless explicitly requested.

### Your task when I ask for a page/module
Whenever I specify a page (by route name, Blade path, or description):
1. **Infer where it lives** in this structure (frontend vs which dashboard/module) and its Vue page path (e.g. `Pages/Clinic/Reservations/Index.vue`).
2. **Propose or generate** a modern, responsive Inertia + Vue 3 page using:
   - Vue 3 single-file components with `<script setup>`.
   - Inertia props from the corresponding Laravel controller.
   - Tailwind CSS classes for layout and styling.
3. Keep components consistent with the rest of the system (layouts, buttons, tables, forms).
4. If an existing Blade file is referenced, use it only as a **reference**, and instead generate the corresponding Vue page and, if needed, an Inertia controller snippet.
5. Clearly separate:
   - Laravel controller changes (`Inertia::render(...)` and props).
   - Vue page/layout/component code.

Unless I say otherwise, always assume:
- **Stack**: Laravel + Inertia.js + Vue 3 + Tailwind CSS.
- The design and code must integrate cleanly into this existing clinic SaaS with its multiple dashboards and modules, replacing old Blade UIs progressively.
```

Page: Public marketing landing page for a healthcare SaaS platform that connects patients with clinics, medical laboratories, and radiology centers. Include:
- Hero: headline, subheadline, primary CTA (e.g. "Find a clinic" or "Get started"), secondary CTA, optional hero image or illustration. Subtle gradient background using primary (#4338ca) and secondary (#2563eb).
- Value props: 3–4 short sections (e.g. "Book appointments", "Trusted labs", "Digital reports", "One platform") with icons and one-line descriptions. Staggered fade-in or slide-up on scroll.
- How it works: 3 steps (e.g. Search → Choose → Book) with clear numbering or icons.
- Types of providers: tabs or cards for "Clinics", "Medical laboratories", "Radiology centers" with short descriptions.
- Social proof: optional testimonial strip or "Trusted by X centers" with logos/numbers.
- Final CTA section and footer with links (About, Contact, Privacy, Terms). Header: logo, nav (Find clinics, For providers, About), Login and Register buttons.
- Animations: hero fade-in, section reveal on scroll, hover on cards and buttons. Fully responsive.
Stack: [Specify e.g. Laravel Blade + Tailwind CSS]
```

---

### 2. Clinics / Medical laboratories / Radiology centers listing page

```
[Start with Master prompt above, then add:]

Page: Browse/search page listing clinics, medical laboratories, and radiology centers. Include:
- Page title and short description (e.g. "Find a clinic, lab, or radiology center near you").
- Filters sidebar or filter bar: location/search, type (Clinic / Laboratory / Radiology), optional rating, availability. Use dropdowns or chips. Clear filters button.
- Results area: grid or list of cards. Each card: image/placeholder, name, type badge (Clinic/Lab/Radiology), short address or area, rating (stars or number), key info (e.g. "Open now", "Home visit"), "View details" button. Cards have hover lift and smooth transition.
- Pagination or "Load more" at bottom.
- Empty state when no results: illustration or icon, message, suggestion to change filters.
- Optional: map view toggle or integrated map strip.
- Staggered card entrance on load. Responsive: filters collapse to drawer or horizontal scroll on mobile.
Stack: [Specify e.g. Laravel Blade + Tailwind CSS]
```

---

### 3. Clinic / Medical laboratory / Radiology center details page

```
[Start with Master prompt above, then add:]

Page: Single facility details page (works for clinic, medical laboratory, or radiology center). Include:
- Header: breadcrumb (Home > Clinics > [Facility name]), facility name, type badge, rating and review count. Optional cover image or gallery strip with light transition.
- Main layout: two columns (main + sidebar) or stacked on mobile.
- Main: About (description), Services offered (list or tags), Working hours (table or list), optional "Available tests" or "Specialties" for labs/radiology. Section headings with subtle underline or accent.
- Sidebar (or below on mobile): Address with map link, phone, website; "Book appointment" or "Request visit" primary CTA; optional quick actions (Call, Directions). Sticky sidebar on desktop.
- Optional: Reviews section (rating breakdown, list of reviews with date and text).
- Optional: Related or "Other facilities nearby" cards at bottom.
- Animations: image/section fade-in, sticky sidebar transition, button hover. Fully responsive.
Stack: [Specify e.g. Laravel Blade + Tailwind CSS]
```

---

### 4. Register page (clinic / medical laboratory / radiology center)

```
[Start with Master prompt above, then add:]

Page: Registration page for healthcare providers (clinic, medical laboratory, or radiology center). Include:
- Centered card or split layout (form on one side, branding/illustration on the other). Use primary/secondary gradient or soft tint on the non-form side.
- Form: Facility type selector (Clinic / Medical laboratory / Radiology center) — dropdown or radio cards. Then: Facility name, Email, Phone, Password (with show/hide), Confirm password, optional Address/City. Checkbox for terms and conditions with link. "Create account" primary button, link to Login page below.
- Validation: required fields and clear error messages (inline or below field). Success state or redirect hint.
- Header: logo and "Already have an account? Log in" link. Footer: minimal (e.g. Privacy, Terms). Animations: card fade-in or slide-in, input focus ring, button hover. Fully responsive; on mobile single column.
Stack: [Specify e.g. Laravel Blade + Tailwind CSS]
```

---

### 5. Login page (clinic / medical laboratory / radiology center)

```
[Start with Master prompt above, then add:]

Page: Login page for healthcare providers (clinic, medical laboratory, or radiology center). Include:
- Centered card or split layout (form on one side, branding/illustration or value message on the other). Use primary/secondary gradient or soft tint on the non-form side.
- Form: Email, Password (with show/hide toggle), "Remember me" checkbox, "Forgot password?" link. "Log in" primary button. "Don't have an account? Register" link below.
- Optional: subtle divider and "Or continue with" (e.g. Google) if applicable.
- Header: logo and link back to home or landing. Footer: minimal. Validation and error message area (e.g. invalid credentials). Animations: card fade-in or slide-in, input focus, button hover. Fully responsive; single column on mobile.
Stack: [Specify e.g. Laravel Blade + Tailwind CSS]
```

---

*Colors: #4338ca (primary), #2563eb (secondary). Keep animations creative but short and professional.*

# Software Requirements Specification (SRS)
# Clinic Management SaaS System

**Document Version:** 1.0  
**Date:** 2025  
**Project:** Clinic System SaaS – Multi-Organization Healthcare Management Platform

---

## Table of Contents
1. [Introduction](#1-introduction)
2. [Overall Description](#2-overall-description)
3. [User Roles and Authentication](#3-user-roles-and-authentication)
4. [Organizations and Tenancy](#4-organizations-and-tenancy)
5. [System Features and Functional Requirements](#5-system-features-and-functional-requirements)
6. [API Specification](#6-api-specification)
7. [Data Model and Database](#7-data-model-and-database)
8. [External Integrations](#8-external-integrations)
9. [Non-Functional Requirements](#9-non-functional-requirements)

---

## 1. Introduction

### 1.1 Purpose
This Software Requirements Specification (SRS) describes the functional and non-functional requirements for the **Clinic Management SaaS System** – a multi-tenant web application for managing clinics, medical laboratories, and radiology centers. It serves administrators, healthcare staff, and patients.

### 1.2 Scope
The system provides:
- **Administration panel** for system-wide management
- **Clinic module** for appointment scheduling, patient records, prescriptions, rays, and chronic diseases
- **Medical Laboratory module** for lab services, analysis, inventory, and patient management
- **Radiology Center module** for imaging services, rays, and patient management
- **Patient portal** (web and API) for appointments, medical records, and reviews
- **Public frontend** for browsing and discovering healthcare organizations

### 1.3 Definitions and Acronyms
| Term | Definition |
|------|------------|
| SaaS | Software as a Service |
| SRS | Software Requirements Specification |
| API | Application Programming Interface |
| RBAC | Role-Based Access Control |
| CRUD | Create, Read, Update, Delete |

---

## 2. Overall Description

### 2.1 Product Perspective
- **Platform:** Laravel 10.x PHP framework
- **Frontend:** Blade templates, Vue.js (clinic dashboard), Livewire
- **Database:** MySQL with multi-tenancy (separate DB per clinic)
- **Authentication:** Laravel Sanctum (API), Session (web), Fortify, Jetstream

### 2.2 Product Functions
1. **Administration** – Manage specialties, locations (governorates, cities, areas), users, roles, organizations, reviews, backups
2. **Clinic Management** – Patients, reservations (manual and online), prescriptions, rays, chronic diseases, glasses distance, medicines, service fees, tooth records
3. **Medical Laboratory** – Lab service categories, lab services, service options, medical analysis, rays, patients, inventory, chat
4. **Radiology Center** – Rays, patients, service fees, inventory
5. **Patient** – Browse organizations, book appointments, view medical profile, reviews
6. **Integrations** – WhatsApp notifications, Zoom meetings, PDF generation, QR codes, barcodes

### 2.3 User Classes and Characteristics
- **Admin** – System administrator, no access to tenant data
- **Clinic Staff** – Doctors, assistants; belong to clinics
- **Medical Laboratory Staff** – Technicians; belong to labs
- **Radiology Center Staff** – Technicians; belong to radiology centers
- **Patients** – End users seeking healthcare services

---

## 3. User Roles and Authentication

### 3.1 Authentication Guards
| Guard | Provider | Use Case |
|-------|----------|----------|
| `web` | users | Clinic staff (session) |
| `medical_laboratory` | users | Medical lab staff (session) |
| `radiology_center` | users | Radiology center staff (session) |
| `patient` | patients | Patient web portal (session) |
| `admin` | admins | Admin panel (session) |
| `patient_api` | patients | Patient mobile/API (Sanctum) |

### 3.2 Authorization
- **Spatie Laravel Permission** – Roles and permissions
- **Policies** – Admin, Announcement, Area, City, Clinic, Doctor, Governorate, Inventory, OrganizationAssignment, OrganizationInventory, RadiologyCenter, Service, Specialty, ToothRecord, User
- **AuthorizeCheck trait** – Controller-level permission checks

---

## 4. Organizations and Tenancy

### 4.1 Organization Types
1. **Clinic** – Uses Stancl Tenancy; each clinic can have a tenant database
2. **Medical Laboratory** – Central database with `organization_id` scope
3. **Radiology Center** – Central database with `organization_id` scope

### 4.2 Tenant Model
- **Tenant:** `App\Models\Clinic`
- **Identification:** By domain/subdomain
- **Bootstrappers:** Database, Cache, Filesystem, Queue
- **Database:** Prefix `tenant` + tenant ID

### 4.3 Organization Registration Flow
1. User registers (clinic / medical lab / radiology center)
2. Data stored in temp tables (approval workflow)
3. Admin approves via pending organizations
4. Activation token sent (email/WhatsApp)
5. User activates account via link (e.g., `/activate-clinic/{token}`)

---

## 5. System Features and Functional Requirements

### 5.1 Administration Module

#### 5.1.1 Dashboard
- View system overview and statistics

#### 5.1.2 Geographic Management
- **Governorates** – CRUD, data listing
- **Cities** – CRUD, filter by governorate
- **Areas** – CRUD, filter by city

#### 5.1.3 Specialties
- CRUD for clinic specialties

#### 5.1.4 Organizations Management
- **Clinics** – List, update status (activate/deactivate)
- **Medical Laboratories** – List, update status
- **Radiology Centers** – List, update status

#### 5.1.5 Organization Approval (Temp Data)
- **Pending Clinics** – Approve, destroy
- **Pending Medical Laboratories** – Approve, destroy
- **Pending Radiology Centers** – Approve, destroy

#### 5.1.6 Users and Roles
- User CRUD (admins)
- Role CRUD with permissions

#### 5.1.7 Reviews Management
- List, create, update, delete reviews
- Update review status

#### 5.1.8 Announcements
- CRUD for system-wide announcements

#### 5.1.9 Backups
- Create, list, download, delete backups

---

### 5.2 Clinic Module

#### 5.2.1 Dashboard
- Clinic dashboard (Blade and Vue.js options)
- Log viewer

#### 5.2.2 Reservations
- **Online Reservations** – CRUD, status, payment, acceptance, trash, restore, force delete
- **Reservation Options** – Status, payment, acceptance
- **System Control** – Reservation control settings

#### 5.2.3 Patient-Related
- **Rays** – CRUD, trash, restore, force delete
- **Medical Analysis** – CRUD, trash, restore, force delete
- **Chronic Diseases** – Managed via Modules
- **Glasses Distance** – Managed via Modules
- **Prescriptions** – Managed via Modules
- **Medicines** – Managed via Modules

#### 5.2.4 Reservations Setup
- **Reservation Numbers** – Add/edit number of reservations per doctor/date
- **Reservation Slots** – Add/edit time slots per doctor/date

#### 5.2.5 Fees
- Daily fees, all fees listing

#### 5.2.6 Service Fees
- CRUD for clinic service fees (per doctor)
- Service options (e.g., for prescriptions/consultations)

#### 5.2.7 Type Management
- CRUD for types (used across modules)

#### 5.2.8 Tooth Record
- Show, store, delete tooth records per patient

#### 5.2.9 Settings
- Clinic settings
- Zoom settings (API key, secret)
- Reservation settings (numbers vs slots)

#### 5.2.10 Roles and Permissions
- Role CRUD, permissions management

#### 5.2.11 Notifications
- Mark notifications as read and redirect

#### 5.2.12 Registration
- Register clinic (store temp data)
- Activate clinic via token

---

### 5.3 Medical Laboratory Module

#### 5.3.1 Dashboard
- Lab dashboard

#### 5.3.2 Patients
- CRUD, trash, restore, force delete
- Patient card, patient PDF
- Add patient code, search
- Assign/unassign patient to lab

#### 5.3.3 Lab Services
- **Service Categories** – CRUD
- **Lab Services** – CRUD (belong to category)
- **Lab Service Options** – Get options by service/category
- **Service Options** – CRUD (key-value options for services)

#### 5.3.4 Medical Analysis
- CRUD, trash, restore, force delete
- Report generation (PDF, page report)
- Create analysis for patient

#### 5.3.5 Rays
- CRUD, trash, restore, force delete

#### 5.3.6 Fees
- List daily fees, all fees

#### 5.3.7 Users and Roles
- User CRUD, Role CRUD with permissions

#### 5.3.8 Events
- CRUD, trash, restore, force delete

#### 5.3.9 Announcements
- CRUD

#### 5.3.10 Inventory
- **Organization Inventory** – CRUD
- **Inventory Movements** – CRUD per inventory

#### 5.3.11 Chat
- Chat list, get chat by patient
- Send message to chat

#### 5.3.12 Reviews
- List, data (for display)

#### 5.3.13 Type
- CRUD for types

#### 5.3.14 Settings
- Medical laboratory settings

#### 5.3.15 Registration
- Register medical laboratory, activate via token

---

### 5.4 Radiology Center Module

#### 5.4.1 Dashboard
- Radiology center dashboard

#### 5.4.2 Patients
- Same as Medical Laboratory (CRUD, card, PDF, assign/unassign, search)

#### 5.4.3 Rays
- CRUD, trash, restore, force delete

#### 5.4.4 Service Fees
- CRUD

#### 5.4.5 Fees
- List daily fees, all fees

#### 5.4.6 Users and Roles
- User CRUD, Role CRUD

#### 5.4.7 Events
- CRUD, trash, restore, force delete

#### 5.4.8 Announcements
- CRUD

#### 5.4.9 Inventory
- Organization inventory CRUD
- Inventory movements CRUD

#### 5.4.10 Reviews, Type, Settings
- Same pattern as Medical Laboratory

#### 5.4.11 Registration
- Register radiology center, activate via token

---

### 5.5 Patient Module

#### 5.5.1 Web Portal (Blade)
- **Dashboard** – Patient dashboard
- **Appointments** – List, add, edit, data
- **Get reservation slot/number** – For booking
- **Show ray, chronic disease, glasses distance**
- **Prescription PDF** – Arabic and English
- **Rays index, chronic disease index**

#### 5.5.2 Patient Registration
- Register patient (web form)

#### 5.5.3 Patient API (Mobile/External)
- Register, login, forgot password
- Logout, update profile, change password, delete profile, get profile
- **Reservations** – List, show, store, change status
- **Clinics** – List, show
- **Chronic diseases** – List, show
- **Glasses distances** – List, show
- **Prescriptions** – List, show
- **Medical analyses** – List, show
- **Rays** – List, show
- **Doctors** – List, show, reservation slots/numbers, service fees
- **Send message** – To organization
- **Reviews** – Create, update, delete
- **Medical laboratories** – List, show
- **Radiology centers** – List, show
- **Home** – Home data

---

### 5.6 Public Frontend

#### 5.6.1 Pages
- **Home** – Landing page
- **Clinics** – List clinics
- **Medical Laboratories** – List labs
- **Radiology Centers** – List radiology centers

#### 5.6.2 Detail Pages
- Clinic detail (`/clinic/{id}`)
- Doctor detail (`/doctor/{id}`)
- Medical laboratory detail (`/medical-laboratory/{id}`)
- Radiology center detail (`/radiology-center/{id}`)

---

## 6. API Specification

### 6.1 Authentication APIs

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/access-tokens` | Get access token (guest) |
| DELETE | `/auth/access-tokens/{token?}` | Revoke token (auth) |
| POST | `/patient/login` | Patient login |
| POST | `/patient/register` | Patient registration |
| POST | `/patient/forgot-password` | Patient forgot password |

### 6.2 Patient APIs (Protected: `auth:patient_api`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/patient/logout` | Logout |
| POST | `/patient/update-profile` | Update profile |
| POST | `/patient/change-password` | Change password |
| POST | `/patient/delete-profile` | Delete profile |
| GET | `/patient/profile` | Get profile |
| GET | `/patient/reservations` | List reservations |
| GET | `/patient/reservation/{id}` | Show reservation |
| POST | `/patient/store_reservation` | Create reservation |
| POST | `/patient/change_reservation_status/{id}/{status}` | Change reservation status |
| GET | `/patient/clinics` | List clinics |
| GET | `/patient/clinic/{id}` | Show clinic |
| GET | `/patient/chronic_diseases` | List chronic diseases |
| GET | `/patient/chronic_disease/{id}` | Show chronic disease |
| GET | `/patient/glasses_distances` | List glasses distances |
| GET | `/patient/glasses_distance/{id}` | Show glasses distance |
| GET | `/patient/prescriptions` | List prescriptions |
| GET | `/patient/prescription/{id}` | Show prescription |
| GET | `/patient/medical_analyses` | List medical analyses |
| GET | `/patient/medical_analysis/{id}` | Show medical analysis |
| GET | `/patient/rays` | List rays |
| GET | `/patient/ray/{id}` | Show ray |
| GET | `/patient/doctors` | List doctors |
| GET | `/patient/doctor/{id}` | Show doctor |
| GET | `/patient/doctor_number_of_reservations` | Get reservation numbers (query: doctor_id, reservation_date) |
| GET | `/patient/doctor_reservation_slots` | Get reservation slots |
| GET | `/patient/doctor_reservation_slots_number` | Get slots or numbers (query: clinic_id, doctor_id, reservation_date) |
| GET | `/patient/doctor_services/{doctor_id}` | Get doctor service fees |
| POST | `/patient/send_message` | Send message |
| POST | `/patient/reviews` | Create review |
| PUT | `/patient/reviews/{id}` | Update review |
| DELETE | `/patient/reviews/{id}` | Delete review |
| GET | `/patient/medical_laboratories` | List medical laboratories |
| GET | `/patient/medical_laboratory/{id}` | Show medical laboratory |
| GET | `/patient/radiology_centers` | List radiology centers |
| GET | `/patient/radiology_center/{id}` | Show radiology center |
| GET | `/patient/home` | Home data |

### 6.3 Clinic Dashboard API
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/clinic/dashboard` | Clinic dashboard (auth:web) |

### 6.4 Response Format
- Uses `ApiResponseTrait` and `ApiHelperTrait` for consistent JSON responses
- Standard fields: `data`, `message`, `success`

---

## 7. Data Model and Database

### 7.1 Core Entities (Central Database)

| Entity | Description |
|--------|-------------|
| **admins** | Admin users |
| **specialties** | Clinic specialties |
| **governorates** | Geographic governorates |
| **cities** | Cities (belongs to governorate) |
| **areas** | Areas (belongs to city) |
| **clinics** | Clinic organizations (tenant model) |
| **medical_laboratories** | Medical lab organizations |
| **radiology_centers** | Radiology center organizations |
| **users** | Staff (clinic, lab, radiology) |
| **doctors** | Doctors (belong to clinic) |
| **patients** | Patient records |
| **patient_clinic** | Patient-clinic assignments |
| **patient_medical_laboratory** | Patient-lab assignments |
| **patient_radiology_center** | Patient-radiology assignments |
| **domains** | Tenant domains |
| **tenants** | Tenant records (Stancl) |

### 7.2 Clinic / Tenant Entities

| Entity | Description |
|--------|-------------|
| **reservations** | Clinic reservations |
| **online_reservations** | Online booking reservations |
| **reservation_numbers** | Number-based reservation config per doctor/date |
| **reservation_slots** | Slot-based reservation config |
| **chronic_diseases** | Patient chronic diseases |
| **rays** | X-ray/imaging records |
| **glasses_distances** | Glasses distance records |
| **prescriptions** | Prescriptions with drugs |
| **drugs** | Prescription drugs |
| **medical_analysis** | Medical analysis records |
| **medicines** | Medicine catalog |
| **reservation_service_fee** | Reservation-service fee link |
| **services** | Service fees per doctor |
| **service_options** | Service options |
| **types** | Type lookups |
| **settings** | Organization settings |
| **events** | Calendar events |
| **system_control** | Reservation control settings |
| **fees** | Fee records |
| **tooth_records** | Dental tooth records |
| **patient_reviews** | Patient reviews |
| **chats** | Chat sessions |
| **messages** | Chat messages |
| **organization_inventories** | Inventory items |
| **inventory_movements** | Inventory transactions |
| **announcements** | Announcements |

### 7.3 Medical Laboratory Specific
| Entity | Description |
|--------|-------------|
| **lab_service_categories** | Lab service categories |
| **lab_services** | Lab services (belong to category) |
| **lab_service_options** | Lab service options (with media) |
| **analysis_service_fee** | Analysis-service fee link |
| **module_services** | Module-specific service fees |

### 7.4 Supporting Tables
| Entity | Description |
|--------|-------------|
| **settings** | Key-value settings (org-scoped) |
| **temp_data** | Pending organization registrations |
| **organization_activation_tokens** | Activation tokens |
| **personal_access_tokens** | Sanctum tokens |
| **subscriptions** | Laravel Cashier subscriptions |
| **subscription_items** | Subscription items |

---

## 8. External Integrations

### 8.1 WhatsApp
- **Provider:** UltraMsg / Hypersender
- **Use:** Activation notifications for clinic, medical lab, radiology center
- **Config:** `config/whatsapp.php` – instance ID, token, country code
- **Traits:** `WhatsappTrait`

### 8.2 Zoom
- **Provider:** macsidigital/laravel-zoom
- **Use:** Create meetings for consultations
- **Config:** `config/zoom.php` – API key, secret, base URL
- **Traits:** `ZoomMeetingTrait`
- **Settings:** Zoom API key/secret stored per organization

### 8.3 PDF Generation
- **Package:** carlos-meneses/laravel-mpdf
- **Use:** Prescriptions (Arabic/English), patient cards, reports

### 8.4 Media
- **Package:** Spatie Laravel Media Library
- **Use:** Rays, medical analysis attachments

### 8.5 Broadcasting
- **Channels:** Laravel Reverb, Pusher
- **Use:** Real-time features (e.g., notifications)
- **Auth:** Supports web, medical_laboratory, radiology_center guards

### 8.6 Backup
- **Package:** Spatie Laravel Backup
- **Use:** Database and file backups

### 8.7 Barcode / QR
- **Packages:** milon/barcode, simplesoftwareio/simple-qrcode
- **Use:** Patient cards, identification

---

## 9. Non-Functional Requirements

### 9.1 Performance
- Pagination and DataTables for large lists
- Caching via tenant-aware cache (Stancl)
- Queue support for notifications and jobs

### 9.2 Security
- CSRF protection
- XSS protection
- Password hashing (bcrypt)
- Sanctum token-based API auth
- Policy-based authorization
- Organization scope (tenant isolation)

### 9.3 Localization
- **Package:** mcamara/laravel-localization
- Multi-language support (Arabic/English)
- Locale cookie, redirect, view path

### 9.4 Logging and Monitoring
- **Telescope** – Debug and monitoring
- **Log Viewer** – rap2hpoutre/laravel-log-viewer, opcodesio/log-viewer
- Performance logging channel

### 9.5 Technology Stack
| Component | Technology |
|-----------|------------|
| Backend | PHP 8.2+, Laravel 10 |
| Frontend | Blade, Vue.js, Livewire |
| Auth | Fortify, Jetstream, Sanctum |
| Database | MySQL |
| Multi-tenancy | Stancl Tenancy |
| Permissions | Spatie Laravel Permission |
| Payments | Laravel Cashier |

### 9.6 Deployment
- Artisan commands for migrations, seeders
- Tenant migrations: `database/migrations/tenants`
- Central migrations: `database/migrations`

---

## Appendix A: Route Summary

- **Web:** `/` (home), `/clinics`, `/medical-laboratories`, `/radiology-centers`, detail pages
- **Admin:** `/{locale}/admin/*` – dashboard, backups, specialties, governorates, cities, areas, clinics, medical labs, radiology centers, users, roles, temp data, announcements
- **Clinic:** `/{locale}/clinic/*` – reservations, rays, analysis, fees, settings, service fees, tooth records, etc.
- **Medical Laboratory:** `/{locale}/medical-laboratory/*` – dashboard, patients, analysis, rays, lab services, inventory, chats, etc.
- **Radiology Center:** `/{locale}/radiology-center/*` – similar structure
- **Patient:** `/{locale}/patient/*` – dashboard, appointments
- **API:** `/api/patient/*` – patient API endpoints

---

## Appendix B: Key Modules (Clinic)

| Module | Path | Purpose |
|--------|------|---------|
| Announcement | Modules/Clinic/Announcement | Announcements |
| Chat | Modules/Clinic/Chat | Chat and messages |
| ChronicDisease | Modules/Clinic/ChronicDisease | Chronic diseases |
| Dashboard | Modules/Clinic/Dashboard | Dashboard |
| Doctor | Modules/Clinic/Doctor | Doctors |
| Event | Modules/Clinic/Event | Events |
| GlassesDistance | Modules/Clinic/GlassesDistance | Glasses distance |
| Medicine | Modules/Clinic/Medicine | Medicines |
| OrganizationInventory | Modules/Clinic/OrganizationInventory | Inventory |
| Patient | Modules/Clinic/Patient | Patients |
| Prescription | Modules/Clinic/Prescription | Prescriptions |
| Reservation | Modules/Clinic/Reservation | Reservations |
| ReservationNumber | Modules/Clinic/ReservationNumber | Number-based reservations |
| ReservationSlot | Modules/Clinic/ReservationSlot | Slot-based reservations |
| Review | Modules/Clinic/Review | Reviews |
| User | Modules/Clinic/User | Users |

---

*End of Software Requirements Specification*

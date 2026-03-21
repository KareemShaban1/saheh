# Clinic SaaS Project Modules

## Project Summary

Clinic SaaS is a multi-tenant healthcare platform that supports:

- Public browsing for organizations and doctors
- Patient mobile/web flows (booking, follow-up, records, chat)
- Organization dashboards for:
  - Clinics
  - Medical laboratories
  - Radiology centers
- Super admin control panel

The platform uses organization-aware authentication and role/permission checks for dashboard users.

## Main User Domains

- **Public**
  - View organizations and doctor profiles
- **Patient**
  - Register/login/profile
  - Manage own healthcare interactions
- **Organization Users**
  - Clinic/Lab/Radiology admins and staff
  - Work inside organization dashboard based on granted permissions
- **Super Admin**
  - Platform governance and master data

## Authentication and Guards

- **Patient auth**
  - Guard: `patient_api`
  - API prefix: `/api/v1/patient/*`
- **Organization auth (clinic/lab/radiology)**
  - Guard: `organization_api`
  - API prefixes:
    - `/api/v1/clinic/*`
    - `/api/v1/medicalLaboratory/*`
    - `/api/v1/radiologyCenter/*`
- **Super admin auth**
  - Guard: `admin_api`
  - API prefix: `/api/v1/admin/*`

## Frontend Modules

## Public Module

- Home
- Clinics listing/details
- Labs listing/details
- Radiology centers listing/details
- Doctor details

## Patient Module

- Home dashboard
- Appointments/reservations
- Profile management
- Prescriptions
- Medical analyses
- Rays
- Chronic diseases
- Glasses distance
- Reviews
- Chat
- **Questionnaires** (new)

## Clinic Dashboard Module

- Dashboard and financial summary
- Reservations:
  - List/today/new/edit/details
  - Sessions context and follow-up
  - Prescription management (including drug rows)
  - Rays
  - Glasses distance
  - Teeth plan
- Reservation numbers and slots
- Doctors
- Patients and history
- Roles and permissions
- Users management
- Services
- Drugs
- **Questionnaires** (new)
- Notifications
- Announcements
- Inventory
- Settings
- Organization chat

## Medical Laboratory Dashboard Module

- Dashboard and financial summary
- Reservations
- Patients and history
- Users
- Roles and permissions
- Service categories
- Services
- Medical analyses
- Today medical analyses
- Notifications
- Organization chat
- **Questionnaires** (new)

## Radiology Dashboard Module

- Dashboard and financial summary
- Rays
- Ray categories
- Patients and history
- Users
- Roles and permissions
- Notifications
- Organization chat
- **Questionnaires** (new)

## Super Admin Module

- Dashboard and financial
- Clinics management
- Medical laboratories management
- Radiology centers management
- Specialties
- Locations (governorates/cities/areas)
- Users
- Reviews
- Announcements

## Backend Functional Modules

## Organization Core

- Organization login/register/profile/logout
- Team-based permissions middleware (`organization.api.permission`)
- Organization chat APIs

## Reservations and Clinical Workflow

- Reservation CRUD and options
- Session chain support
- Multi-payment and financial status tracking
- Reservation media/voice records

## Prescription and Drugs

- Reservation prescription CRUD
- Prescription images
- `prescription_drugs` linking for prescription-specific dose/type/frequency/period/notes
- Clinic drug catalog

## Medical Records

- Rays and ray categories
- Medical analyses and services
- Glasses distance records
- Teeth records
- Chronic diseases

## Patient Organization Assignment

- Patient assignment to organization
- Assigned flag handling
- Organization-specific access checks

## Questionnaire Module (new)

- Organization-level questionnaire builder
  - `questionnaires`
  - `questionnaire_questions`
- Patient answer storage
  - `questionnaire_answers`
- Supports question types:
  - `short_text`, `long_text`, `number`, `boolean`, `date`, `single_choice`, `multiple_choice`
- Required question enforcement
- Patient can answer only if assigned to the organization

## Cross-Cutting Modules

- Notifications
- Role/permission management
- Reviews
- Settings
- Media handling

## API Surface Map (High-Level)

- `POST /api/v1/clinic|medicalLaboratory|radiologyCenter/login`
- `POST /api/v1/clinic|medicalLaboratory|radiologyCenter/register`
- `POST /api/v1/organization/logout`
- `GET/PUT /api/v1/organization/profile`
- `GET|POST|PUT|DELETE /api/v1/clinic|medicalLaboratory|radiologyCenter/*` for domain modules
- `GET|POST /api/v1/patient/*` for patient operations
- `GET|POST|PUT|DELETE /api/v1/admin/*` for super admin operations

## Questionnaire API (Key Endpoints)

- Organization side:
  - `GET /api/v1/{org}/questionnaires`
  - `GET /api/v1/{org}/questionnaires/{id}`
  - `POST /api/v1/{org}/questionnaires`
  - `PUT /api/v1/{org}/questionnaires/{id}`
  - `DELETE /api/v1/{org}/questionnaires/{id}`
  - `GET /api/v1/{org}/questionnaires/{id}/answers`

- Patient side:
  - `GET /api/v1/patient/questionnaires?organization_type=...&organization_id=...`
  - `POST /api/v1/patient/questionnaires/{id}/answers`
  - `GET /api/v1/patient/questionnaires/{id}/answers`


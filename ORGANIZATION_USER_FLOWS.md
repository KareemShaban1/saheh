# Organization User Flows

This document explains how different organization users should use the system, end-to-end.

## Actors

- **Clinic Admin**
- **Clinic Staff (Reception/Assistant/Doctor user)**
- **Lab Admin**
- **Lab Staff**
- **Radiology Admin**
- **Radiology Staff**
- **Patient (interaction side of organization flows)**

Access for organization users depends on assigned role and permissions.

## Shared Organization Flow (All Organizations)

1. Login from dashboard login page
2. Enter organization area (clinic, lab, or radiology)
3. Use sidebar modules based on permissions
4. Manage patients/users/roles and service-related workflows
5. Track notifications and financial data
6. Communicate through organization chat

---

## Clinic Flows

## Clinic Admin Flow

1. **Setup team and access**
   - Create roles and permissions
   - Create users and assign roles
2. **Setup operational structure**
   - Add doctors
   - Configure reservation numbers/slots
   - Add services and drugs catalog
3. **Patient onboarding**
   - Create patient manually or assign by patient code
   - Review patient history
4. **Reservation lifecycle**
   - Create reservation
   - Update status/acceptance/payment
   - Move reservation date if needed
5. **Clinical execution from reservations**
   - Prescription:
     - Add title/notes/images
     - Select drug from clinic drug catalog
     - Override dose/type/frequency/period/notes if needed
   - Rays / Glasses distance / Teeth plan
6. **Follow-up sessions**
   - Create sessions linked to parent reservation
   - Record payment rows and remaining values
7. **Quality and communication**
   - View reviews and announcements
   - Use notifications and chat
8. **Governance**
   - Settings, inventory, modules, financial reporting

## Clinic Staff Flow

1. Open assigned modules (usually reservations/patients)
2. Serve today reservations
3. Update reservation status and acceptance
4. Enter prescription/ray/glasses/teeth data as assigned
5. Capture payments according to permission scope

---

## Lab Flows

## Lab Admin Flow

1. Configure users, roles, permissions
2. Configure service categories and lab services
3. Manage patient assignment and patient history
4. Create or update medical analyses
5. Record analysis report values and payments
6. Monitor today analyses and financial dashboards
7. Use notifications and organization chat
8. Manage **Questionnaires** and review patient answers

## Lab Staff Flow

1. Open medical analyses queue
2. Review patient and reservation context
3. Fill lab service values and notes
4. Complete/report analysis
5. Coordinate with team through chat/notifications

---

## Radiology Flows

## Radiology Admin Flow

1. Configure users, roles, permissions
2. Configure ray categories
3. Manage patient assignment and history
4. Create/update rays and reports
5. Track payments and financial records
6. Monitor notifications and chat
7. Manage **Questionnaires** and review patient answers

## Radiology Staff Flow

1. Open rays list
2. Capture patient/ray details
3. Upload/attach imaging and report
4. Update payment details if permitted
5. Coordinate with team via notifications/chat

---

## Questionnaire Flows

## Organization User Flow (Clinic/Lab/Radiology)

1. Open **Questionnaires** module in dashboard
2. Click **Add Questionnaire**
3. Fill questionnaire metadata:
   - title
   - description
   - active/inactive
4. Add questions:
   - choose type
   - required or optional
   - options for choice questions
5. Save questionnaire
6. Open **Answers** to review submissions grouped by patient

## Patient Flow for Questionnaires

1. Login as patient
2. Open **Questionnaires** in patient dashboard
3. Select:
   - organization type
   - organization
   - questionnaire
4. Fill answers by question type
5. Submit answers
6. Re-open later to review/edit existing answers

### Rules enforced

- Patient must be assigned to the selected organization (`patient_organization.assigned = true`)
- Questionnaire must be active
- Required questions must be answered

---

## Patient-Organization Interaction Flow

1. Patient is assigned to organization (clinic/lab/radiology)
2. Patient books/uses services in that organization
3. Organization staff records medical workflow data
4. Patient can:
   - view own records (prescriptions/analyses/rays/etc.)
   - chat
   - review organization/doctor
   - answer organization questionnaires

---

## Operational Best Practices

- Keep permissions strict by role (least privilege)
- Use active/inactive flags instead of deleting core configuration where possible
- Keep questionnaire titles and questions versioned by creating a new questionnaire when structure changes heavily
- Validate financial/payment data at each step in reservation/analysis/ray workflows
- Review notifications and unanswered questionnaires regularly


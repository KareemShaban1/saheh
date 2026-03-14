import { Toaster } from "@/components/ui/toaster";
import { Toaster as Sonner } from "@/components/ui/sonner";
import { TooltipProvider } from "@/components/ui/tooltip";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom";
import { AuthProvider } from "@/contexts/AuthContext";
import RequireAuth from "@/components/RequireAuth";
import RequireGuest from "@/components/RequireGuest";
import RequireSuperAdmin from "@/components/RequireSuperAdmin";
import RequireOrganizationAccess from "@/components/RequireOrganizationAccess";

// Layouts
import PublicLayout from "@/layouts/PublicLayout";
import PatientLayout from "@/layouts/PatientLayout";
import { ClinicLayout, AdminLayout, LabLayout, RadiologyLayout } from "@/layouts/DashboardLayouts";

// Public pages
import HomePage from "@/pages/HomePage";
import ClinicsPage from "@/pages/public/ClinicsPage";
import ClinicDetailPage from "@/pages/public/ClinicDetailPage";
import DoctorDetailPage from "@/pages/public/DoctorDetailPage";
import LabsPage from "@/pages/public/LabsPage";
import RadiologyCentersPage from "@/pages/public/RadiologyCentersPage";

// Patient pages
import PatientAppointments from "@/pages/patient/PatientAppointments";
import PatientProfile from "@/pages/patient/PatientProfile";
import PatientPrescriptions from "@/pages/patient/PatientPrescriptions";
import PatientAnalyses from "@/pages/patient/PatientAnalyses";
import PatientRays from "@/pages/patient/PatientRays";
import PatientChronicDiseases from "@/pages/patient/PatientChronicDiseases";
import PatientGlasses from "@/pages/patient/PatientGlasses";
import PatientReviews from "@/pages/patient/PatientReviews";
import PatientHome from "@/pages/patient/PatientHome";
import PatientChat from "@/pages/patient/PatientChat";

// Clinic pages
import ClinicDashboardPage from "@/pages/clinic/ClinicDashboardPage";
import ClinicReservations from "@/pages/clinic/ClinicReservations";
import ClinicTodayReservations from "@/pages/clinic/ClinicTodayReservations";
import ClinicReservationDetailsPage from "@/pages/clinic/ClinicReservationDetailsPage";
import ClinicDoctors from "@/pages/clinic/ClinicDoctors";
import ClinicPatients from "@/pages/clinic/ClinicPatients";
import ClinicPatientFormPage from "@/pages/clinic/ClinicPatientFormPage";
import ClinicPatientHistoryPage from "@/pages/clinic/ClinicPatientHistoryPage";
import ClinicReservationNumbers from "@/pages/clinic/ClinicReservationNumbers";
import ClinicReservationSlots from "@/pages/clinic/ClinicReservationSlots";
import ClinicReservationFormPage from "@/pages/clinic/ClinicReservationFormPage";
import ClinicRoles from "@/pages/clinic/ClinicRoles";
import ClinicReviews from "@/pages/clinic/ClinicReviews";
import ClinicAnnouncements from "@/pages/clinic/ClinicAnnouncements";
import ClinicUsers from "@/pages/clinic/ClinicUsers";
import ClinicServices from "@/pages/clinic/ClinicServices";
import ClinicModules from "@/pages/clinic/ClinicModules";
import ClinicInventory from "@/pages/clinic/ClinicInventory";
import ClinicFinancial from "@/pages/clinic/ClinicFinancial";
import ClinicNotifications from "@/pages/clinic/ClinicNotifications";
import ClinicSettings from "@/pages/clinic/ClinicSettings";
import OrganizationChatPage from "@/pages/shared/OrganizationChatPage";

// Admin pages
import AdminDashboard from "@/pages/admin/AdminDashboard";
import AdminClinics from "@/pages/admin/AdminClinics";
import AdminUsers from "@/pages/admin/AdminUsers";
import AdminLabs from "@/pages/admin/AdminLabs";
import AdminRadiology from "@/pages/admin/AdminRadiology";
import AdminSpecialties from "@/pages/admin/AdminSpecialties";
import AdminLocations from "@/pages/admin/AdminLocations";
import AdminReviews from "@/pages/admin/AdminReviews";
import AdminAnnouncements from "@/pages/admin/AdminAnnouncements";
import AdminFinancial from "@/pages/admin/AdminFinancial";

// Lab pages
import LabDashboard from "@/pages/lab/LabDashboard";
import LabReservations from "@/pages/lab/LabReservations";
import LabPatients from "@/pages/lab/LabPatients";
import LabRoles from "@/pages/lab/LabRoles";
import LabUsers from "@/pages/lab/LabUsers";
import LabServiceCategories from "@/pages/lab/LabServiceCategories";
import LabServices from "@/pages/lab/LabServices";
import LabMedicalAnalyses from "@/pages/lab/LabMedicalAnalyses";
import LabFinancial from "@/pages/lab/LabFinancial";
import LabTodayMedicalAnalyses from "@/pages/lab/LabTodayMedicalAnalyses";
import LabNotifications from "@/pages/lab/LabNotifications";
import LabPatientHistoryPage from "@/pages/lab/LabPatientHistoryPage";

// Radiology pages
import RadiologyDashboard from "@/pages/radiology/RadiologyDashboard";
import RadiologyRays from "@/pages/radiology/RadiologyRays";
import RadiologyPatients from "@/pages/radiology/RadiologyPatients";
import RadiologyRoles from "@/pages/radiology/RadiologyRoles";
import RadiologyFinancial from "@/pages/radiology/RadiologyFinancial";
import RadiologyNotifications from "@/pages/radiology/RadiologyNotifications";
import RadiologyUsers from "@/pages/radiology/RadiologyUsers";
import RadiologyPatientHistoryPage from "@/pages/radiology/RadiologyPatientHistoryPage";
import RadiologyRayCategories from "@/pages/radiology/RadiologyRayCategories";

// Auth pages
import LoginPage from "@/pages/auth/LoginPage";
import RegisterPage from "@/pages/auth/RegisterPage";
import DashboardLoginPage from "@/pages/auth/DashboardLoginPage";
import OrganizationLoginPage from "@/pages/auth/OrganizationLoginPage";
import SuperAdminLoginPage from "@/pages/auth/SuperAdminLoginPage";
import ClinicRegisterPage from "@/pages/auth/ClinicRegisterPage";
import LabRegisterPage from "@/pages/auth/LabRegisterPage";
import RadiologyRegisterPage from "@/pages/auth/RadiologyRegisterPage";

// Shared
import PlaceholderPage from "@/components/PlaceholderPage";
import NotFound from "@/pages/NotFound";
import GlobalLoadingIndicator from "@/components/GlobalLoadingIndicator";

import { FileText, FlaskConical, ScanLine, Heart, Glasses, Star, CalendarDays, Users, Shield, Trash2, Hash, Clock, MapPin, Bell } from "lucide-react";
import { QUERY_CACHE_DEFAULTS } from "@/lib/queryCache";

const queryClient = new QueryClient({
  defaultOptions: {
    queries: QUERY_CACHE_DEFAULTS,
  },
});

const App = () => (
  <QueryClientProvider client={queryClient}>
    <TooltipProvider>
      <Toaster />
      <Sonner />
      <BrowserRouter>
        <AuthProvider>
          {/* <GlobalLoadingIndicator /> */}
          <Routes>
            {/* Auth (no layout) */}
            <Route path="/login" element={<RequireGuest><LoginPage /></RequireGuest>} />
            <Route path="/register" element={<RequireGuest><RegisterPage /></RequireGuest>} />
            <Route path="/dashboard-login" element={<DashboardLoginPage />} />
            <Route path="/super-admin/login" element={<SuperAdminLoginPage />} />
            <Route path="/clinic/login" element={<OrganizationLoginPage />} />
            <Route path="/clinic/register" element={<ClinicRegisterPage />} />
            <Route path="/lab/login" element={<OrganizationLoginPage />} />
            <Route path="/lab/register" element={<LabRegisterPage />} />
            <Route path="/radiology/login" element={<OrganizationLoginPage />} />
            <Route path="/radiology/register" element={<RadiologyRegisterPage />} />

            {/* Public */}
            <Route element={<PublicLayout />}>
              <Route path="/" element={<HomePage />} />
              <Route path="/clinics" element={<ClinicsPage />} />
              <Route path="/clinics/:id" element={<ClinicDetailPage />} />
              <Route path="/doctors/:id" element={<DoctorDetailPage />} />
              <Route path="/labs" element={<LabsPage />} />
              <Route path="/labs/:id" element={<PlaceholderPage title="Lab Detail" description="Lab details and services. Connect to GET /medical-laboratory/{id}" icon={FlaskConical} />} />
              <Route path="/radiology-centers" element={<RadiologyCentersPage />} />
              <Route path="/radiology-centers/:id" element={<PlaceholderPage title="Radiology Center Detail" description="Center details. Connect to GET /radiology-center/{id}" icon={ScanLine} />} />
            </Route>

            {/* Patient Dashboard (protected) */}
            <Route element={<RequireAuth><PatientLayout /></RequireAuth>}>
            <Route path="/patient/home" element={<PatientHome />} />
            <Route path="/patient/appointments" element={<PatientAppointments />} />
            <Route path="/patient/profile" element={<PatientProfile />} />
            <Route path="/patient/prescriptions" element={<PatientPrescriptions />} />
            <Route path="/patient/analyses" element={<PatientAnalyses />} />
            <Route path="/patient/rays" element={<PatientRays />} />
            <Route path="/patient/chronic-diseases" element={<PatientChronicDiseases />} />
            <Route path="/patient/glasses" element={<PatientGlasses />} />
            <Route path="/patient/reviews" element={<PatientReviews />} />
            <Route path="/patient/chat" element={<PatientChat />} />
          </Route>

          {/* Clinic Dashboard */}
          <Route element={<RequireOrganizationAccess allowedGuards={["clinic"]}><ClinicLayout /></RequireOrganizationAccess>}>
            <Route path="/clinic-dashboard" element={<ClinicDashboardPage />} />
            <Route path="/clinic-dashboard/reservations" element={<RequireOrganizationAccess allowedGuards={["clinic"]} permissionPrefixes={["reservations"]}><ClinicReservations /></RequireOrganizationAccess>} />
            <Route path="/clinic-dashboard/reservations/:id" element={<RequireOrganizationAccess allowedGuards={["clinic"]} permissionPrefixes={["reservations"]}><ClinicReservationDetailsPage /></RequireOrganizationAccess>} />
            <Route path="/clinic-dashboard/reservations/new" element={<RequireOrganizationAccess allowedGuards={["clinic"]} permissionPrefixes={["reservations"]}><ClinicReservationFormPage /></RequireOrganizationAccess>} />
            <Route path="/clinic-dashboard/reservations/:id/edit" element={<RequireOrganizationAccess allowedGuards={["clinic"]} permissionPrefixes={["reservations"]}><ClinicReservationFormPage /></RequireOrganizationAccess>} />
            <Route path="/clinic-dashboard/today" element={<RequireOrganizationAccess allowedGuards={["clinic"]} permissionPrefixes={["reservations"]}><ClinicTodayReservations /></RequireOrganizationAccess>} />
            <Route path="/clinic-dashboard/numbers" element={<RequireOrganizationAccess allowedGuards={["clinic"]} permissionPrefixes={["reservation-numbers", "numbers"]}><ClinicReservationNumbers /></RequireOrganizationAccess>} />
            <Route path="/clinic-dashboard/slots" element={<RequireOrganizationAccess allowedGuards={["clinic"]} permissionPrefixes={["reservation-slots", "slots"]}><ClinicReservationSlots /></RequireOrganizationAccess>} />
            <Route path="/clinic-dashboard/doctors" element={<RequireOrganizationAccess allowedGuards={["clinic"]} permissionPrefixes={["doctors"]}><ClinicDoctors /></RequireOrganizationAccess>} />
            <Route path="/clinic-dashboard/patients" element={<RequireOrganizationAccess allowedGuards={["clinic"]} permissionPrefixes={["patients"]}><ClinicPatients /></RequireOrganizationAccess>} />
            <Route path="/clinic-dashboard/patients/new" element={<RequireOrganizationAccess allowedGuards={["clinic"]} permissionPrefixes={["patients"]}><ClinicPatientFormPage /></RequireOrganizationAccess>} />
            <Route path="/clinic-dashboard/patients/:id/edit" element={<RequireOrganizationAccess allowedGuards={["clinic"]} permissionPrefixes={["patients"]}><ClinicPatientFormPage /></RequireOrganizationAccess>} />
            <Route path="/clinic-dashboard/patients/:id/history" element={<RequireOrganizationAccess allowedGuards={["clinic"]} permissionPrefixes={["patients"]}><ClinicPatientHistoryPage /></RequireOrganizationAccess>} />
            <Route path="/clinic-dashboard/roles" element={<RequireOrganizationAccess allowedGuards={["clinic"]} permissionPrefixes={["roles", "permissions"]}><ClinicRoles /></RequireOrganizationAccess>} />
            <Route path="/clinic-dashboard/chat" element={<RequireOrganizationAccess allowedGuards={["clinic"]} permissionPrefixes={["chat", "chats"]}><OrganizationChatPage /></RequireOrganizationAccess>} />
            <Route path="/clinic-dashboard/reviews" element={<RequireOrganizationAccess allowedGuards={["clinic"]} permissionPrefixes={["reviews"]}><ClinicReviews /></RequireOrganizationAccess>} />
            <Route path="/clinic-dashboard/announcements" element={<RequireOrganizationAccess allowedGuards={["clinic"]} permissionPrefixes={["announcements"]}><ClinicAnnouncements /></RequireOrganizationAccess>} />
            <Route path="/clinic-dashboard/notifications" element={<RequireOrganizationAccess allowedGuards={["clinic"]} permissionPrefixes={["notifications"]}><ClinicNotifications /></RequireOrganizationAccess>} />
            <Route path="/clinic-dashboard/users" element={<RequireOrganizationAccess allowedGuards={["clinic"]} permissionPrefixes={["users"]}><ClinicUsers /></RequireOrganizationAccess>} />
            <Route path="/clinic-dashboard/services" element={<RequireOrganizationAccess allowedGuards={["clinic"]} permissionPrefixes={["services"]}><ClinicServices /></RequireOrganizationAccess>} />
            <Route path="/clinic-dashboard/inventory" element={<RequireOrganizationAccess allowedGuards={["clinic"]} permissionPrefixes={["inventory"]}><ClinicInventory /></RequireOrganizationAccess>} />
            <Route path="/clinic-dashboard/settings" element={<RequireOrganizationAccess allowedGuards={["clinic"]} permissionPrefixes={["settings"]}><ClinicSettings /></RequireOrganizationAccess>} />
            <Route path="/clinic-dashboard/modules" element={<RequireOrganizationAccess allowedGuards={["clinic"]} permissionPrefixes={["modules"]}><ClinicModules /></RequireOrganizationAccess>} />
            <Route path="/clinic-dashboard/financial" element={<RequireOrganizationAccess allowedGuards={["clinic"]} permissionPrefixes={["financial"]}><ClinicFinancial /></RequireOrganizationAccess>} />
            <Route path="/clinic-dashboard/trash" element={<PlaceholderPage title="Trash" description="Restore or permanently delete reservations" icon={Trash2} />} />
          </Route>

          {/* Admin Dashboard */}
          <Route element={<RequireSuperAdmin><AdminLayout /></RequireSuperAdmin>}>
            <Route path="/super-admin" element={<AdminDashboard />} />
            <Route path="/super-admin/clinics" element={<AdminClinics />} />
            <Route path="/super-admin/labs" element={<AdminLabs />} />
            <Route path="/super-admin/radiology" element={<AdminRadiology />} />
            <Route path="/super-admin/specialties" element={<AdminSpecialties />} />
            <Route path="/super-admin/locations" element={<AdminLocations />} />
            <Route path="/super-admin/users" element={<AdminUsers />} />
            <Route path="/super-admin/reviews" element={<AdminReviews />} />
            <Route path="/super-admin/announcements" element={<AdminAnnouncements />} />
            <Route path="/super-admin/financial" element={<AdminFinancial />} />
          </Route>
          <Route path="/admin" element={<Navigate to="/super-admin" replace />} />
          <Route path="/admin/*" element={<Navigate to="/super-admin" replace />} />

          {/* Lab Dashboard */}
          <Route element={<RequireOrganizationAccess allowedGuards={["medical_laboratory"]}><LabLayout /></RequireOrganizationAccess>}>
            <Route path="/lab-dashboard" element={<LabDashboard />} />
            <Route path="/lab-dashboard/reservations" element={<RequireOrganizationAccess allowedGuards={["medical_laboratory"]} permissionPrefixes={["reservations"]}><LabReservations /></RequireOrganizationAccess>} />
            <Route path="/lab-dashboard/patients" element={<RequireOrganizationAccess allowedGuards={["medical_laboratory"]} permissionPrefixes={["patients"]}><LabPatients /></RequireOrganizationAccess>} />
            <Route path="/lab-dashboard/patients/:id/history" element={<RequireOrganizationAccess allowedGuards={["medical_laboratory"]} permissionPrefixes={["patients"]}><LabPatientHistoryPage /></RequireOrganizationAccess>} />
            <Route path="/lab-dashboard/chat" element={<RequireOrganizationAccess allowedGuards={["medical_laboratory"]} permissionPrefixes={["chat", "chats"]}><OrganizationChatPage /></RequireOrganizationAccess>} />
            <Route path="/lab-dashboard/notifications" element={<RequireOrganizationAccess allowedGuards={["medical_laboratory"]} permissionPrefixes={["notifications"]}><LabNotifications /></RequireOrganizationAccess>} />
            <Route path="/lab-dashboard/roles" element={<RequireOrganizationAccess allowedGuards={["medical_laboratory"]} permissionPrefixes={["roles", "permissions"]}><LabRoles /></RequireOrganizationAccess>} />
            <Route path="/lab-dashboard/users" element={<RequireOrganizationAccess allowedGuards={["medical_laboratory"]} permissionPrefixes={["users"]}><LabUsers /></RequireOrganizationAccess>} />
            <Route path="/lab-dashboard/service-categories" element={<RequireOrganizationAccess allowedGuards={["medical_laboratory"]} permissionPrefixes={["service-categories"]}><LabServiceCategories /></RequireOrganizationAccess>} />
            <Route path="/lab-dashboard/services" element={<RequireOrganizationAccess allowedGuards={["medical_laboratory"]} permissionPrefixes={["services"]}><LabServices /></RequireOrganizationAccess>} />
            <Route path="/lab-dashboard/medical-analyses" element={<RequireOrganizationAccess allowedGuards={["medical_laboratory"]} permissionPrefixes={["medical-analyses"]}><LabMedicalAnalyses /></RequireOrganizationAccess>} />
            <Route path="/lab-dashboard/financial" element={<RequireOrganizationAccess allowedGuards={["medical_laboratory"]} permissionPrefixes={["financial"]}><LabFinancial /></RequireOrganizationAccess>} />
            <Route path="/lab-dashboard/today-medical-analyses" element={<RequireOrganizationAccess allowedGuards={["medical_laboratory"]} permissionPrefixes={["medical-analyses"]}><LabTodayMedicalAnalyses /></RequireOrganizationAccess>} />
          </Route>

          {/* Radiology Dashboard */}
          <Route element={<RequireOrganizationAccess allowedGuards={["radiology_center"]}><RadiologyLayout /></RequireOrganizationAccess>}>
            <Route path="/radiology-dashboard" element={<RadiologyDashboard />} />
            <Route path="/radiology-dashboard/rays" element={<RequireOrganizationAccess allowedGuards={["radiology_center"]} permissionPrefixes={["rays"]}><RadiologyRays /></RequireOrganizationAccess>} />
            <Route path="/radiology-dashboard/ray-categories" element={<RequireOrganizationAccess allowedGuards={["radiology_center"]} permissionPrefixes={["ray-categories"]}><RadiologyRayCategories /></RequireOrganizationAccess>} />
            <Route path="/radiology-dashboard/users" element={<RequireOrganizationAccess allowedGuards={["radiology_center"]} permissionPrefixes={["users"]}><RadiologyUsers /></RequireOrganizationAccess>} />
            <Route path="/radiology-dashboard/patients" element={<RequireOrganizationAccess allowedGuards={["radiology_center"]} permissionPrefixes={["patients"]}><RadiologyPatients /></RequireOrganizationAccess>} />
            <Route path="/radiology-dashboard/patients/:id/history" element={<RequireOrganizationAccess allowedGuards={["radiology_center"]} permissionPrefixes={["patients"]}><RadiologyPatientHistoryPage /></RequireOrganizationAccess>} />
            <Route path="/radiology-dashboard/chat" element={<RequireOrganizationAccess allowedGuards={["radiology_center"]} permissionPrefixes={["chat", "chats"]}><OrganizationChatPage /></RequireOrganizationAccess>} />
            <Route path="/radiology-dashboard/notifications" element={<RequireOrganizationAccess allowedGuards={["radiology_center"]} permissionPrefixes={["notifications"]}><RadiologyNotifications /></RequireOrganizationAccess>} />
            <Route path="/radiology-dashboard/roles" element={<RequireOrganizationAccess allowedGuards={["radiology_center"]} permissionPrefixes={["roles", "permissions"]}><RadiologyRoles /></RequireOrganizationAccess>} />
            <Route path="/radiology-dashboard/financial" element={<RequireOrganizationAccess allowedGuards={["radiology_center"]} permissionPrefixes={["financial"]}><RadiologyFinancial /></RequireOrganizationAccess>} />
          </Route>

            <Route path="*" element={<NotFound />} />
          </Routes>
        </AuthProvider>
      </BrowserRouter>
    </TooltipProvider>
  </QueryClientProvider>
);

export default App;

<template>
    <div class="dashboard-container">
        <!-- Page Header -->
        <div class="page-title mb-4">
            <h4 class="mb-0">
                {{ $t("backend/dashboard_trans.Dashboard") || "Dashboard" }}
            </h4>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-5">
            <LoadingSpinner message="Loading dashboard..." />
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="alert alert-danger">
            <h5>Error Loading Dashboard</h5>
            <p>{{ error }}</p>
            <button @click="fetchData" class="btn btn-primary mt-2">
                Retry
            </button>
        </div>

        <!-- Dashboard Content -->
        <div v-else>
            <!-- Statistics Cards Grid -->
            <div class="row mb-4">
                <StatCard
                    :title="$t('backend/dashboard_trans.doctors') || 'Doctors'"
                    :value="stats.doctorsCount"
                    icon="fa fa-user"
                    :link="'/clinic/users'"
                    format-number
                />
                <StatCard
                    :title="
                        $t('backend/dashboard_trans.Patients') || 'Patients'
                    "
                    :value="stats.patientsCount"
                    icon="fa-solid fa-hospital-user"
                    :link="'/clinic/patients'"
                    format-number
                />
                <StatCard
                    :title="
                        $t('backend/dashboard_trans.Medicines_Number') ||
                        'Medicines'
                    "
                    :value="stats.medicinesCount"
                    icon="fa-solid fa-pills"
                    :link="'/clinic/medicines'"
                    format-number
                />
                <StatCard
                    :title="
                        $t('backend/dashboard_trans.Today_Reservations') ||
                        'Today Reservations'
                    "
                    :value="stats.todayReservationsCount"
                    icon="fa fa-stethoscope"
                    :link="'/clinic/reservations/today'"
                    format-number
                />
            </div>

            <div class="row mb-4">
                <StatCard
                    :title="
                        $t('backend/dashboard_trans.Online_Reservations') ||
                        'Online Reservations'
                    "
                    :value="stats.onlineReservationsCount"
                    icon="fa fa-stethoscope"
                    :link="'/clinic/online_reservations'"
                    format-number
                />
                <StatCard
                    :title="
                        $t('backend/dashboard_trans.All_Reservations') ||
                        'All Reservations'
                    "
                    :value="stats.allReservationsCount"
                    icon="fa fa-stethoscope"
                    :link="'/clinic/reservations'"
                    format-number
                />
            </div>

            <!-- Last Processes Section -->
            <div class="row">
                <div class="col-12 col-xl-9 mb-4">
                    <div class="card card-statistics">
                        <div class="card-body">
                            <h5 class="card-title mb-4">
                                {{
                                    $t(
                                        "backend/dashboard_trans.Last_Processes"
                                    ) || "Last Processes"
                                }}
                            </h5>

                            <!-- Tabs -->
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item">
                                    <a
                                        class="nav-link"
                                        :class="{
                                            active: activeTab === 'patients',
                                        }"
                                        @click.prevent="activeTab = 'patients'"
                                        href="#"
                                    >
                                        {{
                                            $t(
                                                "backend/dashboard_trans.Last_Patients"
                                            ) || "Last Patients"
                                        }}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        class="nav-link"
                                        :class="{
                                            active:
                                                activeTab === 'reservations',
                                        }"
                                        @click.prevent="
                                            activeTab = 'reservations'
                                        "
                                        href="#"
                                    >
                                        {{
                                            $t(
                                                "backend/dashboard_trans.Last_Reservations"
                                            ) || "Last Reservations"
                                        }}
                                    </a>
                                </li>
                            </ul>

                            <!-- Tab Content -->
                            <div class="tab-content mt-3">
                                <!-- Patients Tab -->
                                <div
                                    v-show="activeTab === 'patients'"
                                    class="tab-pane"
                                >
                                    <RecentPatientsTable
                                        :patients="lastPatients"
                                    />
                                </div>

                                <!-- Reservations Tab -->
                                <div
                                    v-show="activeTab === 'reservations'"
                                    class="tab-pane"
                                >
                                    <RecentReservationsTable
                                        :reservations="reservations"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fast Actions Panel -->
                <div class="col-12 col-xl-3">
                    <FastActionsPanel />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useClinicDashboardStore } from "../../stores/modules/clinic/dashboard";
import StatCard from "../../components/dashboard/StatCard.vue";
import LoadingSpinner from "../../components/common/LoadingSpinner.vue";
import RecentPatientsTable from "../../components/dashboard/RecentPatientsTable.vue";
import RecentReservationsTable from "../../components/dashboard/RecentReservationsTable.vue";
import FastActionsPanel from "../../components/dashboard/FastActionsPanel.vue";

const dashboardStore = useClinicDashboardStore();

const activeTab = ref("patients");

const loading = computed(() => dashboardStore.loading);
const error = computed(() => dashboardStore.error);
const stats = computed(() => dashboardStore.stats);
const lastPatients = computed(() => dashboardStore.lastPatients);
const reservations = computed(() => dashboardStore.reservations);

const fetchData = async () => {
    try {
        await dashboardStore.fetchDashboardData();
    } catch (err) {
        console.error("Failed to fetch dashboard data:", err);
    }
};

onMounted(() => {
    fetchData();
});
</script>

<style scoped>
.dashboard-container {
    padding: 1rem;
}

.nav-link {
    cursor: pointer;
}

.nav-link.active {
    color: #007bff;
    border-bottom: 2px solid #007bff;
}
</style>

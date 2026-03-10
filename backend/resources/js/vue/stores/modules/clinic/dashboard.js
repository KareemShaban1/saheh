import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../../../utils/api'

/**
 * Clinic Dashboard Store
 * Manages all dashboard-related state and actions
 */
export const useClinicDashboardStore = defineStore('clinicDashboard', () => {
  // ========== State ==========
  const stats = ref({
    doctorsCount: 0,
    patientsCount: 0,
    medicinesCount: 0,
    todayReservationsCount: 0,
    onlineReservationsCount: 0,
    allReservationsCount: 0,
    todayPayment: 0,
    monthPayment: 0,
  })

  const lastPatients = ref([])
  const reservations = ref([])
  const onlineReservations = ref([])
  const doctors = ref([])
  const doctorWeeklySlots = ref({})
  const charts = ref({
    patientsChart: null,
    reservationsChart: null,
  })

  const loading = ref(false)
  const error = ref(null)

  // ========== Getters (Computed) ==========
  const hasData = computed(() => {
    return stats.value.patientsCount > 0 || lastPatients.value.length > 0
  })

  const totalReservations = computed(() => {
    return stats.value.todayReservationsCount + stats.value.onlineReservationsCount
  })

  const isLoading = computed(() => loading.value)

  const hasError = computed(() => error.value !== null)

  // ========== Actions ==========
  
  /**
   * Fetch all dashboard data
   */
  const fetchDashboardData = async () => {
    loading.value = true
    error.value = null

    try {
      const response = await api.get('/clinic/dashboard')
      
      if (response.data.success && response.data.data) {
        const data = response.data.data
        
        // Update stats
        if (data.stats) {
          stats.value = {
            doctorsCount: data.stats.doctors_count || 0,
            patientsCount: data.stats.patients_count || 0,
            medicinesCount: data.stats.medicines_count || 0,
            todayReservationsCount: data.stats.today_res_count || 0,
            onlineReservationsCount: data.stats.online_reservations_count || 0,
            allReservationsCount: data.stats.all_reservations_count || 0,
            todayPayment: data.stats.today_payment || 0,
            monthPayment: data.stats.month_payment || 0,
          }
        }

        // Update lists
        lastPatients.value = data.last_patients || []
        reservations.value = data.reservations || []
        onlineReservations.value = data.online_reservations || []
        doctors.value = data.doctors || []
        doctorWeeklySlots.value = data.doctor_weekly_slots || {}

        // Update charts if provided
        if (data.charts) {
          charts.value = data.charts
        }
      }
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Failed to fetch dashboard data'
      console.error('Dashboard fetch error:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Update a specific stat
   * @param {string} key - Stat key to update
   * @param {number} value - New value
   */
  const updateStat = (key, value) => {
    if (key in stats.value) {
      stats.value[key] = value
    }
  }

  /**
   * Refresh dashboard data
   */
  const refresh = async () => {
    await fetchDashboardData()
  }

  /**
   * Reset store to initial state
   */
  const reset = () => {
    stats.value = {
      doctorsCount: 0,
      patientsCount: 0,
      medicinesCount: 0,
      todayReservationsCount: 0,
      onlineReservationsCount: 0,
      allReservationsCount: 0,
      todayPayment: 0,
      monthPayment: 0,
    }
    lastPatients.value = []
    reservations.value = []
    onlineReservations.value = []
    doctors.value = []
    doctorWeeklySlots.value = {}
    charts.value = {
      patientsChart: null,
      reservationsChart: null,
    }
    error.value = null
  }

  return {
    // State
    stats,
    lastPatients,
    reservations,
    onlineReservations,
    doctors,
    doctorWeeklySlots,
    charts,
    loading,
    error,
    // Getters
    hasData,
    totalReservations,
    isLoading,
    hasError,
    // Actions
    fetchDashboardData,
    updateStat,
    refresh,
    reset,
  }
})


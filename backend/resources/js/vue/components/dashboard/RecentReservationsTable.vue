<template>
  <div class="table-responsive">
    <table class="table table-hover mb-0" style="text-align: center">
      <thead>
        <tr>
          <th>{{ $t('backend/reservations_trans.Patient_Name') || 'Patient Name' }}</th>
          <th>{{ $t('backend/reservations_trans.Payment') || 'Payment' }}</th>
          <th>{{ $t('backend/reservations_trans.Reservation_Status') || 'Status' }}</th>
          <th>{{ $t('backend/reservations_trans.Control') || 'Actions' }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="reservations.length === 0">
          <td colspan="4" class="text-center text-muted py-4">
            No reservations found
          </td>
        </tr>
        <tr v-for="reservation in reservations" :key="reservation.id">
          <td>{{ reservation.patient?.name || 'N/A' }}</td>
          <td>
            <span
              :class="getPaymentBadgeClass(reservation.payment)"
              class="badge badge-rounded p-2 mb-2"
            >
              {{ getPaymentText(reservation.payment) }}
            </span>
          </td>
          <td>
            <span
              :class="getStatusBadgeClass(reservation.res_status)"
              class="badge badge-rounded p-2 mb-2"
            >
              {{ getStatusText(reservation.res_status) }}
            </span>
          </td>
          <td>
            <a
              :href="`/clinic/reservations/${reservation.id}`"
              class="btn btn-primary btn-sm"
              title="View"
            >
              <i class="fa fa-eye"></i>
            </a>
            <a
              :href="`/clinic/reservations/${reservation.id}/edit`"
              class="btn btn-warning btn-sm"
              title="Edit"
            >
              <i class="fa fa-edit"></i>
            </a>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
const props = defineProps({
  reservations: {
    type: Array,
    default: () => [],
  },
})

const getPaymentBadgeClass = (payment) => {
  return payment === 'paid' ? 'badge-success' : 'badge-danger'
}

const getPaymentText = (payment) => {
  const translations = {
    paid: 'Paid',
    not_paid: 'Not Paid',
  }
  return translations[payment] || payment
}

const getStatusBadgeClass = (status) => {
  const classes = {
    waiting: 'badge-warning text-white',
    entered: 'badge-success',
    finished: 'badge-danger',
  }
  return classes[status] || 'badge-secondary'
}

const getStatusText = (status) => {
  const translations = {
    waiting: 'Waiting',
    entered: 'Entered',
    finished: 'Finished',
  }
  return translations[status] || status
}
</script>








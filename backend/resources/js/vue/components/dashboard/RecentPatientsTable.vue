<template>
  <div class="table-responsive">
    <table class="table table-hover mb-0" style="text-align: center">
      <thead>
        <tr class="table-info text-danger">
          <th>{{ $t('backend/patients_trans.Id') || 'ID' }}</th>
          <th>{{ $t('backend/patients_trans.Patient_Name') || 'Patient Name' }}</th>
          <th>{{ $t('backend/patients_trans.Number_of_Reservations') || 'Reservations' }}</th>
          <th>{{ $t('backend/patients_trans.Phone') || 'Phone' }}</th>
          <th>{{ $t('backend/patients_trans.Control') || 'Actions' }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="patients.length === 0">
          <td colspan="5" class="text-center text-muted py-4">
            No patients found
          </td>
        </tr>
        <tr v-for="patient in patients" :key="patient.id">
          <td>{{ patient.id }}</td>
          <td>{{ patient.name }}</td>
          <td>{{ patient.reservations_count || 0 }}</td>
          <td>{{ patient.phone }}</td>
          <td>
            <a
              :href="`/clinic/patients/${patient.id}`"
              class="btn btn-primary btn-sm"
              title="View"
            >
              <i class="fa fa-eye"></i>
            </a>
            <a
              :href="`/clinic/patients/${patient.id}/edit`"
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
defineProps({
  patients: {
    type: Array,
    default: () => [],
  },
})
</script>








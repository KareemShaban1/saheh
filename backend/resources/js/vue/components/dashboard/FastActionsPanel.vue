<template>
  <div class="card card-statistics h-100">
    <div class="card-body">
      <div class="col-12 px-0">
        <div class="col-12 px-3 py-3">
          <h6>{{ $t('Backend/dashboard_trans.Fast_Processes') || 'Fast Processes' }}</h6>
        </div>
        <div class="col-12" style="min-height: 1px; background: #f1f1f1;"></div>
      </div>
      <div class="col-12 p-3 row d-flex m-0">
        <FastActionItem
          icon="/images/icons/patient.png"
          :label="$t('backend/dashboard_trans.Patients') || 'Patients'"
          link="/clinic/patients"
        />
        <FastActionItem
          icon="/images/icons/reservations.png"
          :label="$t('backend/dashboard_trans.Reservations') || 'Reservations'"
          link="/clinic/reservations"
        />
        <FastActionItem
          icon="/images/icons/fees.png"
          :label="$t('backend/dashboard_trans.Fees') || 'Fees'"
          link="/clinic/fees"
        />
        <FastActionItem
          icon="/images/icons/settings.png"
          :label="$t('backend/dashboard_trans.Settings') || 'Settings'"
          link="/clinic/settings"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import FastActionItem from './FastActionItem.vue'
</script>








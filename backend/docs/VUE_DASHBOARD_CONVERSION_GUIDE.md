# Vue Dashboard Conversion Guide

## Table of Contents
1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Installation & Setup](#installation--setup)
4. [Project Structure](#project-structure)
5. [Conversion Strategy](#conversion-strategy)
6. [Clean Code Principles](#clean-code-principles)
7. [State Management with Pinia](#state-management-with-pinia)
8. [Step-by-Step Conversion Process](#step-by-step-conversion-process)
9. [Best Practices](#best-practices)
10. [Example Implementation](#example-implementation)

---

## Overview

This guide will help you convert your Blade-based dashboard to Vue.js while keeping the existing Blade implementation intact. This allows for a gradual migration and ensures the system remains functional during the transition.

### Key Benefits
- **Gradual Migration**: Convert one dashboard at a time
- **Zero Downtime**: Old Blade dashboards continue working
- **Modern Stack**: Vue 3 + Pinia + TypeScript support
- **Better UX**: Reactive, component-based architecture
- **Maintainability**: Clean code principles and organized structure

---

## Prerequisites

- Node.js 18+ and npm/yarn
- Laravel 10+
- Basic knowledge of Vue.js 3
- Understanding of Pinia (Vue's state management)

---

## Installation & Setup

### 1. Install Vue 3 and Required Dependencies

**Important:** Choose the correct `@vitejs/plugin-vue` version based on your Vite version:
- **Vite 3.x**: `@vitejs/plugin-vue@^3.2.0`
- **Vite 4.x**: `@vitejs/plugin-vue@^4.0.0`
- **Vite 5.x+**: `@vitejs/plugin-vue@^5.0.0` or latest

```bash
# For Vite 3 (most Laravel 10 projects)
npm install vue@^3.3.0 vue-router@^4.2.0 pinia@^2.1.0 @vitejs/plugin-vue@^3.2.0
npm install -D @vue/compiler-sfc@^3.3.0
```

### 2. Update `vite.config.js`

```javascript
import { defineConfig } from 'vite';
import laravel, { refreshPaths } from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/vue-app.js', // New Vue entry point
            ],
            refresh: [
                ...refreshPaths,
                'app/Http/Livewire/**',
                'resources/js/vue/**', // Watch Vue files
            ],
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
            '@vue': '/resources/js/vue',
        },
    },
});
```

### 3. Update `package.json`

```json
{
    "scripts": {
        "dev": "vite",
        "build": "vite build",
        "vue:dev": "vite --mode development",
        "vue:build": "vite build --mode production"
    }
}
```

---

## Project Structure

Create the following directory structure:

```
resources/
├── js/
│   ├── app.js                    # Existing Alpine.js entry
│   ├── vue-app.js                # New Vue entry point
│   ├── bootstrap.js              # Existing bootstrap
│   └── vue/                      # Vue application
│       ├── app.js                # Vue app initialization
│       ├── router/
│       │   ├── index.js          # Router configuration
│       │   └── routes.js         # Route definitions
│       ├── stores/               # Pinia stores
│       │   ├── index.js          # Store setup
│       │   ├── auth.js           # Authentication store
│       │   ├── dashboard.js      # Dashboard store
│       │   └── modules/          # Feature-specific stores
│       │       ├── clinic/
│       │       │   ├── dashboard.js
│       │       │   ├── patients.js
│       │       │   └── reservations.js
│       │       ├── admin/
│       │       └── patient/
│       ├── components/           # Reusable components
│       │   ├── common/
│       │   │   ├── Card.vue
│       │   │   ├── Table.vue
│       │   │   ├── Modal.vue
│       │   │   └── LoadingSpinner.vue
│       │   ├── layout/
│       │   │   ├── DashboardLayout.vue
│       │   │   ├── Sidebar.vue
│       │   │   └── Header.vue
│       │   └── dashboard/
│       │       ├── StatCard.vue
│       │       ├── StatCardsGrid.vue
│       │       ├── RecentPatientsTable.vue
│       │       ├── RecentReservationsTable.vue
│       │       └── ChartsSection.vue
│       ├── views/                # Page components
│       │   ├── clinic/
│       │   │   └── Dashboard.vue
│       │   ├── admin/
│       │   │   └── Dashboard.vue
│       │   └── patient/
│       │       └── Dashboard.vue
│       ├── composables/          # Vue composables
│       │   ├── useApi.js
│       │   ├── useAuth.js
│       │   └── useNotifications.js
│       ├── utils/                # Utility functions
│       │   ├── api.js
│       │   ├── formatters.js
│       │   └── validators.js
│       └── plugins/              # Vue plugins
│           ├── axios.js
│           └── echo.js
```

---

## Conversion Strategy

### Phase 1: Parallel Implementation
- Keep Blade routes active
- Create new Vue routes with different paths (e.g., `/vue/clinic/dashboard`)
- Test Vue implementation alongside Blade

### Phase 2: Feature Parity
- Ensure all Blade features work in Vue
- Match UI/UX exactly
- Test thoroughly

### Phase 3: Gradual Migration
- Add feature flag to switch between Blade/Vue
- Migrate users gradually
- Monitor for issues

### Phase 4: Complete Migration
- Make Vue the default
- Keep Blade as fallback
- Eventually remove Blade (optional)

---

## Clean Code Principles

### 1. Single Responsibility Principle (SRP)
Each component/function should do one thing well.

**Bad:**
```vue
<script setup>
// Component doing too much
const fetchData = async () => {
  // Fetch patients
  // Fetch reservations
  // Process data
  // Update UI
  // Handle errors
  // Log analytics
}
</script>
```

**Good:**
```vue
<script setup>
import { usePatientsStore } from '@/vue/stores/modules/clinic/patients'
import { useReservationsStore } from '@/vue/stores/modules/clinic/reservations'

const patientsStore = usePatientsStore()
const reservationsStore = useReservationsStore()

// Each store handles its own responsibility
onMounted(async () => {
  await Promise.all([
    patientsStore.fetchPatients(),
    reservationsStore.fetchReservations()
  ])
})
</script>
```

### 2. DRY (Don't Repeat Yourself)
Extract reusable logic into composables or utilities.

**Bad:**
```vue
<!-- Multiple components with same logic -->
<script setup>
const formatDate = (date) => {
  return new Date(date).toLocaleDateString('ar-EG')
}
</script>
```

**Good:**
```javascript
// utils/formatters.js
export const formatDate = (date, locale = 'ar-EG') => {
  return new Date(date).toLocaleDateString(locale)
}

// Use in components
import { formatDate } from '@/vue/utils/formatters'
```

### 3. Component Composition
Break down complex components into smaller, reusable ones.

**Bad:**
```vue
<!-- One massive component -->
<template>
  <!-- 500 lines of template -->
</template>
```

**Good:**
```vue
<template>
  <DashboardLayout>
    <StatCardsGrid :stats="stats" />
    <div class="row">
      <RecentPatientsTable :patients="patients" />
      <FastActionsPanel />
    </div>
    <ChartsSection :charts="charts" />
  </DashboardLayout>
</template>
```

### 4. Naming Conventions
- **Components**: PascalCase (e.g., `StatCard.vue`)
- **Composables**: camelCase starting with `use` (e.g., `useApi.js`)
- **Stores**: camelCase (e.g., `dashboard.js`)
- **Utils**: camelCase (e.g., `formatters.js`)

### 5. Type Safety (Optional but Recommended)
Use JSDoc or TypeScript for better IDE support.

```javascript
/**
 * @param {string} date - ISO date string
 * @param {string} locale - Locale code
 * @returns {string} Formatted date
 */
export const formatDate = (date, locale = 'ar-EG') => {
  return new Date(date).toLocaleDateString(locale)
}
```

---

## State Management with Pinia

### Store Structure

#### 1. Main Store Setup (`stores/index.js`)

```javascript
import { createPinia } from 'pinia'

export const pinia = createPinia()

// Export for use in app.js
export default pinia
```

#### 2. Dashboard Store Example (`stores/modules/clinic/dashboard.js`)

```javascript
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/vue/utils/api'

export const useClinicDashboardStore = defineStore('clinicDashboard', () => {
  // State
  const stats = ref({
    doctorsCount: 0,
    patientsCount: 0,
    medicinesCount: 0,
    todayReservationsCount: 0,
    onlineReservationsCount: 0,
    allReservationsCount: 0,
  })

  const lastPatients = ref([])
  const reservations = ref([])
  const onlineReservations = ref([])
  const loading = ref(false)
  const error = ref(null)

  // Getters (computed)
  const hasData = computed(() => {
    return stats.value.patientsCount > 0 || lastPatients.value.length > 0
  })

  const totalReservations = computed(() => {
    return stats.value.todayReservationsCount + stats.value.onlineReservationsCount
  })

  // Actions
  const fetchDashboardData = async () => {
    loading.value = true
    error.value = null

    try {
      const response = await api.get('/api/clinic/dashboard')
      
      stats.value = response.data.stats
      lastPatients.value = response.data.lastPatients
      reservations.value = response.data.reservations
      onlineReservations.value = response.data.onlineReservations
    } catch (err) {
      error.value = err.message
      console.error('Failed to fetch dashboard data:', err)
    } finally {
      loading.value = false
    }
  }

  const updateStat = (key, value) => {
    if (key in stats.value) {
      stats.value[key] = value
    }
  }

  const reset = () => {
    stats.value = {
      doctorsCount: 0,
      patientsCount: 0,
      medicinesCount: 0,
      todayReservationsCount: 0,
      onlineReservationsCount: 0,
      allReservationsCount: 0,
    }
    lastPatients.value = []
    reservations.value = []
    onlineReservations.value = []
    error.value = null
  }

  return {
    // State
    stats,
    lastPatients,
    reservations,
    onlineReservations,
    loading,
    error,
    // Getters
    hasData,
    totalReservations,
    // Actions
    fetchDashboardData,
    updateStat,
    reset,
  }
})
```

#### 3. Patients Store (`stores/modules/clinic/patients.js`)

```javascript
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/vue/utils/api'

export const usePatientsStore = defineStore('patients', () => {
  const patients = ref([])
  const currentPatient = ref(null)
  const loading = ref(false)
  const error = ref(null)
  const pagination = ref({
    currentPage: 1,
    perPage: 15,
    total: 0,
  })

  const patientsCount = computed(() => patients.value.length)

  const fetchPatients = async (params = {}) => {
    loading.value = true
    error.value = null

    try {
      const response = await api.get('/api/clinic/patients', { params })
      patients.value = response.data.data
      pagination.value = {
        currentPage: response.data.current_page,
        perPage: response.data.per_page,
        total: response.data.total,
      }
    } catch (err) {
      error.value = err.message
    } finally {
      loading.value = false
    }
  }

  const fetchPatient = async (id) => {
    loading.value = true
    try {
      const response = await api.get(`/api/clinic/patients/${id}`)
      currentPatient.value = response.data
    } catch (err) {
      error.value = err.message
    } finally {
      loading.value = false
    }
  }

  return {
    patients,
    currentPatient,
    loading,
    error,
    pagination,
    patientsCount,
    fetchPatients,
    fetchPatient,
  }
})
```

### Store Best Practices

1. **Keep stores focused**: One store per feature/domain
2. **Use composables for shared logic**: Don't duplicate code across stores
3. **Handle errors consistently**: Always set error state
4. **Loading states**: Always track loading state
5. **Reset on unmount**: Clean up when component unmounts (if needed)

---

## Step-by-Step Conversion Process

### Step 1: Create API Endpoints

First, create API endpoints that return JSON instead of views.

**Create:** `app/Http/Controllers/Api/Clinic/DashboardController.php`

```php
<?php

namespace App\Http\Controllers\Api\Clinic;

use App\Http\Controllers\Controller;
use Modules\Clinic\Dashboard\Http\Controllers\Backend\DashboardController as BackendDashboardController;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        // Reuse existing logic
        $backendController = new BackendDashboardController();
        
        // Get data (you'll need to refactor to return data instead of view)
        $data = $this->getDashboardData();
        
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    private function getDashboardData(): array
    {
        // Extract data fetching logic from BackendDashboardController
        // Return as array instead of passing to view
        return [
            'stats' => [
                'doctors_count' => 10,
                'patients_count' => 100,
                // ... etc
            ],
            'last_patients' => [],
            'reservations' => [],
            // ... etc
        ];
    }
}
```

**Add route:** `routes/api.php`

```php
Route::prefix('clinic')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Api\Clinic\DashboardController::class, 'index']);
});
```

### Step 2: Create Vue Entry Point

**Create:** `resources/js/vue-app.js`

```javascript
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './vue/router'
import App from './vue/app.vue'
import './bootstrap'

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#vue-app')
```

**Create:** `resources/js/vue/app.vue`

```vue
<template>
  <router-view />
</template>

<script setup>
// Root component
</script>
```

### Step 3: Create Router

**Create:** `resources/js/vue/router/index.js`

```javascript
import { createRouter, createWebHistory } from 'vue-router'
import routes from './routes'

const router = createRouter({
  history: createWebHistory('/vue'),
  routes,
})

export default router
```

**Create:** `resources/js/vue/router/routes.js`

```javascript
export default [
  {
    path: '/clinic/dashboard',
    name: 'clinic.dashboard',
    component: () => import('@vue/views/clinic/Dashboard.vue'),
    meta: {
      requiresAuth: true,
      layout: 'dashboard',
    },
  },
  {
    path: '/admin/dashboard',
    name: 'admin.dashboard',
    component: () => import('@vue/views/admin/Dashboard.vue'),
    meta: {
      requiresAuth: true,
      layout: 'dashboard',
    },
  },
  // Add more routes...
]
```

### Step 4: Create Blade Wrapper

**Create:** `resources/views/backend/dashboards/clinic/vue/dashboard.blade.php`

```blade
@extends('backend.dashboards.clinic.layouts.master')

@section('title')
{{ trans('backend/dashboard_trans.Dashboard') }} - Vue
@endsection

@section('content')
<div id="vue-app"></div>
@endsection

@push('scripts')
@vite(['resources/js/vue-app.js'])
@endpush
```

**Add route:** `routes/clinic.php`

```php
// Vue Dashboard (parallel to Blade)
Route::get('/vue/dashboard', function () {
    return view('backend.dashboards.clinic.vue.dashboard');
})->name('vue.dashboard');
```

### Step 5: Create Vue Components

Start with the main dashboard component and break it down:

1. **Dashboard.vue** - Main container
2. **StatCard.vue** - Reusable stat card
3. **StatCardsGrid.vue** - Grid of stat cards
4. **RecentPatientsTable.vue** - Patients table
5. **RecentReservationsTable.vue** - Reservations table

---

## Best Practices

### 1. API Communication

**Create:** `resources/js/vue/utils/api.js`

```javascript
import axios from 'axios'

const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// Request interceptor
api.interceptors.request.use(
  (config) => {
    // Add CSRF token
    const token = document.querySelector('meta[name="csrf-token"]')?.content
    if (token) {
      config.headers['X-CSRF-TOKEN'] = token
    }
    
    // Add auth token if available
    const authToken = localStorage.getItem('auth_token')
    if (authToken) {
      config.headers['Authorization'] = `Bearer ${authToken}`
    }
    
    return config
  },
  (error) => Promise.reject(error)
)

// Response interceptor
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Handle unauthorized
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default api
```

### 2. Composables for Reusable Logic

**Create:** `resources/js/vue/composables/useApi.js`

```javascript
import { ref } from 'vue'
import api from '@/vue/utils/api'

export function useApi(endpoint) {
  const data = ref(null)
  const loading = ref(false)
  const error = ref(null)

  const fetch = async (params = {}) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.get(endpoint, { params })
      data.value = response.data
      return response.data
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const post = async (payload) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.post(endpoint, payload)
      data.value = response.data
      return response.data
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    data,
    loading,
    error,
    fetch,
    post,
  }
}
```

### 3. Error Handling

Always handle errors gracefully:

```vue
<script setup>
import { onMounted } from 'vue'
import { useClinicDashboardStore } from '@/vue/stores/modules/clinic/dashboard'
import { useNotifications } from '@/vue/composables/useNotifications'

const dashboardStore = useClinicDashboardStore()
const { showError } = useNotifications()

onMounted(async () => {
  try {
    await dashboardStore.fetchDashboardData()
  } catch (error) {
    showError('Failed to load dashboard data. Please try again.')
  }
})
</script>
```

### 4. Loading States

Always show loading indicators:

```vue
<template>
  <div v-if="loading" class="loading-spinner">
    <LoadingSpinner />
  </div>
  <div v-else-if="error" class="error-message">
    {{ error }}
  </div>
  <div v-else>
    <!-- Content -->
  </div>
</template>
```

### 5. Form Validation

Use composables for form handling:

```javascript
// composables/useForm.js
import { ref, reactive } from 'vue'

export function useForm(initialValues = {}) {
  const form = reactive({ ...initialValues })
  const errors = reactive({})
  const submitting = ref(false)

  const setError = (field, message) => {
    errors[field] = message
  }

  const clearErrors = () => {
    Object.keys(errors).forEach(key => delete errors[key])
  }

  const reset = () => {
    Object.assign(form, initialValues)
    clearErrors()
  }

  return {
    form,
    errors,
    submitting,
    setError,
    clearErrors,
    reset,
  }
}
```

---

## Example Implementation

### Complete Dashboard Component

**File:** `resources/js/vue/views/clinic/Dashboard.vue`

```vue
<template>
  <DashboardLayout>
    <template #header>
      <h4 class="page-title">{{ $t('backend/dashboard_trans.Dashboard') }}</h4>
    </template>

    <div v-if="loading" class="text-center py-5">
      <LoadingSpinner />
    </div>

    <div v-else-if="error" class="alert alert-danger">
      {{ error }}
      <button @click="fetchData" class="btn btn-sm btn-primary mt-2">
        Retry
      </button>
    </div>

    <div v-else>
      <!-- Reservation Add Section -->
      <ReservationAddSection
        :doctors="doctors"
        :doctor-weekly-slots="doctorWeeklySlots"
      />

      <!-- Statistics Cards -->
      <StatCardsGrid :stats="stats" />

      <!-- Last Processes Section -->
      <div class="row">
        <div class="col-12 col-xl-9">
          <LastProcessesSection
            :last-patients="lastPatients"
            :reservations="reservations"
            :online-reservations="onlineReservations"
          />
        </div>
        <div class="col-12 col-xl-3">
          <FastActionsPanel />
        </div>
      </div>

      <!-- Charts Section -->
      <ChartsSection :charts="charts" />
    </div>
  </DashboardLayout>
</template>

<script setup>
import { onMounted, computed } from 'vue'
import { useClinicDashboardStore } from '@/vue/stores/modules/clinic/dashboard'
import DashboardLayout from '@/vue/components/layout/DashboardLayout.vue'
import LoadingSpinner from '@/vue/components/common/LoadingSpinner.vue'
import StatCardsGrid from '@/vue/components/dashboard/StatCardsGrid.vue'
import LastProcessesSection from '@/vue/components/dashboard/LastProcessesSection.vue'
import FastActionsPanel from '@/vue/components/dashboard/FastActionsPanel.vue'
import ChartsSection from '@/vue/components/dashboard/ChartsSection.vue'
import ReservationAddSection from '@/vue/components/dashboard/ReservationAddSection.vue'

const dashboardStore = useClinicDashboardStore()

const loading = computed(() => dashboardStore.loading)
const error = computed(() => dashboardStore.error)
const stats = computed(() => dashboardStore.stats)
const lastPatients = computed(() => dashboardStore.lastPatients)
const reservations = computed(() => dashboardStore.reservations)
const onlineReservations = computed(() => dashboardStore.onlineReservations)
const doctors = computed(() => dashboardStore.doctors)
const doctorWeeklySlots = computed(() => dashboardStore.doctorWeeklySlots)
const charts = computed(() => dashboardStore.charts)

const fetchData = () => {
  dashboardStore.fetchDashboardData()
}

onMounted(() => {
  fetchData()
})
</script>
```

### StatCard Component

**File:** `resources/js/vue/components/dashboard/StatCard.vue`

```vue
<template>
  <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-20">
    <div class="card card-statistics h-100">
      <div class="card-body">
        <div class="clearfix">
          <div class="float-right">
            <span :class="iconClass">
              <i :class="icon" class="highlight-icon" aria-hidden="true"></i>
            </span>
          </div>
          <div class="float-left text-left">
            <p class="card-text text-dark">
              {{ title }}
            </p>
            <h4>{{ value }}</h4>
          </div>
        </div>
        <p v-if="link" class="text-muted pt-3 mb-0 mt-2 border-top">
          <i class="fas fa-binoculars mr-1" aria-hidden="true"></i>
          <a :href="link" target="_blank">
            <span class="text-danger">{{ linkText }}</span>
          </a>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
  value: {
    type: [String, Number],
    required: true,
  },
  icon: {
    type: String,
    required: true,
  },
  iconClass: {
    type: String,
    default: 'text-success',
  },
  link: {
    type: String,
    default: null,
  },
  linkText: {
    type: String,
    default: 'عرض البيانات',
  },
})
</script>
```

---

## Migration Checklist

- [ ] Install Vue 3 and dependencies
- [ ] Configure Vite for Vue
- [ ] Create project structure
- [ ] Set up Pinia stores
- [ ] Create API endpoints
- [ ] Create router configuration
- [ ] Build reusable components
- [ ] Convert dashboard page
- [ ] Test all functionality
- [ ] Add error handling
- [ ] Add loading states
- [ ] Test on different browsers
- [ ] Performance optimization
- [ ] Documentation

---

## Additional Resources

- [Vue 3 Documentation](https://vuejs.org/)
- [Pinia Documentation](https://pinia.vuejs.org/)
- [Vue Router Documentation](https://router.vuejs.org/)
- [Vite Documentation](https://vitejs.dev/)

---

## Support

For questions or issues during conversion, refer to:
- Vue.js Discord
- Laravel Community
- Project documentation

---

**Last Updated:** 2024
**Version:** 1.0.0


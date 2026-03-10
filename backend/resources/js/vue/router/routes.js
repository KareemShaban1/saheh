export default [
  {
    path: '/clinic/dashboard',
    name: 'clinic.dashboard',
    component: () => import('../views/clinic/Dashboard.vue'),
    meta: {
      requiresAuth: true,
      layout: 'dashboard',
      title: 'Dashboard',
    },
  },
  {
    path: '/admin/dashboard',
    name: 'admin.dashboard',
    component: () => import('../views/admin/Dashboard.vue'),
    meta: {
      requiresAuth: true,
      layout: 'dashboard',
      title: 'Admin Dashboard',
    },
  },
  {
    path: '/patient/dashboard',
    name: 'patient.dashboard',
    component: () => import('../views/patient/Dashboard.vue'),
    meta: {
      requiresAuth: true,
      layout: 'dashboard',
      title: 'Patient Dashboard',
    },
  },
  // Add more routes as you convert them
]


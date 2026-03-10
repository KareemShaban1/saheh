import axios from 'axios'

/**
 * Axios instance configured for Laravel API
 */
const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  timeout: 30000, // 30 seconds
})

/**
 * Request interceptor
 * Adds CSRF token and authentication token to requests
 */
api.interceptors.request.use(
  (config) => {
    // Add CSRF token from meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
    if (csrfToken) {
      config.headers['X-CSRF-TOKEN'] = csrfToken
    }

    // Add authorization token if available
    const authToken = localStorage.getItem('auth_token')
    if (authToken) {
      config.headers['Authorization'] = `Bearer ${authToken}`
    }

    // Add X-Requested-With header for Laravel
    config.headers['X-Requested-With'] = 'XMLHttpRequest'

    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

/**
 * Response interceptor
 * Handles common errors and redirects
 */
api.interceptors.response.use(
  (response) => {
    return response
  },
  (error) => {
    if (error.response) {
      // Handle specific status codes
      switch (error.response.status) {
        case 401:
          // Unauthorized - redirect to login
          if (window.location.pathname !== '/login') {
            window.location.href = '/login'
          }
          break
        case 403:
          // Forbidden
          console.error('Access forbidden:', error.response.data.message)
          break
        case 404:
          // Not found
          console.error('Resource not found:', error.response.data.message)
          break
        case 422:
          // Validation errors
          console.error('Validation errors:', error.response.data.errors)
          break
        case 500:
          // Server error
          console.error('Server error:', error.response.data.message)
          break
      }
    } else if (error.request) {
      // Request made but no response received
      console.error('Network error:', error.message)
    } else {
      // Something else happened
      console.error('Error:', error.message)
    }

    return Promise.reject(error)
  }
)

export default api








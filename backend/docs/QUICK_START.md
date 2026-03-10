# Quick Start Guide - Vue Dashboard Conversion

This is a quick reference guide to get you started with the Vue dashboard conversion.

## Step 1: Install Dependencies

**Important:** Use the version compatible with your Vite version:
- For **Vite 3.x**: Use `@vitejs/plugin-vue@^3.2.0`
- For **Vite 4.x**: Use `@vitejs/plugin-vue@^4.0.0`
- For **Vite 5.x+**: Use `@vitejs/plugin-vue@^5.0.0`

```bash
# For Vite 3 (your current setup)
npm install vue@^3.3.0 vue-router@^4.2.0 pinia@^2.1.0 @vitejs/plugin-vue@^3.2.0
npm install -D @vue/compiler-sfc@^3.3.0
```

## Step 2: Verify Vite Configuration

The `vite.config.js` has been updated to support Vue. Make sure it includes:
- `@vitejs/plugin-vue` plugin
- `vue-app.js` in the input array
- Vue file watching in refresh paths
- Path aliases (`@` and `@vue`)

## Step 3: Create API Endpoint

You need to create an API endpoint that returns JSON data instead of a Blade view.

**Create:** `app/Http/Controllers/Api/Clinic/DashboardController.php`

```php
<?php

namespace App\Http\Controllers\Api\Clinic;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Modules\Clinic\Doctor\Models\Doctor;
use App\Models\Shared\Patient;
use Modules\Clinic\Reservation\Models\Reservation;
use App\Models\OnlineReservation;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $current_date = Carbon::now('Egypt')->format('Y-m-d');
        $current_month = Carbon::now('Egypt')->format('m');

        $data = [
            'stats' => [
                'doctors_count' => Doctor::count(),
                'patients_count' => Patient::query()->clinic()->count(),
                'medicines_count' => 0, // Update based on your model
                'today_res_count' => Reservation::where('date', $current_date)->count(),
                'online_reservations_count' => OnlineReservation::count(),
                'all_reservations_count' => Reservation::count(),
                'today_payment' => Reservation::where('date', $current_date)->sum('cost'),
                'month_payment' => Reservation::where('month', $current_month)
                    ->where('payment', 'paid')->sum('cost'),
            ],
            'last_patients' => Patient::clinic()
                ->select('id', 'name', 'phone')
                ->withCount('reservations')
                ->latest()
                ->take(5)
                ->get()
                ->toArray(),
            'reservations' => Reservation::with('patient:id,name')
                ->latest()
                ->take(5)
                ->get()
                ->toArray(),
            'online_reservations' => OnlineReservation::latest()
                ->take(5)
                ->get()
                ->toArray(),
            'doctors' => Doctor::all()->toArray(),
            'doctor_weekly_slots' => [], // Implement based on your logic
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
```

**Add route in `routes/api.php`:**

```php
Route::prefix('clinic')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Api\Clinic\DashboardController::class, 'index']);
});
```

## Step 4: Add Vue Route

Add a route to access the Vue dashboard (parallel to Blade):

**In `routes/clinic.php`:**

```php
// Vue Dashboard (parallel to Blade)
Route::get('/vue/dashboard', function () {
    return view('backend.dashboards.clinic.vue.dashboard');
})->name('vue.dashboard');
```

## Step 5: Build Assets

```bash
npm run dev
# or for production
npm run build
```

## Step 6: Test the Vue Dashboard

1. Start your Laravel server: `php artisan serve`
2. Navigate to: `http://localhost:8000/vue/clinic/dashboard`
3. You should see the Vue dashboard loading

## Step 7: Development Workflow

1. **Make changes** to Vue files in `resources/js/vue/`
2. **Vite will hot-reload** automatically (if running `npm run dev`)
3. **Test** in browser
4. **Build for production** when ready: `npm run build`

## Common Issues & Solutions

### Issue: Vue app not mounting
**Solution:** Check browser console for errors. Ensure:
- `#vue-app` div exists in Blade template
- Vite assets are compiled (`npm run dev`)
- No JavaScript errors in console

### Issue: API returns 401 Unauthorized
**Solution:** 
- Check authentication middleware
- Ensure CSRF token is included
- Verify API route uses correct guard

### Issue: Components not found
**Solution:**
- Check import paths use `@vue/` alias
- Verify file paths match exactly
- Rebuild assets: `npm run build`

### Issue: Store not working
**Solution:**
- Ensure Pinia is installed: `npm install pinia`
- Check store is imported correctly
- Verify store is registered in `vue-app.js`

## Next Steps

1. **Convert more components**: Start with smaller components
2. **Add more stores**: Create stores for other features (patients, reservations, etc.)
3. **Add composables**: Extract reusable logic
4. **Add error handling**: Implement global error handling
5. **Add loading states**: Improve UX with loading indicators
6. **Add tests**: Write unit tests for components and stores

## File Structure Created

```
resources/js/
├── vue-app.js                    # Vue entry point
└── vue/
    ├── app.vue                   # Root component
    ├── router/
    │   ├── index.js              # Router setup
    │   └── routes.js             # Route definitions
    ├── stores/
    │   ├── index.js              # Pinia setup
    │   └── modules/
    │       └── clinic/
    │           └── dashboard.js  # Dashboard store
    ├── components/
    │   ├── common/
    │   │   └── LoadingSpinner.vue
    │   └── dashboard/
    │       ├── StatCard.vue
    │       ├── RecentPatientsTable.vue
    │       ├── RecentReservationsTable.vue
    │       ├── FastActionsPanel.vue
    │       └── FastActionItem.vue
    ├── views/
    │   └── clinic/
    │       └── Dashboard.vue     # Main dashboard view
    └── utils/
        └── api.js                # API utility
```

## Resources

- **Full Guide**: See `VUE_DASHBOARD_CONVERSION_GUIDE.md`
- **Vue Docs**: https://vuejs.org/
- **Pinia Docs**: https://pinia.vuejs.org/
- **Vite Docs**: https://vitejs.dev/

---

**Happy Coding! 🚀**


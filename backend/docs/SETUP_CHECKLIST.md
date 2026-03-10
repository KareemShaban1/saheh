# Setup Checklist - Vue Dashboard Conversion

Use this checklist to ensure everything is set up correctly.

## Pre-Installation Checklist

- [ ] Node.js 18+ is installed (`node --version`)
- [ ] npm/yarn is installed (`npm --version`)
- [ ] Laravel 10+ project is running
- [ ] Current Blade dashboard is working

## Installation Steps

### Step 1: Install Vue Dependencies

**Important:** Use the version compatible with your Vite version.

For Vite 3.x (your current setup):
```bash
npm install vue@^3.3.0 vue-router@^4.2.0 pinia@^2.1.0 @vitejs/plugin-vue@^3.2.0
npm install -D @vue/compiler-sfc@^3.3.0
```

For Vite 4.x:
```bash
npm install vue@^3.3.0 vue-router@^4.2.0 pinia@^2.1.0 @vitejs/plugin-vue@^4.0.0
npm install -D @vue/compiler-sfc@^3.3.0
```

**Check:**
- [ ] All packages installed without errors
- [ ] `package.json` updated with new dependencies
- [ ] `node_modules` contains vue, vue-router, pinia

### Step 2: Verify Vite Configuration

**File:** `vite.config.js`

**Check:**
- [ ] `@vitejs/plugin-vue` is imported
- [ ] `vue()` plugin is added to plugins array
- [ ] `vue-app.js` is in the input array
- [ ] `resources/js/vue/**` is in refresh paths
- [ ] Path aliases (`@` and `@vue`) are configured

### Step 3: Create API Endpoint

**File:** `app/Http/Controllers/Api/Clinic/DashboardController.php`

**Check:**
- [ ] Controller file created
- [ ] Returns JSON response
- [ ] Includes all dashboard data (stats, patients, reservations, etc.)
- [ ] Error handling implemented
- [ ] Matches structure expected by Vue store

**Route:** `routes/api.php`

**Check:**
- [ ] Route added: `GET /api/clinic/dashboard`
- [ ] Middleware configured (auth:sanctum or auth:web)
- [ ] Route is accessible

**Test:**
```bash
# Test API endpoint (replace token with actual token)
curl -X GET http://localhost:8000/api/clinic/dashboard \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

- [ ] API returns 200 status
- [ ] Response has `success: true`
- [ ] Response has `data` object with all required fields

### Step 4: Create Vue Route

**File:** `routes/clinic.php`

**Check:**
- [ ] Route added: `GET /vue/clinic/dashboard`
- [ ] Returns Blade view: `backend.dashboards.clinic.vue.dashboard`
- [ ] Route name: `vue.dashboard`

**Test:**
- [ ] Navigate to `/vue/clinic/dashboard` in browser
- [ ] Page loads without errors
- [ ] `#vue-app` div is present in HTML

### Step 5: Verify File Structure

**Check all files exist:**

- [ ] `resources/js/vue-app.js`
- [ ] `resources/js/vue/app.vue`
- [ ] `resources/js/vue/router/index.js`
- [ ] `resources/js/vue/router/routes.js`
- [ ] `resources/js/vue/stores/index.js`
- [ ] `resources/js/vue/stores/modules/clinic/dashboard.js`
- [ ] `resources/js/vue/utils/api.js`
- [ ] `resources/js/vue/components/common/LoadingSpinner.vue`
- [ ] `resources/js/vue/components/dashboard/StatCard.vue`
- [ ] `resources/js/vue/components/dashboard/RecentPatientsTable.vue`
- [ ] `resources/js/vue/components/dashboard/RecentReservationsTable.vue`
- [ ] `resources/js/vue/components/dashboard/FastActionsPanel.vue`
- [ ] `resources/js/vue/components/dashboard/FastActionItem.vue`
- [ ] `resources/js/vue/views/clinic/Dashboard.vue`
- [ ] `resources/views/backend/dashboards/clinic/vue/dashboard.blade.php`

### Step 6: Build Assets

```bash
npm run dev
```

**Check:**
- [ ] Vite compiles without errors
- [ ] No TypeScript/JavaScript errors
- [ ] Vue files are processed
- [ ] Assets are generated in `public/build/`

**Watch for:**
- Module not found errors
- Import path errors
- Syntax errors in Vue files

### Step 7: Test Vue Dashboard

**Browser Test:**
1. Navigate to `/vue/clinic/dashboard`
2. Open browser DevTools (F12)
3. Check Console tab

**Check:**
- [ ] No JavaScript errors in console
- [ ] Vue app mounts successfully
- [ ] Network tab shows API call to `/api/clinic/dashboard`
- [ ] API returns 200 status
- [ ] Data displays correctly
- [ ] Loading spinner shows while loading
- [ ] Error message shows if API fails

### Step 8: Verify Functionality

**Test Each Feature:**
- [ ] Statistics cards display correct numbers
- [ ] Last patients table shows data
- [ ] Recent reservations table shows data
- [ ] Tab switching works (patients/reservations)
- [ ] Fast actions panel links work
- [ ] Loading states work
- [ ] Error states work
- [ ] Responsive design works (mobile/tablet/desktop)

## Common Issues & Solutions

### Issue: "Cannot find module '@vue/...'"
**Solution:** 
- Check path aliases in `vite.config.js`
- Verify import paths use `@vue/` prefix
- Rebuild assets: `npm run build`

### Issue: "Vue is not defined"
**Solution:**
- Ensure Vue is installed: `npm install vue`
- Check `vue-app.js` imports Vue correctly
- Verify Vite plugin is configured

### Issue: "API returns 401 Unauthorized"
**Solution:**
- Check authentication middleware
- Verify CSRF token is included
- Check API route uses correct guard
- Test API endpoint directly with Postman/curl

### Issue: "Store is undefined"
**Solution:**
- Ensure Pinia is installed: `npm install pinia`
- Check store is imported correctly
- Verify Pinia is registered in `vue-app.js`

### Issue: "Component not found"
**Solution:**
- Check file path matches import path exactly
- Verify component file exists
- Check for typos in component name
- Rebuild assets

### Issue: "Hot reload not working"
**Solution:**
- Ensure `npm run dev` is running
- Check Vite refresh paths include Vue files
- Clear browser cache
- Restart Vite dev server

## Post-Setup Verification

### Code Quality Checks
- [ ] No console errors
- [ ] No TypeScript/ESLint warnings
- [ ] Components follow naming conventions
- [ ] Code follows clean code principles
- [ ] Comments added where needed

### Performance Checks
- [ ] Initial load time is acceptable
- [ ] API calls are optimized
- [ ] No unnecessary re-renders
- [ ] Images/assets are optimized

### Security Checks
- [ ] CSRF tokens are included
- [ ] Authentication is verified
- [ ] API endpoints are protected
- [ ] No sensitive data in client code

## Next Steps After Setup

1. **Customize API Response**: Adjust data structure to match your needs
2. **Add More Components**: Convert more dashboard sections
3. **Add Error Handling**: Implement global error handling
4. **Add Loading States**: Improve UX
5. **Add Tests**: Write unit tests
6. **Optimize**: Code splitting, lazy loading
7. **Document**: Add JSDoc comments

## Success Criteria

✅ All checklist items completed  
✅ Vue dashboard loads without errors  
✅ Data displays correctly  
✅ All features work as expected  
✅ No console errors  
✅ Performance is acceptable  

---

**Once all items are checked, you're ready to start converting more features!**


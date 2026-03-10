# Fix: Vite IPv6 Timeout Issue

## Problem
Vite was trying to use IPv6 (`[::1]`) which caused 504 Gateway Timeout errors:
```
GET http://[::1]:5173/node_modules/.vite/deps/vue.js?v=64e13bf6 net::ERR_ABORTED 504 (Gateway Timeout)
```

## Solution
Updated `vite.config.js` to force IPv4 (`127.0.0.1`) instead of IPv6.

## What Changed

Added server configuration to `vite.config.js`:
```javascript
server: {
    host: '127.0.0.1', // Force IPv4 instead of IPv6
    port: 5173,
    strictPort: true,
    hmr: {
        host: '127.0.0.1', // Force IPv4 for HMR
    },
}
```

## Steps to Fix

1. **Stop Vite dev server** (if running):
   - Press `Ctrl+C` in the terminal running `npm run dev`

2. **Restart Vite dev server**:
   ```bash
   npm run dev
   ```

3. **Verify it's using IPv4**:
   You should see:
   ```
   VITE v3.x.x  ready in xxx ms
   
   ➜  Local:   http://127.0.0.1:5173/
   ```
   (Not `[::1]:5173`)

4. **Clear browser cache**:
   - Hard refresh: `Ctrl+Shift+R` (Windows) or `Cmd+Shift+R` (Mac)
   - Or clear browser cache

5. **Try accessing dashboard again**:
   ```
   http://localhost:8000/ar/clinic/vue/dashboard
   ```

## Alternative: Use Production Build

If dev server still has issues, you can build for production:

```bash
npm run build
```

This creates static assets in `public/build/` that don't require the Vite dev server.

**Note:** You'll need to rebuild after making changes to Vue files.

## Verify Fix

1. **Check Vite is running on IPv4:**
   - Look at terminal output
   - Should show `http://127.0.0.1:5173/`

2. **Check browser console:**
   - Open DevTools (F12)
   - Network tab should show requests to `127.0.0.1:5173` (not `[::1]:5173`)
   - No more 504 errors

3. **Check Vue app loads:**
   - Dashboard should load
   - No console errors
   - Vue components render

## If Still Having Issues

1. **Check firewall:**
   - Windows Firewall might be blocking port 5173
   - Allow Node.js through firewall

2. **Try different port:**
   ```javascript
   server: {
       host: '127.0.0.1',
       port: 5174, // Try different port
   }
   ```

3. **Check if port is in use:**
   ```bash
   netstat -ano | findstr :5173
   ```
   If something is using the port, kill it or use a different port

4. **Use production build:**
   ```bash
   npm run build
   ```
   This doesn't require the dev server

---

**The fix should resolve the IPv6 timeout issue!**








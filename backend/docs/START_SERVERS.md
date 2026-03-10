# How to Start Servers for Vue Dashboard

## 🚀 Quick Start

You need **TWO terminals** running simultaneously:

### Terminal 1: Laravel Server

```bash
php artisan serve
```

**Expected output:**
```
INFO  Server running on [http://127.0.0.1:8000]
```

**Keep this terminal open!** The server must stay running.

---

### Terminal 2: Vite Dev Server

```bash
npm run dev
```

**Expected output:**
```
  VITE v3.x.x  ready in xxx ms

  ➜  Local:   http://localhost:5173/
  ➜  Network: use --host to expose
```

**Keep this terminal open too!** Vite needs to stay running for hot-reloading.

---

## ✅ Verify Servers Are Running

### Check Laravel Server:
Open browser and visit: `http://localhost:8000`

You should see your Laravel application (or login page).

### Check Vite Server:
The Vite server runs on port 5173, but you don't need to access it directly. It works automatically when you load pages that use Vite assets.

---

## 🌐 Access Vue Dashboard

Once **both servers are running**:

1. **Make sure you're logged in** to your clinic account
2. Navigate to:
   ```
   http://localhost:8000/ar/clinic/vue/dashboard
   ```
   or
   ```
   http://localhost:8000/en/clinic/vue/dashboard
   ```

---

## 🔧 Troubleshooting

### Issue: "ERR_CONNECTION_REFUSED"

**Solution:**
- Laravel server is NOT running
- Start it: `php artisan serve`
- Wait for: `Server running on [http://127.0.0.1:8000]`

### Issue: "Vite assets not loading"

**Solution:**
- Vite dev server is NOT running
- Start it: `npm run dev`
- Keep the terminal open

### Issue: "404 Not Found" on Vue dashboard

**Solution:**
1. Check route exists:
   ```bash
   php artisan route:list | findstr vue
   ```
2. Make sure you're logged in
3. Check locale is correct (ar/en)

### Issue: "Port 8000 already in use"

**Solution:**
Use a different port:
```bash
php artisan serve --port=8001
```
Then access: `http://localhost:8001/ar/clinic/vue/dashboard`

---

## 📝 Windows PowerShell Commands

### Start Laravel (Terminal 1):
```powershell
cd E:\My_Work\Laravel\clinic_system_saas
php artisan serve
```

### Start Vite (Terminal 2):
```powershell
cd E:\My_Work\Laravel\clinic_system_saas
npm run dev
```

---

## 🎯 Quick Checklist

Before accessing Vue dashboard:

- [ ] Laravel server running (`php artisan serve`)
- [ ] Vite dev server running (`npm run dev`)
- [ ] Both terminals kept open
- [ ] Logged in to clinic account
- [ ] Using correct URL with locale

---

## 💡 Pro Tip

Create a batch file to start both servers:

**Create `start-dev.bat`:**
```batch
@echo off
start "Laravel Server" cmd /k "php artisan serve"
timeout /t 2
start "Vite Server" cmd /k "npm run dev"
```

Then just double-click `start-dev.bat` to start both servers!

---

**Remember: Keep both terminals open while developing!**








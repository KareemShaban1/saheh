<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>MedPortal — Provider Login</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
<style>
  :root {
    --primary: #4338ca;
    --primary-dark: #3730a3;
    --primary-light: rgba(67,56,202,0.08);
    --secondary: #2563eb;
    --secondary-light: rgba(37,99,235,0.08);
  }

  * { box-sizing: border-box; }

  body {
    font-family: 'DM Sans', sans-serif;
    background: #f8f8fc;
    min-height: 100vh;
    margin: 0;
  }

  /* ── Branding panel ───────────────────────── */
  .brand-panel {
    background: linear-gradient(145deg, #4338ca 0%, #2563eb 55%, #1d4ed8 100%);
    position: relative;
    overflow: hidden;
  }

  .brand-panel::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
      radial-gradient(ellipse 80% 60% at 20% 30%, rgba(255,255,255,0.07) 0%, transparent 60%),
      radial-gradient(ellipse 60% 80% at 80% 80%, rgba(67,56,202,0.35) 0%, transparent 60%);
  }

  .brand-panel::after {
    content: '';
    position: absolute;
    width: 420px; height: 420px;
    border-radius: 50%;
    border: 1px solid rgba(255,255,255,0.08);
    bottom: -120px; right: -120px;
  }

  .ring {
    position: absolute;
    border-radius: 50%;
    border: 1px solid rgba(255,255,255,0.06);
  }

  /* ── Card ──────────────────────────────────── */
  #login-card {
    opacity: 0;
    transform: translateY(22px);
    animation: cardIn 0.65s cubic-bezier(0.22,1,0.36,1) 0.1s forwards;
  }

  @keyframes cardIn {
    to { opacity: 1; transform: translateY(0); }
  }

  /* ── Input focus ring ──────────────────────── */
  .field-input {
    transition: border-color 0.2s, box-shadow 0.2s;
  }
  .field-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(67,56,202,0.12);
  }

  /* ── Primary button ────────────────────────── */
  .btn-primary {
    background: var(--primary);
    transition: background 0.18s, transform 0.12s, box-shadow 0.18s;
  }
  .btn-primary:hover {
    background: var(--primary-dark);
    box-shadow: 0 6px 24px rgba(67,56,202,0.35);
    transform: translateY(-1px);
  }
  .btn-primary:active {
    transform: translateY(0);
    box-shadow: none;
  }

  /* ── Google button ─────────────────────────── */
  .btn-google {
    transition: background 0.15s, box-shadow 0.15s;
  }
  .btn-google:hover {
    background: #f1f1f9;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
  }

  /* ── Staggered brand text ──────────────────── */
  .brand-item {
    opacity: 0;
    transform: translateX(-14px);
    animation: slideR 0.5s ease forwards;
  }
  .brand-item:nth-child(1) { animation-delay: 0.35s; }
  .brand-item:nth-child(2) { animation-delay: 0.5s; }
  .brand-item:nth-child(3) { animation-delay: 0.65s; }

  @keyframes slideR {
    to { opacity: 1; transform: translateX(0); }
  }

  /* ── Shake animation for error ─────────────── */
  @keyframes shake {
    0%,100%{transform:translateX(0)}
    20%{transform:translateX(-6px)}
    40%{transform:translateX(6px)}
    60%{transform:translateX(-4px)}
    80%{transform:translateX(4px)}
  }
  .shake { animation: shake 0.38s ease; }

  /* ── Feature pills ──────────────────────────── */
  .feature-pill {
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(6px);
    border: 1px solid rgba(255,255,255,0.15);
  }

  /* ── Responsive: stack on mobile ───────────── */
  @media (max-width: 767px) {
    .brand-panel { display: none; }
  }
</style>
</head>
<body class="flex flex-col min-h-screen">

<!-- ═══════ HEADER ═══════ -->
<header class="w-full px-6 py-4 flex items-center justify-between bg-white border-b border-gray-100 z-10">
  <a href="#" class="flex items-center gap-2.5 group" aria-label="MedPortal home">
    <!-- Logo mark -->
    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:linear-gradient(135deg,#4338ca,#2563eb)">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
      </svg>
    </div>
    <span class="font-semibold text-gray-900 tracking-tight" style="font-family:'DM Serif Display',serif;font-size:1.15rem;">MedPortal</span>
  </a>

  <a href="#" class="text-sm font-medium flex items-center gap-1.5 transition-colors" style="color:var(--secondary);">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
    Back to home
  </a>
</header>

<!-- ═══════ MAIN ═══════ -->
<main class="flex flex-1">

  <!-- LEFT — Branding panel -->
  <div class="brand-panel hidden md:flex flex-col justify-between w-1/2 p-12 lg:p-16 relative z-0">

    <!-- Decorative rings -->
    <div class="ring" style="width:300px;height:300px;top:-80px;left:-80px;"></div>
    <div class="ring" style="width:200px;height:200px;top:60px;right:40px;"></div>

    <!-- Top logo text (white) -->
    <div class="relative z-10">
      <div class="flex items-center gap-2.5">
        <div class="w-9 h-9 rounded-xl bg-white/15 border border-white/20 flex items-center justify-center">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
          </svg>
        </div>
        <span class="text-white font-semibold text-lg tracking-tight" style="font-family:'DM Serif Display',serif;">MedPortal</span>
      </div>
    </div>

    <!-- Center content -->
    <div class="relative z-10 space-y-8">
      <div>
        <p class="text-indigo-200 text-xs font-semibold tracking-widest uppercase mb-4">Healthcare Provider Platform</p>
        <h1 class="text-white leading-tight" style="font-family:'DM Serif Display',serif;font-size:clamp(1.9rem,3vw,2.7rem);">
          Your clinical workspace,<br/>
          <em>unified.</em>
        </h1>
        <p class="mt-4 text-indigo-100 text-sm leading-relaxed max-w-xs">
          Securely manage patients, lab results, imaging reports, and referrals — all from one intelligent dashboard built for modern healthcare teams.
        </p>
      </div>

      <!-- Feature pills -->
      <div class="space-y-3">
        <div class="brand-item feature-pill rounded-xl px-4 py-3 flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-white/15 flex items-center justify-center shrink-0">
            <svg width="15" height="15" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
          </div>
          <span class="text-white text-sm font-medium">Integrated scheduling & patient records</span>
        </div>
        <div class="brand-item feature-pill rounded-xl px-4 py-3 flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-white/15 flex items-center justify-center shrink-0">
            <svg width="15" height="15" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          </div>
          <span class="text-white text-sm font-medium">HIPAA-compliant, end-to-end encrypted</span>
        </div>
        <div class="brand-item feature-pill rounded-xl px-4 py-3 flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-white/15 flex items-center justify-center shrink-0">
            <svg width="15" height="15" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
          </div>
          <span class="text-white text-sm font-medium">Real-time lab & radiology results</span>
        </div>
      </div>
    </div>

    <!-- Bottom badge -->
    <div class="relative z-10">
      <div class="feature-pill inline-flex items-center gap-2 rounded-full px-4 py-2">
        <div class="w-2 h-2 rounded-full bg-emerald-400"></div>
        <span class="text-white/80 text-xs">Trusted by 4,200+ providers across 38 countries</span>
      </div>
    </div>
  </div>

  <!-- RIGHT — Login form -->
  <div class="flex-1 flex items-center justify-center px-5 py-12 md:py-0 bg-white md:bg-gray-50">
    <div id="login-card" class="w-full max-w-md">

      <!-- Mobile-only logo -->
      <div class="flex items-center gap-2 mb-8 md:hidden">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:linear-gradient(135deg,#4338ca,#2563eb)">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
        </div>
        <span class="font-semibold text-gray-900" style="font-family:'DM Serif Display',serif;font-size:1.1rem;">MedPortal</span>
      </div>

      <!-- Heading -->
      <div class="mb-8">
        <h2 class="text-gray-900 font-semibold" style="font-family:'DM Serif Display',serif;font-size:1.85rem;line-height:1.2;">Welcome back</h2>
        <p class="mt-1.5 text-sm text-gray-500">Sign in to your provider account to continue.</p>
      </div>

      <!-- Error alert -->
      <div id="error-alert" class="hidden mb-5 rounded-xl px-4 py-3 text-sm flex items-start gap-3" style="background:rgba(220,38,38,0.06);border:1px solid rgba(220,38,38,0.18);color:#b91c1c;">
        <svg class="shrink-0 mt-0.5" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span id="error-msg">Invalid email or password. Please try again.</span>
      </div>

      <!-- FORM -->
      <form id="login-form" method="POST" action="{{ Route('login') }}" novalidate class="space-y-4">
	@csrf
        <!-- Email -->
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email address</label>
          <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
            </span>
            <input
              id="email" name="email" type="email" autocomplete="email"
              placeholder="you@clinic.com"
              class="field-input w-full pl-10 pr-4 py-2.5 text-sm text-gray-900 border border-gray-200 rounded-xl bg-white placeholder-gray-400"
            />
          </div>
          <p id="email-err" class="hidden mt-1 text-xs text-red-600">Please enter a valid email address.</p>
        </div>

        <!-- Password -->
        <div>
          <div class="flex items-center justify-between mb-1.5">
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <a href="#" class="text-xs font-medium transition-colors" style="color:var(--secondary);" tabindex="-1">Forgot password?</a>
          </div>
          <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </span>
            <input
              id="password" name="password" type="password" autocomplete="current-password"
              placeholder="••••••••"
              class="field-input w-full pl-10 pr-11 py-2.5 text-sm text-gray-900 border border-gray-200 rounded-xl bg-white placeholder-gray-400"
            />
            <button type="button" id="pw-toggle" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors" aria-label="Toggle password visibility">
              <svg id="eye-show" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg id="eye-hide" class="hidden" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
          <p id="pw-err" class="hidden mt-1 text-xs text-red-600">Password must be at least 8 characters.</p>
        </div>

        <!-- Remember me -->
        <div class="flex items-center gap-2.5">
          <input id="remember" name="remember" type="checkbox" class="w-4 h-4 rounded cursor-pointer" style="accent-color:var(--primary);"/>
          <label for="remember" class="text-sm text-gray-600 cursor-pointer select-none">Remember me for 30 days</label>
        </div>

        <!-- Submit -->
        <button type="submit" id="submit-btn" class="btn-primary w-full flex items-center justify-center gap-2 text-white font-semibold text-sm py-2.5 rounded-xl mt-2">
          <span id="btn-text">Log in</span>
          <svg id="btn-spinner" class="hidden animate-spin" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
        </button>
      </form>

      <!-- Divider -->
      <div class="flex items-center gap-3 my-5">
        <div class="flex-1 h-px bg-gray-200"></div>
        <span class="text-xs text-gray-400 font-medium">Or continue with</span>
        <div class="flex-1 h-px bg-gray-200"></div>
      </div>

      <!-- Google SSO -->
      <!-- <button type="button" class="btn-google w-full flex items-center justify-center gap-3 border border-gray-200 rounded-xl py-2.5 bg-white text-sm font-medium text-gray-700">
        <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
          <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
          <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
          <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
        </svg>
        Continue with Google Workspace
      </button> -->

      <!-- Register -->
      <p class="mt-6 text-center text-sm text-gray-500">
        Don't have an account?
        <a href="#" class="font-semibold transition-colors" style="color:var(--secondary);">Register your facility</a>
      </p>

    </div>
  </div>

</main>

<!-- ═══════ FOOTER ═══════ -->
<footer class="bg-white border-t border-gray-100 px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-gray-400">
  <span>© 2025 MedPortal Technologies, Inc.</span>
  <div class="flex items-center gap-4">
    <a href="#" class="hover:text-gray-600 transition-colors">Privacy Policy</a>
    <a href="#" class="hover:text-gray-600 transition-colors">Terms of Service</a>
    <a href="#" class="hover:text-gray-600 transition-colors">HIPAA Notice</a>
    <a href="#" class="hover:text-gray-600 transition-colors">Support</a>
  </div>
</footer>

<script>
  /* ── Password toggle ─────────────────── */
  const pwInput   = document.getElementById('password');
  const eyeShow   = document.getElementById('eye-show');
  const eyeHide   = document.getElementById('eye-hide');

  document.getElementById('pw-toggle').addEventListener('click', () => {
    const isHidden = pwInput.type === 'password';
    pwInput.type = isHidden ? 'text' : 'password';
    eyeShow.classList.toggle('hidden', isHidden);
    eyeHide.classList.toggle('hidden', !isHidden);
  });

  /* ── Form validation & submit ────────── */
  const form       = document.getElementById('login-form');
  const emailInput = document.getElementById('email');
  const emailErr   = document.getElementById('email-err');
  const pwErr      = document.getElementById('pw-err');
  const errorAlert = document.getElementById('error-alert');
  const submitBtn  = document.getElementById('submit-btn');
  const btnText    = document.getElementById('btn-text');
  const spinner    = document.getElementById('btn-spinner');

  function validateEmail(val) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val); }
  function validatePw(val)    { return val.length >= 8; }

  function setFieldError(input, errEl, show) {
    errEl.classList.toggle('hidden', !show);
    input.style.borderColor = show ? '#ef4444' : '';
    if (show) input.style.boxShadow = '0 0 0 3px rgba(239,68,68,0.1)';
    else       input.style.boxShadow = '';
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const emailVal = emailInput.value.trim();
    const pwVal    = pwInput.value;
    let valid = true;

    if (!validateEmail(emailVal)) { setFieldError(emailInput, emailErr, true); valid = false; }
    else { setFieldError(emailInput, emailErr, false); }

    if (!validatePw(pwVal)) { setFieldError(pwInput, pwErr, true); valid = false; }
    else { setFieldError(pwInput, pwErr, false); }

    if (!valid) return;

    /* Simulate loading */
    btnText.textContent = 'Signing in…';
    spinner.classList.remove('hidden');
    submitBtn.disabled = true;

    await new Promise(r => setTimeout(r, 1800));

    /* Demo: always show error for any credentials */
    btnText.textContent = 'Log in';
    spinner.classList.add('hidden');
    submitBtn.disabled = false;

    errorAlert.classList.remove('hidden');
    const card = document.getElementById('login-card');
    card.classList.remove('shake');
    void card.offsetWidth;
    card.classList.add('shake');
    setTimeout(() => errorAlert.classList.add('hidden'), 5000);
  });

  /* Clear field error on input */
  emailInput.addEventListener('input', () => setFieldError(emailInput, emailErr, false));
  pwInput.addEventListener('input',    () => setFieldError(pwInput,    pwErr,    false));
</script>
</body>
</html>
	<!-- HEADER -->
	<header id="header">
		<a class="logo" href="{{ url('/') }}">
			<div class="logo-icon">
				<i class="fas fa-heartbeat text-xl text-white"></i>
			</div>
			<span>صحيح</span>
		</a>
		<nav id="main-nav">
			<a href="{{ route('clinics') }}">العيادات</a>
			<a href="{{ route('medical-laboratories') }}">المختبرات</a>
			<a href="{{ route('radiology-centers') }}">مراكز الأشعة</a>
			<a href="#how">كيف نعمل</a>
			<a href="#social">من نحن</a>
		</nav>
		<div class="header-actions" id="header-actions">
			<a href="{{ route('register-clinic') }}" class="btn-ghost">تسجيل عيادة</a>
			<a href="{{ route('register-medical-laboratory') }}" class="btn-ghost">تسجيل مختبر</a>
			<a href="{{ route('register-radiology-center') }}" class="btn-primary">انضم كمركز أشعة</a>
		</div>
		<div class="hamburger" id="hamburger" aria-label="القائمة">
			<span></span><span></span><span></span>
		</div>
	</header>

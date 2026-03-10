@extends('frontend.layouts.app')
@section('content')
	<!-- HERO -->
	<section class="hero">
		<div class="hero-inner">
			<div class="hero-content">
				<div class="hero-badge">🏥 رعاية صحية بسيطة وموثوقة</div>
				<h1>صحتك،<br><em>منصة واحدة</em></h1>
				<p>تواصل فوراً مع أفضل العيادات والمختبرات الطبية المعتمدة ومراكز الأشعة. احجز مواعيدك، اطلّع على التقارير الرقمية، وأدر صحتك من مكان واحد.</p>
				<div class="hero-ctas">
					<a href="{{ route('clinics') }}" class="cta-primary">
						<span>ابحث عن عيادة</span>
						<span>←</span>
					</a>
					<a href="#how" class="cta-secondary">
						<span>كيف يعمل</span>
					</a>
				</div>
			</div>
			<div class="hero-visual">
				<div class="hero-card-stack">
					<div class="floating-badge fb-top">
						<span class="fb-icon">✅</span>
						<div>
							<div style="font-size:0.82rem;font-weight:600;">تم تأكيد الحجز</div>
							<div style="font-size:0.72rem;color:#6b7280;">اليوم 3:00 م</div>
						</div>
					</div>
					<div class="hero-main-card">
						<div class="card-header-row">
							<div class="avatar">👩‍⚕️</div>
							<div>
								<div class="card-title">مواعيدي</div>
								<div class="card-sub">{{ now()->translatedFormat('F Y') }}</div>
							</div>
						</div>
						<div class="appointment-list">
							<div class="apt-item">
								<span class="apt-icon">🏥</span>
								<div class="apt-info">
									<div class="apt-name">كشف عام</div>
									<div class="apt-time">عيادة · {{ now()->addDays(2)->translatedFormat('d M') }}</div>
								</div>
								<span class="apt-badge badge-green">مؤكد</span>
							</div>
							<div class="apt-item">
								<span class="apt-icon">🔬</span>
								<div class="apt-info">
									<div class="apt-name">تحاليل دم</div>
									<div class="apt-time">مختبر · {{ now()->addDays(5)->translatedFormat('d M') }}</div>
								</div>
								<span class="apt-badge badge-blue">مجدول</span>
							</div>
							<div class="apt-item">
								<span class="apt-icon">🩻</span>
								<div class="apt-info">
									<div class="apt-name">أشعة رنين</div>
									<div class="apt-time">مركز أشعة · {{ now()->addDays(10)->translatedFormat('d M') }}</div>
								</div>
								<span class="apt-badge badge-purple">قيد الانتظار</span>
							</div>
						</div>
					</div>
					<div class="floating-badge fb-bottom">
						<span class="fb-icon">📊</span>
						<div>
							<div style="font-size:0.82rem;font-weight:600;">النتائج جاهزة</div>
							<div style="font-size:0.72rem;color:#6b7280;">اطلع على تقريرك</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- VALUE PROPS -->
	<section class="value-props">
		<div class="section-inner">
			<div class="value-header reveal">
				<span class="section-label">لماذا صحيح</span>
				<h2 class="section-heading">كل ما تحتاجه لصحتك<br>في مكان واحد</h2>
				<p class="section-sub">تجربة رعاية صحية سلسة مبنية حول احتياجاتك — سريعة، شفافة، ومتاحة دائماً.</p>
			</div>
			<div class="value-grid">
				<div class="value-card reveal">
					<div class="value-icon vi-1">📅</div>
					<h3>حجز المواعيد</h3>
					<p>جدول فوراً مع العيادات والأخصائيين دون مكالمات أو انتظار.</p>
				</div>
				<div class="value-card reveal">
					<div class="value-icon vi-2">🔬</div>
					<h3>مختبرات موثوقة</h3>
					<p>شبكة معتمدة من المختبرات للتحاليل الدقيقة والسريعة التي تعتمد عليها.</p>
				</div>
				<div class="value-card reveal">
					<div class="value-icon vi-3">📋</div>
					<h3>تقارير رقمية</h3>
					<p>استلم نتائج التحاليل وتقارير الأشعة رقمياً وقابل للمشاركة مع أي طبيب.</p>
				</div>
				<div class="value-card reveal">
					<div class="value-icon vi-4">🩺</div>
					<h3>منصة واحدة</h3>
					<p>سجلّك الصحي ومواعيدك وملفاتك في لوحة تحكم واحدة وآمنة.</p>
				</div>
			</div>
		</div>
	</section>

	<!-- HOW IT WORKS -->
	<section class="how-it-works" id="how">
		<div class="section-inner">
			<div class="how-header reveal">
				<span class="section-label">البداية</span>
				<h2 class="section-heading">ثلاث خطوات لرعاية أفضل</h2>
				<p class="section-sub">من البحث إلى الموعد في دقائق — بهذه البساطة.</p>
			</div>
			<div class="steps-row">
				<div class="step reveal">
					<div class="step-num">1</div>
					<div class="step-icon-row">🔍</div>
					<h3>ابحث</h3>
					<p>تصفّح العيادات والمختبرات ومراكز الأشعة القريبة حسب التخصص أو التقييم.</p>
				</div>
				<div class="step reveal">
					<div class="step-num">2</div>
					<div class="step-icon-row">👆</div>
					<h3>اختر</h3>
					<p>قارن مقدمي الخدمة حسب التقييمات والخدمات واختر الأنسب لك.</p>
				</div>
				<div class="step reveal">
					<div class="step-num">3</div>
					<div class="step-icon-row">✅</div>
					<h3>احجز</h3>
					<p>أكد موعدك فوراً. استلم التذكيرات والتعليمات والنتائج تلقائياً.</p>
				</div>
			</div>
		</div>
	</section>

	<!-- PROVIDERS -->
	<section class="providers" id="providers">
		<div class="section-inner">
			<div class="providers-header reveal">
				<span class="section-label">شبكتنا</span>
				<h2 class="section-heading">مقدمو خدمات موثوقون<br>جاهزون عندما تحتاجهم</h2>
				<p class="section-sub">اكتشف شبكتنا المتنامية من شركاء الرعاية الصحية المعتمدين.</p>
			</div>
			<div class="tabs">
				<button class="tab-btn active" data-tab="clinics">🏥 العيادات ({{ $clinicsCount }})</button>
				<button class="tab-btn" data-tab="labs">🔬 المختبرات ({{ $medicalLabsCount }})</button>
				<button class="tab-btn" data-tab="radiology">🩻 مراكز الأشعة ({{ $radiologyCentersCount }})</button>
			</div>

			<div class="tab-panel active" id="tab-clinics">
				<div class="provider-grid">
					@forelse($featuredClinics as $clinic)
					<a href="{{ route('clinic.detail', $clinic->id) }}" class="provider-card" style="text-decoration:none;color:inherit;">
						<div class="provider-top">
							<span class="provider-icon">🏥</span>
							<span class="provider-rating">★ {{ number_format($clinic->reviews_avg_rating ?? 0, 1) }}</span>
						</div>
						<h4>{{ $clinic->name }}</h4>
						<p>{{ Str::limit($clinic->description ?? 'عيادة متكاملة لرعايتك الصحية.', 100) }}</p>
						<div class="provider-tags">
							@if($clinic->specialty)
								<span class="provider-tag">{{ $clinic->specialty->name_ar ?? $clinic->specialty->name_en ?? '—' }}</span>
							@endif
							@if($clinic->governorate)
								<span class="provider-tag">{{ $clinic->governorate->name }}</span>
							@endif
						</div>
					</a>
					@empty
					<div class="provider-card" style="grid-column: 1 / -1; text-align: center;">
						<p class="section-sub">لا توجد عيادات مسجلة حالياً. <a href="{{ route('register-clinic') }}" style="color: var(--indigo);">سجّل عيادتك</a>.</p>
					</div>
					@endforelse
				</div>
				@if($featuredClinics->isNotEmpty())
				<div style="text-align: center; margin-top: 24px;">
					<a href="{{ route('clinics') }}" class="cta-secondary">عرض كل العيادات</a>
				</div>
				@endif
			</div>

			<div class="tab-panel" id="tab-labs">
				<div class="provider-grid">
					@forelse($featuredMedicalLaboratories as $lab)
					<a href="{{ route('medical-laboratory.detail', $lab->id) }}" class="provider-card" style="text-decoration:none;color:inherit;">
						<div class="provider-top">
							<span class="provider-icon">🔬</span>
							<span class="provider-rating">★ {{ number_format($lab->reviews_avg_rating ?? 0, 1) }}</span>
						</div>
						<h4>{{ $lab->name }}</h4>
						<p>{{ Str::limit($lab->description ?? 'مختبر طبي معتمد للتحاليل الدقيقة.', 100) }}</p>
						<div class="provider-tags">
							@if($lab->governorate)
								<span class="provider-tag">{{ $lab->governorate->name }}</span>
							@endif
						</div>
					</a>
					@empty
					<div class="provider-card" style="grid-column: 1 / -1; text-align: center;">
						<p class="section-sub">لا توجد مختبرات مسجلة حالياً. <a href="{{ route('register-medical-laboratory') }}" style="color: var(--indigo);">سجّل مختبرك</a>.</p>
					</div>
					@endforelse
				</div>
				@if($featuredMedicalLaboratories->isNotEmpty())
				<div style="text-align: center; margin-top: 24px;">
					<a href="{{ route('medical-laboratories') }}" class="cta-secondary">عرض كل المختبرات</a>
				</div>
				@endif
			</div>

			<div class="tab-panel" id="tab-radiology">
				<div class="provider-grid">
					@forelse($featuredRadiologyCenters as $center)
					<a href="{{ route('radiology-center.detail', $center->id) }}" class="provider-card" style="text-decoration:none;color:inherit;">
						<div class="provider-top">
							<span class="provider-icon">🩻</span>
							<span class="provider-rating">★ {{ number_format($center->reviews_avg_rating ?? 0, 1) }}</span>
						</div>
						<h4>{{ $center->name }}</h4>
						<p>{{ Str::limit($center->description ?? 'مركز أشعة متكامل للتصوير الدقيق.', 100) }}</p>
						<div class="provider-tags">
							@if($center->governorate)
								<span class="provider-tag">{{ $center->governorate->name }}</span>
							@endif
						</div>
					</a>
					@empty
					<div class="provider-card" style="grid-column: 1 / -1; text-align: center;">
						<p class="section-sub">لا توجد مراكز أشعة مسجلة حالياً. <a href="{{ route('register-radiology-center') }}" style="color: var(--indigo);">سجّل مركزك</a>.</p>
					</div>
					@endforelse
				</div>
				@if($featuredRadiologyCenters->isNotEmpty())
				<div style="text-align: center; margin-top: 24px;">
					<a href="{{ route('radiology-centers') }}" class="cta-secondary">عرض كل مراكز الأشعة</a>
				</div>
				@endif
			</div>
		</div>
	</section>

	<!-- SOCIAL PROOF -->
	<section class="social-proof" id="social">
		<div class="section-inner">
			<div class="stats-row reveal">
				<div class="stat-block">
					<div class="stat-num">{{ $clinicsCount + $medicalLabsCount + $radiologyCentersCount }}+</div>
					<div class="stat-label">مركز شريك</div>
				</div>
				<div class="stat-block">
					<div class="stat-num">{{ $patientsCount >= 1000 ? number_format($patientsCount / 1000, 1) . 'k' : $patientsCount }}</div>
					<div class="stat-label">مريض مُخدم</div>
				</div>
				<div class="stat-block">
					<div class="stat-num">{{ $doctorsCount }}+</div>
					<div class="stat-label">طبيب</div>
				</div>
				<div class="stat-block">
					<div class="stat-num">{{ $reservationsCount }}+</div>
					<div class="stat-label">حجز موعد</div>
				</div>
			</div>
			<div class="reveal" style="text-align:center; margin-bottom: 40px;">
				<span class="section-label">قصص المرضى</span>
				<h2 class="section-heading">ماذا يقول مرضانا</h2>
			</div>
			<div class="testimonials-row">
				<div class="testimonial reveal">
					<div class="quote-mark">"</div>
					<p>حجز أشعة الرنين من خلال صحيح كان سهلاً جداً. استلمت نتائجي رقمياً خلال ساعات دون انتظار أسابيع. طبيبي استطاع الاطلاع عليها مباشرة.</p>
					<div class="testimonial-author">
						<div class="author-avatar">👩</div>
						<div>
							<div class="author-name">ليلى منصور</div>
							<div class="author-role">مريضة · القاهرة</div>
						</div>
					</div>
				</div>
				<div class="testimonial reveal">
					<div class="quote-mark">"</div>
					<p>كمدير عيادة، صحيح غيّر طريقة إدارتنا للمواعيد. انخفضت الإلغاءات وزاد رضا المرضى بشكل ملحوظ.</p>
					<div class="testimonial-author">
						<div class="author-avatar">👨‍⚕️</div>
						<div>
							<div class="author-name">د. كريم حسن</div>
							<div class="author-role">مدير عيادة · الإسكندرية</div>
						</div>
					</div>
				</div>
				<div class="testimonial reveal">
					<div class="quote-mark">"</div>
					<p>البحث عن مختبر جيد كان متعباً. مع صحيح قارنت بين ثلاثة مختبرات من حيث التقييمات في أقل من 5 دقائق. كل شيء كان كما هو موصوف.</p>
					<div class="testimonial-author">
						<div class="author-avatar">🧑</div>
						<div>
							<div class="author-name">عمر توفيق</div>
							<div class="author-role">مريض · الجيزة</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- FINAL CTA -->
	<section class="final-cta">
		<div class="reveal">
			<h2>Ready to take control<br>of your health?</h2>
			<p>Join over 120,000 patients who manage their healthcare smarter with MediLink.</p>
			<div class="final-cta-actions">
				<a href="#" class="cta-white">Get Started Free</a>
				<a href="#providers" class="cta-outline-white">For Providers</a>
			</div>
		</div>
	</section>

@endsection

@push('scripts')
	<script>
	// Header scroll effect
	const header = document.getElementById('header');
	window.addEventListener('scroll', () => {
		header.classList.toggle('scrolled', window.scrollY > 20);
	});

	// Hamburger menu
	const hamburger = document.getElementById('hamburger');
	const mainNav = document.getElementById('main-nav');
	const headerActions = document.getElementById('header-actions');
	let menuOpen = false;

	hamburger.addEventListener('click', () => {
		menuOpen = !menuOpen;
		mainNav.classList.toggle('open', menuOpen);
		headerActions.classList.toggle('open', menuOpen);
		hamburger.style.opacity = menuOpen ? '0.7' : '1';
	});

	// Tabs
	const tabBtns = document.querySelectorAll('.tab-btn');
	const tabPanels = document.querySelectorAll('.tab-panel');

	tabBtns.forEach(btn => {
		btn.addEventListener('click', () => {
			tabBtns.forEach(b => b.classList.remove('active'));
			tabPanels.forEach(p => p.classList.remove('active'));
			btn.classList.add('active');
			document.getElementById('tab-' + btn.dataset.tab).classList
				.add('active');
		});
	});

	// Intersection Observer for reveal animations
	const reveals = document.querySelectorAll('.reveal');

	const revealObserver = new IntersectionObserver((entries) => {
		entries.forEach((entry, i) => {
			if (entry.isIntersecting) {
				// Stagger sibling reveals
				const siblings = Array.from(entry.target
					.parentElement
					.querySelectorAll('.reveal'));
				const idx = siblings.indexOf(entry.target);
				setTimeout(() => {
					entry.target
						.classList
						.add(
							'visible'
						);
				}, idx * 80);
				revealObserver.unobserve(entry.target);
			}
		});
	}, {
		threshold: 0.12,
		rootMargin: '0px 0px -40px 0px'
	});

	reveals.forEach(el => revealObserver.observe(el));

	// Counter animation for stats
	const statNums = document.querySelectorAll('.stat-num');
	const statObserver = new IntersectionObserver((entries) => {
		entries.forEach(entry => {
			if (entry.isIntersecting) {
				statObserver.unobserve(entry.target);
			}
		});
	}, {
		threshold: 0.5
	});
	statNums.forEach(el => statObserver.observe(el));
	</script>

@endpush

	



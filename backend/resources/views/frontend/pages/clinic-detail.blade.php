@extends('frontend.layouts.app')

@push('styles')
<style>
	:root {
		--ink: #1a1a2e;
		--ink-soft: #3d3d5c;
		--ink-muted: #7a7a9a;
		--surface: #f7f5f2;
		--white: #ffffff;
		--accent: #c8704a;
		--accent-soft: #f0e0d6;
		--teal: #2a7f6f;
		--teal-soft: #e0f0ec;
		--border: #e8e4de;
		--shadow: 0 4px 24px rgba(26, 26, 46, 0.08);
		--shadow-lg: 0 12px 48px rgba(26, 26, 46, 0.14);
		--r: 12px;
		--r-sm: 6px;
	}

	*,
	*::before,
	*::after {
		box-sizing: border-box;
		margin: 0;
		padding: 0;
	}

	html {
		scroll-behavior: smooth;
	}

	body {
		font-family: 'Tajawal', 'DM Sans', sans-serif;
		background: var(--surface);
		color: var(--ink);
		font-size: 15px;
		line-height: 1.65;
	}

	/* ── TOPBAR ── */
	.topbar {
		background: var(--white);
		border-bottom: 1px solid var(--border);
		padding: 0 1rem;
		height: 56px;
		display: flex;
		align-items: center;
		position: sticky;
		top: 0;
		z-index: 100;
		backdrop-filter: blur(12px);
	}
	@media (min-width: 768px) {
		.topbar { padding: 0 2rem; }
	}

	.breadcrumb {
		display: flex;
		align-items: center;
		gap: .5rem;
		font-size: 13px;
		color: var(--ink-muted);
	}

	.breadcrumb a {
		color: var(--accent);
		text-decoration: none;
		font-weight: 500;
	}

	.breadcrumb a:hover {
		text-decoration: underline;
	}

	.breadcrumb span {
		opacity: .5;
	}

	/* ── GALLERY ── */
	.gallery {
		width: 100%;
		height: 340px;
		position: relative;
		overflow: hidden;
		background: var(--ink);
	}

	.gallery-slides {
		display: flex;
		height: 100%;
		transition: transform .7s cubic-bezier(.4, 0, .2, 1);
	}

	.gallery-slide {
		flex: 0 0 100%;
		background-size: cover;
		background-position: center;
		opacity: 0;
		transition: opacity .4s ease;
	}

	.gallery-slide.active {
		opacity: 1;
	}

	.gallery-overlay {
		position: absolute;
		inset: 0;
		background: linear-gradient(to bottom, transparent 40%, rgba(26, 26, 46, .65));
		pointer-events: none;
	}

	.gallery-dots {
		position: absolute;
		bottom: 1rem;
		left: 50%;
		transform: translateX(-50%);
		display: flex;
		gap: .4rem;
	}

	.gallery-dot {
		width: 7px;
		height: 7px;
		border-radius: 50%;
		background: rgba(255, 255, 255, .45);
		border: none;
		cursor: pointer;
		transition: background .3s, transform .3s;
	}

	.gallery-dot.active {
		background: #fff;
		transform: scale(1.3);
	}

	.gallery-nav {
		position: absolute;
		top: 50%;
		transform: translateY(-50%);
		background: rgba(255, 255, 255, .18);
		border: 1px solid rgba(255, 255, 255, .3);
		backdrop-filter: blur(8px);
		color: #fff;
		width: 40px;
		height: 40px;
		border-radius: 50%;
		cursor: pointer;
		font-size: 18px;
		display: flex;
		align-items: center;
		justify-content: center;
		transition: background .2s;
	}

	.gallery-nav:hover {
		background: rgba(255, 255, 255, .32);
	}

	.gallery-nav.prev {
		left: 1rem;
	}

	.gallery-nav.next {
		right: 1rem;
	}

	/* ── HERO HEADER ── */
	.hero-header {
		background: var(--white);
		border-bottom: 1px solid var(--border);
		padding: 2rem 2rem 1.5rem;
	}

	.facility-meta {
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		gap: .75rem;
		margin-bottom: 1rem;
	}

	.badge {
		display: inline-flex;
		align-items: center;
		gap: .35rem;
		padding: .3rem .75rem;
		border-radius: 20px;
		font-size: 12px;
		font-weight: 600;
		letter-spacing: .04em;
		text-transform: uppercase;
	}

	.badge-lab {
		background: var(--teal-soft);
		color: var(--teal);
	}

	.badge-clinic {
		background: var(--accent-soft);
		color: var(--accent);
	}

	.badge-radiology {
		background: #e8eaf6;
		color: #3949ab;
	}

	.rating-row {
		display: flex;
		align-items: center;
		gap: .4rem;
	}

	.stars {
		color: #f4a823;
		font-size: 16px;
		letter-spacing: 1px;
	}

	.rating-num {
		font-weight: 700;
		color: var(--ink);
	}

	.review-count {
		color: var(--ink-muted);
		font-size: 13px;
	}

	.facility-name {
		font-family: 'Playfair Display', serif;
		font-size: clamp(1.6rem, 4vw, 2.4rem);
		font-weight: 700;
		line-height: 1.2;
		color: var(--ink);
	}

	/* ── LAYOUT ── */
	.page-content {
		max-width: 1200px;
		margin: 1rem auto;
		padding: 0 1rem;
		display: grid;
		grid-template-columns: 1fr;
		gap: 1.5rem;
		align-items: start;
	}
	@media (min-width: 768px) {
		.page-content { padding: 0 1.5rem; margin: 1.5rem auto; }
	}
	@media (min-width: 1024px) {
		.page-content { grid-template-columns: 1fr 340px; gap: 2rem; margin: 2rem auto; }
	}

	/* ── SECTIONS ── */
	.section {
		background: var(--white);
		border-radius: var(--r);
		padding: 1.75rem;
		margin-bottom: 1.5rem;
		border: 1px solid var(--border);
		opacity: 0;
		transform: translateY(20px);
		transition: opacity .5s ease, transform .5s ease;
	}

	.section.visible {
		opacity: 1;
		transform: none;
	}

	.section-title {
		font-family: 'Playfair Display', serif;
		font-size: 1.1rem;
		font-weight: 700;
		color: var(--ink);
		margin-bottom: 1.25rem;
		padding-bottom: .6rem;
		border-bottom: 2px solid var(--accent-soft);
		position: relative;
	}

	.section-title::after {
		content: '';
		position: absolute;
		bottom: -2px;
		left: 0;
		width: 40px;
		height: 2px;
		background: var(--accent);
		border-radius: 2px;
	}

	.about-text {
		color: var(--ink-soft);
		line-height: 1.8;
	}

	/* Services tags */
	.tags {
		display: flex;
		flex-wrap: wrap;
		gap: .6rem;
	}

	.tag {
		background: var(--surface);
		border: 1px solid var(--border);
		border-radius: 20px;
		padding: .35rem .9rem;
		font-size: 13px;
		font-weight: 500;
		color: var(--ink-soft);
		transition: background .2s, color .2s, border-color .2s;
		cursor: default;
	}

	.tag:hover {
		background: var(--accent-soft);
		color: var(--accent);
		border-color: var(--accent);
	}

	/* Hours table */
	.hours-table {
		width: 100%;
		border-collapse: collapse;
	}

	.hours-table tr {
		border-bottom: 1px solid var(--border);
	}

	.hours-table tr:last-child {
		border-bottom: none;
	}

	.hours-table td {
		padding: .65rem 0;
		font-size: 14px;
	}

	.hours-table td:first-child {
		color: var(--ink-soft);
		font-weight: 500;
		width: 120px;
	}

	.hours-table td:last-child {
		color: var(--ink);
		text-align: right;
	}

	.badge-open {
		background: #e6f4ea;
		color: #2e7d32;
		border-radius: 4px;
		padding: 1px 8px;
		font-size: 11px;
		font-weight: 600;
		margin-left: 6px;
	}

	.badge-closed {
		background: #fde8e8;
		color: #c62828;
		border-radius: 4px;
		padding: 1px 8px;
		font-size: 11px;
		font-weight: 600;
		margin-left: 6px;
	}

	/* Available tests */
	.tests-grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
		gap: .6rem;
	}

	.test-item {
		display: flex;
		align-items: center;
		gap: .5rem;
		background: var(--surface);
		border: 1px solid var(--border);
		border-radius: var(--r-sm);
		padding: .55rem .8rem;
		font-size: 13px;
		color: var(--ink-soft);
		font-weight: 500;
	}

	.test-icon {
		color: var(--teal);
		font-size: 14px;
	}

	/* ── SIDEBAR ── */
	.sidebar {
		position: sticky;
		top: 72px;
	}

	.sidebar-card {
		background: var(--white);
		border: 1px solid var(--border);
		border-radius: var(--r);
		padding: 1.5rem;
		margin-bottom: 1.25rem;
		box-shadow: var(--shadow);
		opacity: 0;
		transform: translateX(20px);
		transition: opacity .5s ease .2s, transform .5s ease .2s;
	}

	.sidebar-card.visible {
		opacity: 1;
		transform: none;
	}

	/* CTA */
	.cta-btn {
		display: block;
		width: 100%;
		background: var(--accent);
		color: #fff;
		border: none;
		border-radius: var(--r-sm);
		padding: .85rem 1.5rem;
		font-family: 'DM Sans', sans-serif;
		font-size: 15px;
		font-weight: 600;
		text-align: center;
		cursor: pointer;
		letter-spacing: .01em;
		transition: background .2s, transform .15s, box-shadow .2s;
		text-decoration: none;
	}

	.cta-btn:hover {
		background: #b5603a;
		transform: translateY(-1px);
		box-shadow: 0 6px 20px rgba(200, 112, 74, .35);
	}

	.cta-btn:active {
		transform: none;
	}

	.cta-secondary {
		display: block;
		width: 100%;
		background: transparent;
		color: var(--accent);
		border: 1.5px solid var(--accent);
		border-radius: var(--r-sm);
		padding: .7rem 1.5rem;
		font-family: 'DM Sans', sans-serif;
		font-size: 14px;
		font-weight: 600;
		text-align: center;
		cursor: pointer;
		margin-top: .6rem;
		transition: background .2s, color .2s;
		text-decoration: none;
	}

	.cta-secondary:hover {
		background: var(--accent-soft);
	}

	/* Contact info */
	.contact-list {
		list-style: none;
		display: flex;
		flex-direction: column;
		gap: .85rem;
	}

	.contact-item {
		display: flex;
		gap: .75rem;
		align-items: flex-start;
	}

	.contact-icon {
		width: 36px;
		height: 36px;
		flex-shrink: 0;
		background: var(--surface);
		border: 1px solid var(--border);
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 15px;
	}

	.contact-info { }

	.contact-label {
		font-size: 11px;
		color: var(--ink-muted);
		font-weight: 600;
		text-transform: uppercase;
		letter-spacing: .04em;
	}

	.contact-value {
		font-size: 14px;
		color: var(--ink);
		font-weight: 500;
	}

	.contact-value a {
		color: var(--accent);
		text-decoration: none;
	}

	.contact-value a:hover {
		text-decoration: underline;
	}

	/* Quick actions */
	.quick-actions {
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: .6rem;
		margin-top: 1rem;
	}

	.quick-btn {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: .3rem;
		background: var(--surface);
		border: 1px solid var(--border);
		border-radius: var(--r-sm);
		padding: .75rem .5rem;
		font-size: 12px;
		font-weight: 600;
		color: var(--ink-soft);
		cursor: pointer;
		transition: background .2s, color .2s, border-color .2s;
		text-decoration: none;
	}

	.quick-btn:hover {
		background: var(--accent-soft);
		color: var(--accent);
		border-color: var(--accent);
	}

	.quick-btn .icon {
		font-size: 20px;
	}

	/* ── REVIEWS ── */
	.rating-overview {
		display: flex;
		gap: 2rem;
		align-items: center;
		margin-bottom: 1.5rem;
		padding-bottom: 1.25rem;
		border-bottom: 1px solid var(--border);
	}

	.rating-big {
		font-family: 'Playfair Display', serif;
		font-size: 3rem;
		font-weight: 700;
		line-height: 1;
	}

	.rating-bars {
		flex: 1;
	}

	.rbar {
		display: flex;
		align-items: center;
		gap: .6rem;
		margin-bottom: .35rem;
		font-size: 12px;
		color: var(--ink-muted);
	}

	.rbar-track {
		flex: 1;
		height: 6px;
		background: var(--surface);
		border-radius: 3px;
		overflow: hidden;
	}

	.rbar-fill {
		height: 100%;
		background: var(--accent);
		border-radius: 3px;
		transition: width 1s ease;
	}

	.review-list {
		display: flex;
		flex-direction: column;
		gap: 1rem;
	}

	.review-card {
		background: var(--surface);
		border-radius: var(--r-sm);
		padding: 1rem 1.2rem;
		border: 1px solid var(--border);
	}

	.review-header {
		display: flex;
		justify-content: space-between;
		margin-bottom: .4rem;
	}

	.reviewer-name {
		font-weight: 600;
		font-size: 14px;
	}

	.review-date {
		font-size: 12px;
		color: var(--ink-muted);
	}

	.review-stars {
		color: #f4a823;
		font-size: 13px;
		margin-bottom: .4rem;
	}

	.review-text {
		font-size: 14px;
		color: var(--ink-soft);
		line-height: 1.7;
	}

	/* ── NEARBY ── */
	.nearby-grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
		gap: 1rem;
	}

	.nearby-card {
		background: var(--surface);
		border: 1px solid var(--border);
		border-radius: var(--r);
		overflow: hidden;
		transition: box-shadow .2s, transform .2s;
		cursor: pointer;
		text-decoration: none;
		color: inherit;
		display: block;
	}

	.nearby-card:hover {
		box-shadow: var(--shadow-lg);
		transform: translateY(-3px);
	}

	.nearby-thumb {
		height: 110px;
		background-size: cover;
		background-position: center;
	}

	.nearby-body {
		padding: .85rem;
	}

	.nearby-name {
		font-weight: 600;
		font-size: 14px;
		margin-bottom: .3rem;
	}

	.nearby-meta {
		font-size: 12px;
		color: var(--ink-muted);
		display: flex;
		gap: .5rem;
		align-items: center;
	}

	.nearby-rating {
		color: #f4a823;
	}

	/* ── RESPONSIVE ── */
	@media (max-width: 767px) {
		.sidebar {
			position: static;
		}

		.sidebar-card {
			transform: none;
			opacity: 0;
			transition: opacity .5s ease;
		}

		.sidebar-card.visible {
			opacity: 1;
		}

		.facility-meta {
			flex-direction: column;
			align-items: flex-start;
		}

		.rating-overview {
			flex-direction: column;
			align-items: flex-start;
			gap: 1rem;
		}

		.section {
			padding: 1.25rem;
		}

		.quick-actions {
			grid-template-columns: 1fr 1fr;
		}

		.form-row {
			grid-template-columns: 1fr;
		}

		.modal {
			width: 95%;
			padding: 1.5rem;
		}

		.gallery-nav {
			width: 36px;
			height: 36px;
			font-size: 16px;
		}

		.gallery-nav.prev { left: 0.5rem; }
		.gallery-nav.next { right: 0.5rem; }
	}

	/* ── SCROLL INDICATOR ── */
	.scroll-prog {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		z-index: 200;
		height: 3px;
		background: transparent;
	}

	.scroll-prog-bar {
		height: 100%;
		width: 0;
		background: linear-gradient(90deg, var(--accent), var(--teal));
		transition: width .1s linear;
	}

	/* ── MAP PLACEHOLDER ── */
	.map-placeholder {
		height: 130px;
		border-radius: var(--r-sm);
		background: #e8f0e8;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 13px;
		color: var(--teal);
		font-weight: 500;
		margin-bottom: 1rem;
		cursor: pointer;
		border: 1px solid var(--border);
		overflow: hidden;
		position: relative;
		transition: opacity .2s;
	}

	.map-placeholder:hover {
		opacity: .85;
	}

	.map-grid {
		position: absolute;
		inset: 0;
		background-image:
			linear-gradient(rgba(42, 127, 111, .08) 1px, transparent 1px),
			linear-gradient(90deg, rgba(42, 127, 111, .08) 1px, transparent 1px);
		background-size: 20px 20px;
	}

	.map-pin {
		font-size: 28px;
		position: relative;
		z-index: 1;
	}

	.map-label {
		position: relative;
		z-index: 1;
		margin-top: .4rem;
		text-align: center;
	}

	/* Modal */
	.modal-overlay {
		display: none;
		position: fixed;
		inset: 0;
		background: rgba(26, 26, 46, .5);
		backdrop-filter: blur(4px);
		z-index: 300;
		align-items: center;
		justify-content: center;
	}

	.modal-overlay.open {
		display: flex;
	}

	.modal {
		background: var(--white);
		border-radius: var(--r);
		padding: 2rem;
		max-width: 440px;
		width: 90%;
		box-shadow: var(--shadow-lg);
		animation: slideUp .3s ease;
	}

	@keyframes slideUp {
		from {
			transform: translateY(30px);
			opacity: 0;
		}

		to {
			transform: none;
			opacity: 1;
		}
	}

	.modal-title {
		font-family: 'Playfair Display', serif;
		font-size: 1.3rem;
		margin-bottom: 1.25rem;
	}

	.form-group {
		margin-bottom: 1rem;
	}

	.form-group label {
		display: block;
		font-size: 13px;
		font-weight: 600;
		color: var(--ink-muted);
		margin-bottom: .4rem;
	}

	.form-group input,
	.form-group select,
	.form-group textarea {
		width: 100%;
		border: 1.5px solid var(--border);
		border-radius: var(--r-sm);
		padding: .7rem 1rem;
		font-family: 'DM Sans', sans-serif;
		font-size: 14px;
		color: var(--ink);
		background: var(--surface);
		outline: none;
		transition: border-color .2s;
	}

	.form-group input:focus,
	.form-group select:focus {
		border-color: var(--accent);
		background: var(--white);
	}

	.form-row {
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: .75rem;
	}

	.modal-footer {
		display: flex;
		gap: .75rem;
		margin-top: 1.25rem;
	}

	.btn-ghost {
		flex: 1;
		background: none;
		border: 1.5px solid var(--border);
		border-radius: var(--r-sm);
		padding: .75rem;
		font-size: 14px;
		font-weight: 600;
		cursor: pointer;
		color: var(--ink-muted);
		transition: background .2s;
	}

	.btn-ghost:hover {
		background: var(--surface);
	}

	.btn-primary-modal {
		flex: 2;
		background: var(--accent);
		color: #fff;
		border: none;
		border-radius: var(--r-sm);
		padding: .75rem;
		font-size: 14px;
		font-weight: 600;
		cursor: pointer;
		transition: background .2s;
	}

	.btn-primary-modal:hover {
		background: #b5603a;
	}
	</style>
@endpush

@section('content')
	<!-- Scroll Progress -->
	<div class="scroll-prog">
		<div class="scroll-prog-bar" id="scrollBar"></div>
	</div>


	<!-- Gallery -->
	@php $logoUrl = $clinic->logo ? asset('storage/' . $clinic->logo) : null; @endphp
	<div class="gallery" id="gallery">
		<div class="gallery-slides" id="gallerySlides">
			@if($logoUrl)
			<div class="gallery-slide active" style="background-image:url({{ json_encode($logoUrl) }});background-size:contain;background-repeat:no-repeat;background-color:#1a1a2e;"></div>
			@else
			<div class="gallery-slide active" style="background:linear-gradient(135deg,#2d2a9c 0%,#1e40af 100%);display:flex;align-items:center;justify-content:center;font-size:4rem;">🏥</div>
			@endif
		</div>
		<div class="gallery-overlay"></div>
		<div class="gallery-dots" id="galDots">
			<button class="gallery-dot active" aria-label="الشريحة 1"></button>
		</div>
	</div>

	<!-- Hero Header -->
	<div class="hero-header">
		<div style="max-width:1200px; margin:0 auto;">
			<div class="facility-meta">
				<span class="badge badge-clinic">🏥 عيادة</span>
				@if($clinic->specialty)
				<span class="badge badge-lab">{{ $clinic->specialty->name_ar ?? $clinic->specialty->name_en }}</span>
				@endif
				<div class="rating-row">
					@php $avg = round((float)($clinic->reviews_avg_rating ?? 0), 1); $stars = min(5, max(0, (int)round($avg))); @endphp
					<span class="stars" aria-hidden="true">{{ str_repeat('★', $stars) }}{{ $stars < 5 ? str_repeat('☆', 5 - $stars) : '' }}</span>
					<span class="rating-num">{{ $avg }}</span>
					<span class="review-count">({{ $clinic->reviews_count ?? 0 }} تقييم)</span>
				</div>
			</div>
			<h1 class="facility-name">{{ $clinic->name }}</h1>
		</div>
	</div>

	<!-- Main Layout -->
	<div class="page-content">

		<!-- MAIN COLUMN -->
		<main>

			<!-- About -->
			<div class="section" data-animate>
				<h2 class="section-title">عن العيادة</h2>
				@if($clinic->description)
				<p class="about-text">{{ $clinic->description }}</p>
				@else
				<p class="about-text">عيادة متكاملة تقدم خدمات الرعاية الصحية. للاستفسار عن الخدمات والأوقات يرجى التواصل عبر البيانات الظاهرة.</p>
				@endif
			</div>

			<!-- Services / Specialty & Doctors -->
			<div class="section" data-animate>
				<h2 class="section-title">التخصص والخدمات</h2>
				<div class="tags">
					@if($clinic->specialty)
					<span class="tag">{{ $clinic->specialty->name_ar ?? $clinic->specialty->name_en }}</span>
					@endif
					@foreach($clinic->doctors as $doctor)
						@if($doctor->specialty)
						<span class="tag">{{ $doctor->specialty->name_ar ?? $doctor->specialty->name_en }}</span>
						@endif
					@endforeach
				</div>
				@if($clinic->doctors->isEmpty() && !$clinic->specialty)
				<p class="about-text" style="color:var(--ink-muted);">—</p>
				@endif
			</div>

			<!-- Doctors -->
			@if($clinic->doctors->isNotEmpty())
			<div class="section" data-animate>
				<h2 class="section-title">الأطباء</h2>
				<div class="tests-grid">
					@foreach($clinic->doctors as $doctor)
					<a href="{{ route('doctor.detail', $doctor->id) }}" class="test-item" style="text-decoration:none;color:inherit;">
						<span class="test-icon">👩‍⚕️</span>
						{{ optional($doctor->user)->name ?? 'طبيب' }}
						@if($doctor->specialty)
						<span style="font-size:11px;color:var(--ink-muted);">({{ $doctor->specialty->name_ar ?? $doctor->specialty->name_en }})</span>
						@endif
					</a>
					@endforeach
				</div>
			</div>
			@endif

			<!-- Working Hours -->
			<div class="section" data-animate>
				<h2 class="section-title">أوقات العمل</h2>
				<p class="about-text" style="color:var(--ink-muted);">اتصل بالعيادة للاستفسار عن أوقات العمل.</p>
			</div>

			<!-- Reviews -->
			<div class="section" data-animate>
				<h2 class="section-title">تقييمات المرضى</h2>
				@php
					$reviews = $clinic->reviews;
					$totalReviews = $reviews->count();
					$avgRating = $totalReviews > 0 ? round($reviews->avg('rating'), 1) : 0;
					$ratingCounts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
					foreach ($reviews as $r) {
						$star = min(5, max(1, (int) $r->rating));
						$ratingCounts[$star] = ($ratingCounts[$star] ?? 0) + 1;
					}
				@endphp
				<div class="rating-overview">
					<div>
						<div class="rating-big">{{ $avgRating }}</div>
						<div class="stars" style="font-size:18px; margin-top:.3rem;">{{ str_repeat('★', min(5, (int)round($avgRating))) }}{{ str_repeat('☆', 5 - min(5, (int)round($avgRating))) }}</div>
						<div style="font-size:12px; color:var(--ink-muted); margin-top:.2rem;">{{ $totalReviews }} تقييم</div>
					</div>
					@if($totalReviews > 0)
					<div class="rating-bars">
						@foreach([5,4,3,2,1] as $star)
						@php $pct = $totalReviews > 0 ? round(($ratingCounts[$star] ?? 0) / $totalReviews * 100) : 0; @endphp
						<div class="rbar"><span>{{ $star }}★</span>
							<div class="rbar-track">
								<div class="rbar-fill" data-width="{{ $pct }}%" style="width:0"></div>
							</div><span>{{ $pct }}%</span>
						</div>
						@endforeach
					</div>
					@endif
				</div>

				<div class="review-list">
					@forelse($reviews->take(10) as $review)
					<div class="review-card">
						<div class="review-header">
							<span class="reviewer-name">{{ $review->patient?->name ?? 'مريض' }}</span>
							<span class="review-date">{{ $review->created_at?->translatedFormat('d M Y') ?? '—' }}</span>
						</div>
						<div class="review-stars">{{ str_repeat('★', min(5, (int)$review->rating)) }}{{ str_repeat('☆', 5 - min(5, (int)$review->rating)) }}</div>
						@if($review->comment)
						<p class="review-text">{{ $review->comment }}</p>
						@endif
					</div>
					@empty
					<p class="about-text" style="color:var(--ink-muted);">لا توجد تقييمات بعد.</p>
					@endforelse
				</div>
			</div>

			<!-- Nearby -->
			@if(isset($nearbyClinics) && $nearbyClinics->isNotEmpty())
			<div class="section" data-animate>
				<h2 class="section-title">عيادات أخرى قريبة</h2>
				<div class="nearby-grid">
					@foreach($nearbyClinics as $near)
					<a class="nearby-card" href="{{ route('clinic.detail', $near->id) }}">
						<div class="nearby-thumb" style="background: linear-gradient(135deg, #e8f6f1 0%, #e0f0ec 100%); display:flex; align-items:center; justify-content:center; font-size:2.5rem;">🏥</div>
						<div class="nearby-body">
							<div class="nearby-name">{{ $near->name }}</div>
							<div class="nearby-meta">
								<span class="nearby-rating">★ {{ number_format($near->reviews_avg_rating ?? 0, 1) }}</span>
								@if($near->governorate)
								<span>• {{ $near->governorate->name }}</span>
								@endif
							</div>
						</div>
					</a>
					@endforeach
				</div>
			</div>
			@endif

		</main>

		<!-- SIDEBAR -->
		<aside>
			<div class="sidebar sidebar-inner">

				<!-- CTA Card -->
				<div class="sidebar-card" data-animate-side>
					<button class="cta-btn" type="button" onclick="openModal()">📅 حجز موعد</button>
					@if($clinic->phone)
					<a href="tel:{{ preg_replace('/\s+/', '', $clinic->phone) }}" class="cta-secondary">📞 اتصل الآن</a>
					@endif
				</div>

				<!-- Contact Card -->
				@php
					$mapUrl = 'https://www.google.com/maps/search/?api=1&query=';
					if ($clinic->latitude && $clinic->longitude) {
						$mapUrl .= urlencode($clinic->latitude . ',' . $clinic->longitude);
					} elseif ($clinic->address) {
						$mapUrl .= urlencode($clinic->address);
					} else {
						$mapUrl .= urlencode(trim(($clinic->governorate?->name ?? '') . ' ' . ($clinic->city?->name ?? '') . ' ' . ($clinic->area?->name ?? '')));
					}
				@endphp
				<div class="sidebar-card" data-animate-side>
					<div class="map-placeholder" onclick="window.open('{{ $mapUrl }}','_blank')" role="button" tabindex="0">
						<div class="map-grid"></div>
						<div style="position:relative;z-index:1;text-align:center;">
							<div class="map-pin">📍</div>
							<div class="map-label">عرض على خرائط جوجل</div>
						</div>
					</div>
					<ul class="contact-list">
						@if($clinic->address || $clinic->area || $clinic->city)
						<li class="contact-item">
							<div class="contact-icon">📍</div>
							<div class="contact-info">
								<div class="contact-label">العنوان</div>
								<div class="contact-value">{{ $clinic->address ?: trim(($clinic->area?->name ?? '') . '، ' . ($clinic->city?->name ?? '') . '، ' . ($clinic->governorate?->name ?? '')) ?: '—' }}</div>
							</div>
						</li>
						@endif
						@if($clinic->phone)
						<li class="contact-item">
							<div class="contact-icon">📞</div>
							<div class="contact-info">
								<div class="contact-label">الهاتف</div>
								<div class="contact-value"><a href="tel:{{ preg_replace('/\s+/', '', $clinic->phone) }}">{{ $clinic->phone }}</a></div>
							</div>
						</li>
						@endif
						@if($clinic->website)
						<li class="contact-item">
							<div class="contact-icon">🌐</div>
							<div class="contact-info">
								<div class="contact-label">الموقع</div>
								<div class="contact-value"><a href="{{ $clinic->website }}" target="_blank" rel="noopener">{{ Str::limit($clinic->website, 30) }}</a></div>
							</div>
						</li>
						@endif
						@if($clinic->email)
						<li class="contact-item">
							<div class="contact-icon">✉️</div>
							<div class="contact-info">
								<div class="contact-label">البريد</div>
								<div class="contact-value"><a href="mailto:{{ $clinic->email }}">{{ $clinic->email }}</a></div>
							</div>
						</li>
						@endif
					</ul>
					<div class="quick-actions">
						@if($clinic->phone)
						<a href="tel:{{ preg_replace('/\s+/', '', $clinic->phone) }}" class="quick-btn"><span class="icon">📞</span> اتصال</a>
						<a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $clinic->phone) }}" target="_blank" rel="noopener" class="quick-btn"><span class="icon">💬</span> واتساب</a>
						@endif
						<a href="{{ $mapUrl }}" target="_blank" rel="noopener" class="quick-btn"><span class="icon">🗺️</span> خرائط</a>
						@if($clinic->email)
						<a href="mailto:{{ $clinic->email }}" class="quick-btn"><span class="icon">✉️</span> بريد</a>
						@endif
					</div>
				</div>

			</div>
		</aside>

	</div>

	<!-- Booking Modal -->
	<div class="modal-overlay" id="modalOverlay" onclick="closeModalOutside(event)">
		<div class="modal">
			<h2 class="modal-title">حجز موعد</h2>
			<div class="form-group">
				<label>الاسم الكامل</label>
				<input type="text" placeholder="الاسم الكامل">
			</div>
			<div class="form-row">
				<div class="form-group">
					<label>رقم الهاتف</label>
					<input type="tel" placeholder="05xxxxxxxx">
				</div>
				<div class="form-group">
					<label>البريد الإلكتروني</label>
					<input type="email" placeholder="example@email.com">
				</div>
			</div>
			<div class="form-row">
				<div class="form-group">
					<label>التاريخ المفضل</label>
					<input type="date">
				</div>
				<div class="form-group">
					<label>الوقت المفضل</label>
					<select>
						<option>صباحاً (8–12)</option>
						<option>ظهراً (12–5)</option>
						<option>مساءً (5–9)</option>
					</select>
				</div>
			</div>
			<div class="form-group">
				<label>ملاحظات (اختياري)</label>
				<textarea placeholder="أي متطلبات أو أسئلة..."
					style="height:70px;resize:vertical;"></textarea>
			</div>
			<div class="modal-footer">
				<button class="btn-ghost" type="button" onclick="closeModal()">إلغاء</button>
				<button class="btn-primary-modal" type="button" onclick="submitBooking()">تأكيد الحجز</button>
			</div>
		</div>
	</div>

	

@endsection


@push('scripts')

<script>
	// ── Gallery (optional carousel if multiple slides) ──
	const slides = document.querySelectorAll('.gallery-slide');
	const dots = document.querySelectorAll('.gallery-dot');
	if (slides.length > 1 && dots.length > 1) {
		let current = 0;
		function showSlide(n) {
			slides[current].classList.remove('active');
			dots[current].classList.remove('active');
			current = (n + slides.length) % slides.length;
			slides[current].classList.add('active');
			dots[current].classList.add('active');
		}
		const galPrev = document.getElementById('galPrev');
		const galNext = document.getElementById('galNext');
		if (galPrev) galPrev.onclick = () => showSlide(current - 1);
		if (galNext) galNext.onclick = () => showSlide(current + 1);
		dots.forEach((d, i) => { d.onclick = () => showSlide(i); });
		setInterval(() => showSlide(current + 1), 4500);
	}

	// ── Scroll Progress ──
	window.addEventListener('scroll', () => {
		const pct = (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100;
		document.getElementById('scrollBar').style.width = pct + '%';
	});

	// ── Intersection Observer for animations ──
	const io = new IntersectionObserver((entries) => {
		entries.forEach(e => {
			if (e.isIntersecting) {
				e.target.classList.add('visible');
				// Animate rating bars when reviews section becomes visible
				e.target.querySelectorAll('.rbar-fill').forEach(
					b => {
						b.style.width = b
							.dataset
							.width;
					});
				io.unobserve(e.target);
			}
		});
	}, {
		threshold: 0.1,
		rootMargin: '0px 0px -40px 0px'
	});

	document.querySelectorAll('[data-animate], [data-animate-side]').forEach(el => io.observe(el));

	// Also observe sidebar cards
	document.querySelectorAll('.sidebar-card').forEach(el => io.observe(el));

	// ── Modal ──
	function openModal() {
		document.getElementById('modalOverlay').classList.add('open');
		document.body.style.overflow = 'hidden';
	}

	function closeModal() {
		document.getElementById('modalOverlay').classList.remove('open');
		document.body.style.overflow = '';
	}

	function closeModalOutside(e) {
		if (e.target === document.getElementById('modalOverlay')) closeModal();
	}

	function submitBooking() {
		closeModal();
		// Show simple success feedback
		const toast = document.createElement('div');
		toast.textContent = '✅ تم إرسال طلب الحجز! سنتصل بك قريباً.';
		Object.assign(toast.style, {
			position: 'fixed',
			bottom: '1.5rem',
			left: '50%',
			transform: 'translateX(-50%)',
			background: 'var(--ink)',
			color: '#fff',
			padding: '.85rem 1.5rem',
			borderRadius: '30px',
			fontSize: '14px',
			fontWeight: '500',
			zIndex: '400',
			animation: 'slideUp .3s ease',
			boxShadow: '0 8px 24px rgba(0,0,0,.3)'
		});
		document.body.appendChild(toast);
		setTimeout(() => toast.remove(), 4000);
	}
	document.addEventListener('keydown', e => {
		if (e.key === 'Escape') closeModal();
	});
	</script>
@endpush
@extends('frontend.layouts.app')

@push('styles')
<style>
	:root {
		--bg: #f8f9ff;
		--surface: #ffffff;
		--surface2: #f1f3fb;
		--border: #e5e7eb;
		--text: #1f2937;
		--text-muted: #6b7280;
		--text-subtle: #9ca3af;
		--accent: #4338ca;
		--accent-dark: #3730a3;
		--accent-light: rgba(67, 56, 202, 0.08);
		--secondary: #2563eb;
		--secondary-light: rgba(37, 99, 235, 0.08);
		--clinic: #0a7c5c;
		--clinic-bg: #e8f6f1;
		--lab: #2563eb;
		--lab-bg: rgba(37, 99, 235, 0.08);
		--radiology: #4338ca;
		--radiology-bg: rgba(67, 56, 202, 0.08);
		--shadow: 0 2px 16px rgba(67, 56, 202, 0.07);
		--shadow-hover: 0 12px 40px rgba(67, 56, 202, 0.16);
		--radius: 16px;
		--radius-sm: 10px;
	}

	*,
	*::before,
	*::after {
		box-sizing: border-box;
		margin: 0;
		padding: 0;
	}

	body {
		font-family: 'Tajawal', 'Sora', sans-serif;
		background: var(--bg);
		color: var(--text);
		min-height: 100vh;
		direction: rtl;
	}

	/* ── HEADER ── */
	header {
		background: var(--surface);
		border-bottom: 1px solid var(--border);
		position: sticky;
		top: 0;
		z-index: 100;
		backdrop-filter: blur(8px);
	}

	.header-inner {
		max-width: 1280px;
		margin: 0 auto;
		padding: 0 16px;
		height: 64px;
		display: flex;
		align-items: center;
		gap: 16px;
	}
	@media (min-width: 768px) {
		.header-inner { padding: 0 24px; }
	}

	.logo {
		font-family: 'DM Serif Display', serif;
		font-size: 1.5rem;
		color: var(--accent);
		text-decoration: none;
		flex-shrink: 0;
	}

	.logo span {
		color: var(--text);
	}

	/* ── HERO ── */
	.hero {
		background: linear-gradient(135deg, #1e1b6e 0%, #2d2a9c 50%, #1e40af 100%);
		padding: 60px 24px 48px;
		text-align: center;
		position: relative;
		overflow: hidden;
	}

	.hero::before {
		content: '';
		position: absolute;
		inset: 0;
		background: radial-gradient(ellipse 60% 50% at 70% 100%, rgba(37, 99, 235, 0.35) 0%, transparent 70%);
	}

	.hero-content {
		position: relative;
		z-index: 1;
		max-width: 640px;
		margin: 0 auto;
	}

	.hero h1 {
		font-family: 'DM Serif Display', serif;
		font-size: clamp(2rem, 5vw, 3.2rem);
		color: #fff;
		line-height: 1.15;
		margin-bottom: 12px;
	}

	.hero h1 em {
		color: #a5b4fc;
		font-style: italic;
	}

	.hero p {
		color: rgba(255, 255, 255, 0.65);
		font-size: 1rem;
		font-weight: 300;
		letter-spacing: 0.02em;
	}

	/* ── SEARCH BAR ── */
	.search-wrap {
		max-width: 600px;
		margin: 28px auto 0;
		position: relative;
	}

	.search-wrap input {
		width: 100%;
		padding: 16px 56px 16px 20px;
		border-radius: 50px;
		border: none;
		font-family: 'Sora', sans-serif;
		font-size: 0.95rem;
		background: #fff;
		color: var(--text);
		outline: none;
		box-shadow: 0 4px 24px rgba(0, 0, 0, 0.25);
	}

	.search-wrap button.search-btn {
		position: absolute;
		right: 8px;
		top: 50%;
		transform: translateY(-50%);
		width: 40px;
		height: 40px;
		border-radius: 50%;
		border: none;
		background: var(--accent);
		color: #fff;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
		transition: background 0.2s;
	}

	.search-wrap button.search-btn:hover {
		background: var(--accent-dark);
	}

	/* ── LAYOUT ── */
	.layout {
		max-width: 1280px;
		margin: 0 auto;
		padding: 24px 16px;
		display: grid;
		grid-template-columns: 280px 1fr;
		gap: 24px;
		align-items: start;
	}
	@media (min-width: 768px) {
		.layout { padding: 32px 20px; gap: 32px; }
	}
	@media (min-width: 1024px) {
		.layout { padding: 40px 24px; }
	}

	/* ── SIDEBAR ── */
	.sidebar {
		background: var(--surface);
		border-radius: var(--radius);
		border: 1px solid var(--border);
		padding: 28px 24px;
		position: sticky;
		top: 80px;
	}

	.sidebar-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin-bottom: 24px;
	}

	.sidebar-title {
		font-size: 0.8rem;
		font-weight: 600;
		letter-spacing: 0.1em;
		text-transform: uppercase;
		color: var(--text-muted);
	}

	.clear-btn {
		font-size: 0.78rem;
		color: var(--secondary);
		background: none;
		border: none;
		cursor: pointer;
		font-family: 'Sora', sans-serif;
		font-weight: 500;
		padding: 4px 8px;
		border-radius: 6px;
		transition: background 0.15s;
	}

	.clear-btn:hover {
		background: var(--secondary-light);
	}

	.filter-group {
		margin-bottom: 24px;
	}

	.filter-label {
		font-size: 0.8rem;
		font-weight: 600;
		color: var(--text-muted);
		margin-bottom: 10px;
		display: block;
		letter-spacing: 0.06em;
		text-transform: uppercase;
	}

	/* Chips */
	.chips {
		display: flex;
		flex-wrap: wrap;
		gap: 8px;
	}

	.chip {
		padding: 7px 14px;
		border-radius: 50px;
		border: 1.5px solid var(--border);
		background: var(--surface);
		font-family: 'Sora', sans-serif;
		font-size: 0.82rem;
		font-weight: 500;
		cursor: pointer;
		transition: all 0.18s;
		color: var(--text-muted);
	}

	.chip:hover {
		border-color: var(--accent);
		color: var(--accent);
	}

	.chip.active {
		background: var(--accent);
		border-color: var(--accent);
		color: #fff;
	}

	.chip.clinic.active {
		background: var(--clinic);
		border-color: var(--clinic);
	}

	.chip.lab.active {
		background: var(--secondary);
		border-color: var(--secondary);
	}

	.chip.radiology.active {
		background: var(--accent);
		border-color: var(--accent);
	}

	/* Select */
	.filter-select {
		width: 100%;
		padding: 10px 14px;
		border-radius: var(--radius-sm);
		border: 1.5px solid var(--border);
		background: var(--surface);
		font-family: 'Sora', sans-serif;
		font-size: 0.87rem;
		color: var(--text);
		cursor: pointer;
		outline: none;
		appearance: none;
		background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%237a7168' stroke-width='2'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
		background-repeat: no-repeat;
		background-position: right 12px center;
		padding-right: 36px;
		transition: border-color 0.18s;
	}

	.filter-select:focus {
		border-color: var(--accent);
	}
	[dir="rtl"] .filter-select {
		background-position: left 12px center;
		padding-left: 36px;
		padding-right: 14px;
	}

	/* Stars rating filter */
	.star-filter {
		display: flex;
		gap: 6px;
		align-items: center;
	}

	.star-opt {
		display: flex;
		align-items: center;
		gap: 4px;
		font-size: 0.82rem;
		color: var(--text-muted);
		cursor: pointer;
		padding: 6px 10px;
		border-radius: 8px;
		border: 1.5px solid var(--border);
		transition: all 0.15s;
		white-space: nowrap;
	}

	.star-opt:hover {
		border-color: var(--secondary);
		color: var(--text);
	}

	.star-opt.active {
		background: var(--secondary-light);
		border-color: var(--secondary);
		color: var(--text);
	}

	.star-icon {
		color: var(--secondary);
	}

	/* Toggle */
	.toggle-row {
		display: flex;
		align-items: center;
		gap: 12px;
	}

	.toggle {
		width: 40px;
		height: 22px;
		background: var(--border);
		border-radius: 50px;
		position: relative;
		cursor: pointer;
		transition: background 0.2s;
		flex-shrink: 0;
		border: none;
	}

	.toggle::after {
		content: '';
		position: absolute;
		left: 3px;
		top: 3px;
		width: 16px;
		height: 16px;
		border-radius: 50%;
		background: #fff;
		transition: transform 0.2s;
		box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
	}

	.toggle.on {
		background: var(--accent);
	}

	.toggle.on::after {
		transform: translateX(18px);
	}

	.toggle-label {
		font-size: 0.85rem;
		color: var(--text);
	}

	/* ── MAIN CONTENT ── */
	.main {}

	.results-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin-bottom: 24px;
		flex-wrap: wrap;
		gap: 12px;
	}

	.results-count {
		font-size: 0.9rem;
		color: var(--text-muted);
	}

	.results-count strong {
		color: var(--text);
		font-weight: 600;
	}

	.view-toggle {
		display: flex;
		gap: 4px;
		background: var(--surface);
		border: 1px solid var(--border);
		border-radius: var(--radius-sm);
		padding: 4px;
	}

	.view-btn {
		width: 34px;
		height: 34px;
		border-radius: 7px;
		border: none;
		background: transparent;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
		color: var(--text-muted);
		transition: all 0.15s;
	}

	.view-btn.active {
		background: var(--accent);
		color: #fff;
	}

	.view-btn:hover:not(.active) {
		background: var(--surface2);
	}

	/* ── GRID ── */
	.cards-grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
		gap: 20px;
	}

	.cards-grid.list-view {
		grid-template-columns: 1fr;
	}

	/* ── CARD ── */
	.card {
		background: var(--surface);
		border-radius: var(--radius);
		border: 1px solid var(--border);
		overflow: hidden;
		display: flex;
		flex-direction: column;
		transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.25s;
		cursor: pointer;
		opacity: 0;
		transform: translateY(24px);
		animation: fadeUp 0.45s forwards;
	}

	.card:hover {
		transform: translateY(-6px) scale(1.01);
		box-shadow: var(--shadow-hover);
	}

	@keyframes fadeUp {
		to {
			opacity: 1;
			transform: translateY(0);
		}
	}

	.card-image {
		width: 100%;
		height: 160px;
		background: var(--surface2);
		position: relative;
		overflow: hidden;
		flex-shrink: 0;
	}

	.card-image img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	.card-image-placeholder {
		width: 100%;
		height: 100%;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		gap: 8px;
		font-size: 2.4rem;
	}

	.card-badge {
		position: absolute;
		top: 12px;
		left: 12px;
		padding: 4px 10px;
		border-radius: 50px;
		font-size: 0.72rem;
		font-weight: 600;
		letter-spacing: 0.06em;
		text-transform: uppercase;
	}

	.badge-clinic {
		background: var(--clinic-bg);
		color: var(--clinic);
	}

	.badge-lab {
		background: var(--lab-bg);
		color: var(--secondary);
	}

	.badge-radiology {
		background: var(--radiology-bg);
		color: var(--accent);
	}

	.open-tag {
		position: absolute;
		top: 12px;
		right: 12px;
		background: rgba(10, 124, 92, 0.9);
		color: #fff;
		padding: 3px 8px;
		border-radius: 50px;
		font-size: 0.7rem;
		font-weight: 600;
		backdrop-filter: blur(4px);
	}

	.closed-tag {
		position: absolute;
		top: 12px;
		right: 12px;
		background: rgba(180, 50, 30, 0.85);
		color: #fff;
		padding: 3px 8px;
		border-radius: 50px;
		font-size: 0.7rem;
		font-weight: 600;
	}

	.card-body {
		padding: 20px;
		flex: 1;
		display: flex;
		flex-direction: column;
		gap: 8px;
	}

	.card-name {
		font-size: 1rem;
		font-weight: 600;
		color: var(--text);
		line-height: 1.3;
	}

	.card-address {
		font-size: 0.82rem;
		color: var(--text-muted);
		display: flex;
		align-items: flex-start;
		gap: 4px;
	}

	.card-address svg {
		flex-shrink: 0;
		margin-top: 1px;
	}

	.card-rating {
		display: flex;
		align-items: center;
		gap: 6px;
		font-size: 0.85rem;
	}

	.stars {
		color: var(--secondary);
		letter-spacing: -1px;
	}

	.rating-num {
		font-weight: 600;
		color: var(--text);
	}

	.rating-count {
		color: var(--text-muted);
		font-size: 0.78rem;
	}

	.card-tags {
		display: flex;
		flex-wrap: wrap;
		gap: 6px;
		margin-top: 4px;
	}

	.tag {
		padding: 3px 9px;
		background: var(--surface2);
		border-radius: 50px;
		font-size: 0.75rem;
		color: var(--text-muted);
		font-weight: 500;
	}

	.card-footer {
		padding: 16px 20px;
		border-top: 1px solid var(--border);
	}

	.view-btn-card {
		width: 100%;
		padding: 10px;
		background: var(--text);
		color: #fff;
		border: none;
		border-radius: var(--radius-sm);
		font-family: 'Sora', sans-serif;
		font-size: 0.85rem;
		font-weight: 600;
		cursor: pointer;
		transition: background 0.18s, transform 0.15s;
		letter-spacing: 0.02em;
	}

	.view-btn-card:hover {
		background: var(--accent);
		transform: scale(1.01);
	}

	/* list view card */
	.cards-grid.list-view .card {
		flex-direction: row;
	}

	.cards-grid.list-view .card-image {
		width: 180px;
		height: auto;
		flex-shrink: 0;
		border-radius: 0;
	}

	.cards-grid.list-view .card-footer {
		border-top: none;
		border-left: 1px solid var(--border);
		display: flex;
		align-items: center;
		padding: 20px;
		min-width: 140px;
	}

	/* ── EMPTY STATE ── */
	.empty-state {
		grid-column: 1 / -1;
		text-align: center;
		padding: 80px 24px;
		display: none;
	}

	.empty-state.visible {
		display: block;
	}

	.empty-icon {
		font-size: 4rem;
		margin-bottom: 16px;
		display: block;
	}

	.empty-state h3 {
		font-family: 'DM Serif Display', serif;
		font-size: 1.5rem;
		margin-bottom: 8px;
	}

	.empty-state p {
		color: var(--text-muted);
		font-size: 0.9rem;
		max-width: 320px;
		margin: 0 auto 20px;
	}

	.reset-btn {
		padding: 10px 24px;
		background: var(--accent);
		color: #fff;
		border: none;
		border-radius: 50px;
		font-family: 'Sora', sans-serif;
		font-size: 0.9rem;
		font-weight: 600;
		cursor: pointer;
		transition: background 0.18s;
	}

	.reset-btn:hover {
		background: var(--accent-dark);
	}

	/* ── PAGINATION ── */
	.pagination {
		margin-top: 40px;
		display: flex;
		justify-content: center;
		gap: 8px;
		align-items: center;
	}

	.page-btn {
		width: 40px;
		height: 40px;
		border-radius: var(--radius-sm);
		border: 1.5px solid var(--border);
		background: var(--surface);
		font-family: 'Sora', sans-serif;
		font-size: 0.85rem;
		font-weight: 500;
		cursor: pointer;
		transition: all 0.15s;
		color: var(--text-muted);
	}

	.page-btn:hover {
		border-color: var(--accent);
		color: var(--accent);
	}

	.page-btn.active {
		background: var(--accent);
		border-color: var(--accent);
		color: #fff;
		font-weight: 600;
	}

	.load-more-btn {
		display: block;
		margin: 40px auto 0;
		padding: 14px 40px;
		background: transparent;
		border: 2px solid var(--text);
		border-radius: 50px;
		font-family: 'Sora', sans-serif;
		font-size: 0.9rem;
		font-weight: 600;
		cursor: pointer;
		transition: all 0.2s;
		color: var(--text);
	}

	.load-more-btn:hover {
		background: var(--text);
		color: #fff;
	}

	/* ── MOBILE FILTER DRAWER ── */
	.mobile-filter-bar {
		display: none;
		padding: 12px 16px;
		background: var(--surface);
		border-bottom: 1px solid var(--border);
		gap: 8px;
		overflow-x: auto;
		scrollbar-width: none;
	}

	.mobile-filter-bar::-webkit-scrollbar {
		display: none;
	}

	.filter-drawer-btn {
		padding: 8px 16px;
		border-radius: 50px;
		border: 1.5px solid var(--border);
		background: var(--surface);
		font-family: 'Sora', sans-serif;
		font-size: 0.82rem;
		font-weight: 500;
		cursor: pointer;
		white-space: nowrap;
		color: var(--text);
		display: flex;
		align-items: center;
		gap: 6px;
		flex-shrink: 0;
		transition: all 0.15s;
	}

	.filter-drawer-btn:hover {
		border-color: var(--accent);
		color: var(--accent);
	}

	.filter-drawer-btn.active {
		background: var(--accent);
		border-color: var(--accent);
		color: #fff;
	}

	.drawer-overlay {
		position: fixed;
		inset: 0;
		background: rgba(0, 0, 0, 0.4);
		z-index: 200;
		display: none;
		opacity: 0;
		transition: opacity 0.25s;
	}

	.drawer-overlay.open {
		display: block;
		opacity: 1;
	}

	.filter-drawer {
		position: fixed;
		bottom: 0;
		left: 0;
		right: 0;
		background: var(--surface);
		border-radius: 24px 24px 0 0;
		z-index: 201;
		padding: 24px;
		transform: translateY(100%);
		transition: transform 0.3s cubic-bezier(0.34, 1.26, 0.64, 1);
		max-height: 85vh;
		overflow-y: auto;
	}

	.filter-drawer.open {
		transform: translateY(0);
	}

	.drawer-handle {
		width: 40px;
		height: 4px;
		background: var(--border);
		border-radius: 2px;
		margin: 0 auto 20px;
	}

	/* ── RESPONSIVE ── */
	@media (max-width: 900px) {
		.layout {
			grid-template-columns: 1fr;
			padding: 20px 16px;
		}

		.sidebar {
			display: none;
		}

		.mobile-filter-bar {
			display: flex;
		}

		.cards-grid {
			grid-template-columns: 1fr;
		}

		.cards-grid.list-view .card {
			flex-direction: column;
		}

		.cards-grid.list-view .card-image {
			width: 100%;
			height: 160px;
		}

		.cards-grid.list-view .card-footer {
			border-left: none;
			border-top: 1px solid var(--border);
			min-width: unset;
		}

		.results-header {
			flex-wrap: wrap;
			gap: 12px;
		}

		.results-header .filter-select {
			min-width: 140px;
		}
	}

	@media (max-width: 600px) {
		.hero {
			padding: 32px 12px 28px;
		}

		.hero h1 {
			font-size: clamp(1.5rem, 6vw, 2.2rem);
		}

		.search-wrap {
			margin-top: 20px;
			padding: 0 8px;
		}

		.search-wrap input {
			padding: 14px 52px 14px 16px;
			font-size: 0.9rem;
		}

		.layout {
			padding: 16px 12px;
		}

		.cards-grid {
			grid-template-columns: 1fr;
			gap: 16px;
		}

		.card-body {
			padding: 16px;
		}

		.card-footer {
			padding: 12px 16px;
		}

		.results-header {
			flex-direction: column;
			align-items: flex-start;
		}

		.results-header > div {
			width: 100%;
		}

		.results-header .filter-select {
			width: 100%;
			max-width: 100%;
		}

		.filter-drawer {
			right: 0;
			left: 0;
			border-radius: 24px 24px 0 0;
		}
	}

	@media (min-width: 601px) and (max-width: 900px) {
		.cards-grid {
			grid-template-columns: repeat(2, 1fr);
		}
	}
	</style>

@endpush

@section('content')

	<!-- HERO -->
	<section class="hero">
		<div class="hero-content">
			<h1>ابحث عن <em>عيادة</em><br>قريبة منك</h1>
			<p>تصفّح العيادات المعتمدة والموثوقة</p>
			<div class="search-wrap">
				<input type="text" id="searchInput"
					placeholder="ابحث بالاسم أو المنطقة أو التخصص…">
				<button class="search-btn" onclick="applyFilters()" type="button" aria-label="بحث">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="2.5">
						<circle cx="11" cy="11" r="8" />
						<path d="m21 21-4.35-4.35" />
					</svg>
				</button>
			</div>
		</div>
	</section>

	<!-- MOBILE FILTER BAR -->
	<div class="mobile-filter-bar" id="mobileFilterBar">
		<button class="filter-drawer-btn" onclick="openDrawer()" type="button">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
				stroke-width="2">
				<line x1="8" y1="6" x2="21" y2="6" />
				<line x1="8" y1="12" x2="21" y2="12" />
				<line x1="8" y1="18" x2="21" y2="18" />
				<line x1="3" y1="6" x2="3.01" y2="6" />
				<line x1="3" y1="12" x2="3.01" y2="12" />
				<line x1="3" y1="18" x2="3.01" y2="18" />
			</svg>
			كل الفلاتر
		</button>
		<button class="filter-drawer-btn" id="mChipOpen" onclick="mobileChip('open',this)" type="button">مفتوح الآن</button>
	</div>

	<!-- DRAWER OVERLAY -->
	<div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
	<div class="filter-drawer" id="filterDrawer">
		<div class="drawer-handle"></div>
		<div class="sidebar-header">
			<span class="sidebar-title">الفلاتر</span>
			<button class="clear-btn" onclick="clearFilters()" type="button">مسح الكل</button>
		</div>
		<div class="filter-group">
			<label class="filter-label">المنطقة</label>
			<select class="filter-select" id="drawerAreaSelect" onchange="var s=document.getElementById('areaSelect');if(s)s.value=this.value; applyFilters(); closeDrawer();">
				<option value="">كل المناطق</option>
				@foreach($areas as $area)
					<option value="{{ $area->id }}">{{ $area->name }}</option>
				@endforeach
			</select>
		</div>
		<div class="filter-group">
			<label class="filter-label">أقل تقييم</label>
			<div class="star-filter" id="drawerRatingFilter">
				<label class="star-opt active" onclick="setRating(0,this)"><span class="star-icon">★</span> أي</label>
				<label class="star-opt" onclick="setRating(3,this)"><span class="star-icon">★★★</span> 3+</label>
				<label class="star-opt" onclick="setRating(4,this)"><span class="star-icon">★★★★</span> 4+</label>
			</div>
		</div>
		<div class="filter-group">
			<label class="filter-label">التوفر</label>
			<div class="toggle-row">
				<button class="toggle" id="drawerOpenToggle" type="button" onclick="toggleOpen('drawerOpenToggle')"></button>
				<span class="toggle-label">مفتوح الآن فقط</span>
			</div>
		</div>
		<button class="reset-btn" style="width:100%;margin-top:8px" onclick="closeDrawer()" type="button">تطبيق الفلاتر</button>
	</div>

	<!-- MAIN LAYOUT -->
	<div class="layout">
		<!-- SIDEBAR -->
		<aside class="sidebar">
			<div class="sidebar-header">
				<span class="sidebar-title">Filters</span>
				<button class="clear-btn" onclick="clearFilters()">Clear all</button>
			</div>

			<div class="filter-group">
				<label class="filter-label">Area</label>
				<select class="filter-select" id="areaSelect" onchange="applyFilters()">
					<option value="">All areas</option>
					<option>Cairo — Maadi</option>
					<option>Cairo — Nasr City</option>
					<option>Cairo — Heliopolis</option>
					<option>Cairo — Downtown</option>
					<option>Giza — Dokki</option>
					<option>Giza — Mohandessin</option>
					<option>Alexandria</option>
				</select>
			</div>

			<div class="filter-group">
				<label class="filter-label">Type</label>
				<div class="chips" id="typeChips">
					<button class="chip active" data-type="all"
						onclick="selectChip(this,'typeChips')">All</button>
					<button class="chip clinic" data-type="Clinic"
						onclick="selectChip(this,'typeChips')">Clinic</button>
					<button class="chip lab" data-type="Laboratory"
						onclick="selectChip(this,'typeChips')">Laboratory</button>
					<button class="chip radiology" data-type="Radiology"
						onclick="selectChip(this,'typeChips')">Radiology</button>
				</div>
			</div>

			<div class="filter-group">
				<label class="filter-label">Min Rating</label>
				<div class="star-filter" id="ratingFilter">
					<label class="star-opt active" onclick="setRating(0,this)"><span
							class="star-icon">★</span> Any</label>
					<label class="star-opt" onclick="setRating(3,this)"><span
							class="star-icon">★★★</span> 3+</label>
					<label class="star-opt" onclick="setRating(4,this)"><span
							class="star-icon">★★★★</span> 4+</label>
				</div>
			</div>

			<div class="filter-group">
				<label class="filter-label">Availability</label>
				<div class="toggle-row">
					<button class="toggle" id="openToggle"
						onclick="toggleOpen('openToggle')"></button>
					<span class="toggle-label">Open now only</span>
				</div>
			</div>
		</aside>

		<!-- RESULTS -->
		<main class="main">
			<div class="results-header">
				<div class="results-count" id="resultsCount"><strong>{{ $clinics->count() }}</strong> عيادة</div>
				<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
					<select class="filter-select" style="width:160px" id="sortSelect" onchange="applyFilters()">
						<option value="rating">ترتيب: الأعلى تقييماً</option>
						<option value="name">ترتيب: الاسم أ–ي</option>
					</select>
					<div class="view-toggle">
						<button class="view-btn active" id="gridViewBtn" title="عرض شبكي" type="button" onclick="setView('grid')">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<rect x="3" y="3" width="7" height="7" />
								<rect x="14" y="3" width="7" height="7" />
								<rect x="3" y="14" width="7" height="7" />
								<rect x="14" y="14" width="7" height="7" />
							</svg>
						</button>
						<button class="view-btn" id="listViewBtn" title="عرض قائمة" type="button" onclick="setView('list')">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<line x1="8" y1="6" x2="21" y2="6" />
								<line x1="8" y1="12" x2="21" y2="12" />
								<line x1="8" y1="18" x2="21" y2="18" />
								<line x1="3" y1="6" x2="3.01" y2="6" />
								<line x1="3" y1="12" x2="3.01" y2="12" />
								<line x1="3" y1="18" x2="3.01" y2="18" />
							</svg>
						</button>
					</div>
				</div>
			</div>

			<div class="cards-grid" id="cardsGrid"></div>

			<div class="empty-state" id="emptyState">
				<span class="empty-icon">🔍</span>
				<h3>لم يتم العثور على عيادات</h3>
				<p>غيّر الفلاتر أو كلمات البحث للحصول على نتائج أكثر.</p>
				<button class="reset-btn" onclick="clearFilters()" type="button">إعادة تعيين الفلاتر</button>
			</div>

			<div id="paginationArea"></div>
		</main>
	</div>

	@php
		$clinicsJson = collect($clinics)->map(function ($c) {
			$areaParts = array_filter([
				$c->governorate?->name,
				$c->city?->name,
				$c->area?->name,
			]);
			$areaStr = implode(' — ', $areaParts);
			$tags = [];
			if ($c->specialty) {
				$tags[] = $c->specialty->name_ar ?? $c->specialty->name_en ?? '';
			}
			$tags = array_filter($tags);
			if (empty($tags)) {
				$tags = ['عيادة'];
			}
			return [
				'id' => $c->id,
				'name' => $c->name,
				'type' => 'Clinic',
				'area' => $areaStr ?: '—',
				'area_id' => $c->area_id,
				'rating' => round((float) ($c->reviews_avg_rating ?? 0), 1),
				'reviews' => (int) ($c->reviews_count ?? 0),
				'open' => true,
				'tags' => $tags,
				'emoji' => '🏥',
				'bg' => '#e8f6f1',
			];
		})->values();
	@endphp


@endsection

@push('scripts')

	<script>
	const FACILITIES = @json($clinicsJson);

	// Fix url in FACILITIES (Blade can't output route in loop easily in JSON)
	FACILITIES.forEach(function(f) {
		f.url = "{{ url('/') }}/clinic/" + f.id;
	});

	let filters = { areaId: '', rating: 0, openOnly: false };
	let currentView = 'grid';
	let currentPage = 1;
	const PER_PAGE = 9;

	function stars(r) {
		const full = Math.floor(r);
		const half = r - full >= 0.5;
		let s = '★'.repeat(full);
		if (half) s += '½';
		return s;
	}

	function renderCards(data) {
		const grid = document.getElementById('cardsGrid');
		const empty = document.getElementById('emptyState');
		grid.innerHTML = '';
		if (!data.length) {
			empty.classList.add('visible');
			document.getElementById('paginationArea').innerHTML = '';
			document.getElementById('resultsCount').innerHTML = '<strong>0</strong> عيادة';
			return;
		}
		empty.classList.remove('visible');
		const label = data.length === 1 ? 'عيادة' : 'عيادة';
		document.getElementById('resultsCount').innerHTML = '<strong>' + data.length + '</strong> ' + label;

		const total = data.length;
		const totalPages = Math.ceil(total / PER_PAGE);
		const paged = data.slice((currentPage - 1) * PER_PAGE, currentPage * PER_PAGE);

		paged.forEach(function(f, i) {
			const card = document.createElement('a');
			card.href = f.url;
			card.className = 'card';
			card.style.animationDelay = (i * 0.07) + 's';
			card.style.textDecoration = 'none';
			card.style.color = 'inherit';
			card.innerHTML = '<div class="card-image" style="background:' + f.bg + '">' +
				'<div class="card-image-placeholder" style="background:' + f.bg + '">' + f.emoji + '</div>' +
				'<span class="card-badge badge-clinic">عيادة</span>' +
				'</div>' +
				'<div class="card-body">' +
				'<div class="card-name">' + (f.name || '—') + '</div>' +
				'<div class="card-address"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> ' + (f.area || '—') + '</div>' +
				'<div class="card-rating"><span class="stars">' + stars(f.rating) + '</span> <span class="rating-num">' + f.rating + '</span> <span class="rating-count">(' + (f.reviews || 0) + ')</span></div>' +
				'<div class="card-tags">' + (f.tags || []).map(function(t){ return '<span class="tag">' + t + '</span>'; }).join('') + '</div>' +
				'</div>' +
				'<div class="card-footer"><span class="view-btn-card">عرض التفاصيل</span></div>';
			grid.appendChild(card);
		});

		const pa = document.getElementById('paginationArea');
		if (totalPages <= 1) { pa.innerHTML = ''; return; }
		var html = '<div class="pagination">';
		html += '<button class="page-btn" type="button" onclick="goPage(' + (currentPage - 1) + ')" ' + (currentPage === 1 ? 'disabled style="opacity:.4;cursor:default"' : '') + '>←</button>';
		for (var p = 1; p <= totalPages; p++) {
			html += '<button class="page-btn ' + (p === currentPage ? 'active' : '') + '" type="button" onclick="goPage(' + p + ')">' + p + '</button>';
		}
		html += '<button class="page-btn" type="button" onclick="goPage(' + (currentPage + 1) + ')" ' + (currentPage === totalPages ? 'disabled style="opacity:.4;cursor:default"' : '') + '>→</button>';
		html += '</div>';
		pa.innerHTML = html;
	}

	function getAreaSelectVal() {
		var el = document.getElementById('areaSelect');
		return el ? el.value : '';
	}

	function setAreaSelectVal(val) {
		var el = document.getElementById('areaSelect');
		if (el) el.value = val;
		var draw = document.getElementById('drawerAreaSelect');
		if (draw) draw.value = val;
	}

	function getFiltered() {
		var q = (document.getElementById('searchInput').value || '').toLowerCase();
		var areaId = getAreaSelectVal();
		var data = FACILITIES.filter(function(f) {
			if (areaId && String(f.area_id) !== String(areaId)) return false;
			if (filters.rating && f.rating < filters.rating) return false;
			if (filters.openOnly && !f.open) return false;
			if (q) {
				var nameMatch = (f.name || '').toLowerCase().indexOf(q) !== -1;
				var areaMatch = (f.area || '').toLowerCase().indexOf(q) !== -1;
				var tagMatch = (f.tags || []).some(function(t) { return t.toLowerCase().indexOf(q) !== -1; });
				if (!nameMatch && !areaMatch && !tagMatch) return false;
			}
			return true;
		});
		var sort = document.getElementById('sortSelect').value;
		if (sort === 'rating') data.sort(function(a, b) { return b.rating - a.rating; });
		if (sort === 'name') data.sort(function(a, b) { return (a.name || '').localeCompare(b.name || ''); });
		return data;
	}

	function applyFilters() {
		currentPage = 1;
		renderCards(getFiltered());
	}

	function goPage(p) {
		var total = Math.ceil(getFiltered().length / PER_PAGE);
		if (p < 1 || p > total) return;
		currentPage = p;
		renderCards(getFiltered());
		document.querySelector('.layout').scrollIntoView({ behavior: 'smooth' });
	}

	function setRating(val, el) {
		document.querySelectorAll('.star-opt').forEach(function(o) { o.classList.remove('active'); });
		document.querySelectorAll('[onclick*="setRating(0,"]').forEach(function(o) { if (val === 0) o.classList.add('active'); });
		document.querySelectorAll('[onclick*="setRating(3,"]').forEach(function(o) { if (val === 3) o.classList.add('active'); });
		document.querySelectorAll('[onclick*="setRating(4,"]').forEach(function(o) { if (val === 4) o.classList.add('active'); });
		filters.rating = val;
		applyFilters();
	}

	function toggleOpen(id) {
		var btn = document.getElementById(id);
		if (btn) btn.classList.toggle('on');
		filters.openOnly = btn && btn.classList.contains('on');
		['openToggle', 'drawerOpenToggle'].forEach(function(tid) {
			var t = document.getElementById(tid);
			if (t) { if (filters.openOnly) t.classList.add('on'); else t.classList.remove('on'); }
		});
		applyFilters();
	}

	function clearFilters() {
		filters = { areaId: '', rating: 0, openOnly: false };
		document.getElementById('searchInput').value = '';
		setAreaSelectVal('');
		document.getElementById('sortSelect').value = 'rating';
		document.querySelectorAll('.star-opt').forEach(function(o) { o.classList.remove('active'); });
		document.querySelectorAll('#ratingFilter .star-opt:first-child').forEach(function(o) { o.classList.add('active'); });
		var drawFirst = document.querySelector('#drawerRatingFilter .star-opt:first-child');
		if (drawFirst) drawFirst.classList.add('active');
		document.querySelectorAll('.toggle').forEach(function(t) { t.classList.remove('on'); });
		applyFilters();
	}

	function setView(v) {
		currentView = v;
		var grid = document.getElementById('cardsGrid');
		document.getElementById('gridViewBtn').classList.toggle('active', v === 'grid');
		document.getElementById('listViewBtn').classList.toggle('active', v === 'list');
		if (v === 'list') grid.classList.add('list-view');
		else grid.classList.remove('list-view');
	}

	function mobileChip(type, el) {
		document.querySelectorAll('#mobileFilterBar .filter-drawer-btn').forEach(function(b) { b.classList.remove('active'); });
		if (el) el.classList.add('active');
		if (type === 'open') {
			filters.openOnly = true;
			document.getElementById('openToggle').classList.add('on');
			var draw = document.getElementById('drawerOpenToggle');
			if (draw) draw.classList.add('on');
		} else {
			filters.openOnly = false;
			document.getElementById('openToggle').classList.remove('on');
			var draw = document.getElementById('drawerOpenToggle');
			if (draw) draw.classList.remove('on');
		}
		applyFilters();
	}

	function openDrawer() {
		document.getElementById('drawerOverlay').classList.add('open');
		document.getElementById('filterDrawer').classList.add('open');
		document.body.style.overflow = 'hidden';
		if (document.getElementById('drawerAreaSelect')) document.getElementById('drawerAreaSelect').value = getAreaSelectVal();
	}

	function closeDrawer() {
		document.getElementById('drawerOverlay').classList.remove('open');
		document.getElementById('filterDrawer').classList.remove('open');
		document.body.style.overflow = '';
	}

	document.getElementById('searchInput').addEventListener('keydown', function(e) {
		if (e.key === 'Enter') applyFilters();
	});

	document.querySelector('#ratingFilter .star-opt').classList.add('active');
	renderCards(getFiltered());
	</script>
@endpush
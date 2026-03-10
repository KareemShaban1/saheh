<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>صحيح — ربطك بالرعاية الصحية</title>
	<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&family=Playfair+Display:wght@700;900&display=swap"
		rel="stylesheet">
	<!-- tailwind -->
	<script src="https://cdn.tailwindcss.com"></script>
	<!-- font-awesome -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

	<style>
	:root {
		--indigo: #4338ca;
		--blue: #2563eb;
		--indigo-light: #6366f1;
		--sky: #0ea5e9;
		--white: #ffffff;
		--off-white: #f8faff;
		--soft: #eef2ff;
		--muted: #6b7280;
		--dark: #0f172a;
		--card-bg: rgba(255, 255, 255, 0.85);
		--radius: 18px;
		--shadow: 0 8px 40px rgba(67, 56, 202, 0.12);
		--shadow-hover: 0 20px 60px rgba(67, 56, 202, 0.22);
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
		direction: rtl;
	}

	body {
		font-family: 'Tajawal', 'DM Sans', sans-serif;
		background: var(--off-white);
		color: var(--dark);
		overflow-x: hidden;
	}

	/* HEADER */
	header {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		z-index: 100;
		padding: 0 4%;
		height: 72px;
		display: flex;
		align-items: center;
		justify-content: space-between;
		background: rgba(248, 250, 255, 0.85);
		backdrop-filter: blur(16px);
		border-bottom: 1px solid rgba(99, 102, 241, 0.1);
		transition: box-shadow 0.3s;
	}
	@media (min-width: 1200px) {
		header { padding: 0 5%; }
	}

	header.scrolled {
		box-shadow: 0 4px 30px rgba(67, 56, 202, 0.12);
	}

	.logo {
		display: flex;
		align-items: center;
		gap: 10px;
		text-decoration: none;
		font-family: 'Playfair Display', serif;
		font-size: 1.5rem;
		font-weight: 900;
		color: var(--dark);
	}

	.logo-icon {
		width: 36px;
		height: 36px;
		background: linear-gradient(135deg, var(--indigo), var(--sky));
		border-radius: 10px;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 18px;
	}

	.logo span {
		color: var(--indigo);
	}

	nav {
		display: flex;
		align-items: center;
		gap: 36px;
	}

	nav a {
		text-decoration: none;
		font-size: 0.92rem;
		font-weight: 500;
		color: var(--muted);
		transition: color 0.2s;
		letter-spacing: 0.01em;
	}

	nav a:hover {
		color: var(--indigo);
	}

	.header-actions {
		display: flex;
		gap: 12px;
		align-items: center;
	}

	.btn-ghost {
		padding: 9px 22px;
		border: 1.5px solid rgba(67, 56, 202, 0.3);
		border-radius: 100px;
		font-size: 0.88rem;
		font-weight: 500;
		color: var(--indigo);
		cursor: pointer;
		background: transparent;
		transition: all 0.2s;
		font-family: 'DM Sans', sans-serif;
		text-decoration: none;
	}

	.btn-ghost:hover {
		background: var(--soft);
		border-color: var(--indigo);
	}

	.btn-primary {
		padding: 9px 22px;
		border-radius: 100px;
		font-size: 0.88rem;
		font-weight: 500;
		color: #fff;
		cursor: pointer;
		background: linear-gradient(135deg, var(--indigo) 0%, var(--blue) 100%);
		border: none;
		transition: all 0.25s;
		font-family: 'DM Sans', sans-serif;
		text-decoration: none;
		box-shadow: 0 4px 16px rgba(67, 56, 202, 0.3);
	}

	.btn-primary:hover {
		transform: translateY(-1px);
		box-shadow: 0 8px 24px rgba(67, 56, 202, 0.4);
	}

	.hamburger {
		display: none;
		flex-direction: column;
		gap: 5px;
		cursor: pointer;
		padding: 4px;
	}

	.hamburger span {
		display: block;
		width: 24px;
		height: 2px;
		background: var(--dark);
		border-radius: 2px;
		transition: all 0.3s;
	}

	/* HERO */
	.hero {
		min-height: 100vh;
		padding: 120px 5% 80px;
		display: flex;
		align-items: center;
		position: relative;
		overflow: hidden;
		background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 40%, #dbeafe 100%);
	}

	.hero::before {
		content: '';
		position: absolute;
		top: -120px;
		right: -120px;
		width: 600px;
		height: 600px;
		border-radius: 50%;
		background: radial-gradient(circle, rgba(67, 56, 202, 0.18) 0%, transparent 70%);
		pointer-events: none;
	}

	.hero::after {
		content: '';
		position: absolute;
		bottom: -80px;
		left: -80px;
		width: 400px;
		height: 400px;
		border-radius: 50%;
		background: radial-gradient(circle, rgba(37, 99, 235, 0.12) 0%, transparent 70%);
		pointer-events: none;
	}

	.hero-inner {
		max-width: 1200px;
		margin: 0 auto;
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: 60px;
		align-items: center;
		width: 100%;
		position: relative;
		z-index: 1;
	}

	.hero-badge {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		padding: 6px 16px;
		background: rgba(67, 56, 202, 0.08);
		border: 1px solid rgba(67, 56, 202, 0.2);
		border-radius: 100px;
		font-size: 0.82rem;
		font-weight: 500;
		color: var(--indigo);
		margin-bottom: 24px;
		opacity: 0;
		animation: fadeUp 0.6s 0.1s forwards;
	}

	.hero-badge::before {
		content: '●';
		color: var(--sky);
		font-size: 0.6rem;
	}

	.hero h1 {
		font-family: 'Playfair Display', serif;
		font-size: clamp(2.8rem, 5vw, 4.2rem);
		font-weight: 900;
		line-height: 1.1;
		color: var(--dark);
		margin-bottom: 22px;
		opacity: 0;
		animation: fadeUp 0.7s 0.2s forwards;
	}

	.hero h1 em {
		font-style: normal;
		background: linear-gradient(135deg, var(--indigo), var(--sky));
		-webkit-background-clip: text;
		-webkit-text-fill-color: transparent;
		background-clip: text;
	}

	.hero p {
		font-size: 1.15rem;
		font-weight: 300;
		line-height: 1.7;
		color: #374151;
		margin-bottom: 36px;
		max-width: 460px;
		opacity: 0;
		animation: fadeUp 0.7s 0.35s forwards;
	}

	.hero-ctas {
		display: flex;
		gap: 14px;
		flex-wrap: wrap;
		opacity: 0;
		animation: fadeUp 0.7s 0.5s forwards;
	}

	.cta-primary {
		padding: 15px 32px;
		border-radius: 100px;
		font-size: 1rem;
		font-weight: 500;
		color: #fff;
		background: linear-gradient(135deg, var(--indigo) 0%, var(--blue) 100%);
		border: none;
		cursor: pointer;
		transition: all 0.25s;
		font-family: 'DM Sans', sans-serif;
		text-decoration: none;
		box-shadow: 0 6px 24px rgba(67, 56, 202, 0.4);
		display: inline-flex;
		align-items: center;
		gap: 8px;
	}

	.cta-primary:hover {
		transform: translateY(-2px);
		box-shadow: 0 12px 36px rgba(67, 56, 202, 0.5);
	}

	.cta-secondary {
		padding: 15px 32px;
		border-radius: 100px;
		font-size: 1rem;
		font-weight: 500;
		color: var(--indigo);
		background: rgba(67, 56, 202, 0.08);
		border: 1.5px solid rgba(67, 56, 202, 0.25);
		cursor: pointer;
		transition: all 0.25s;
		font-family: 'DM Sans', sans-serif;
		text-decoration: none;
		display: inline-flex;
		align-items: center;
		gap: 8px;
	}

	.cta-secondary:hover {
		background: rgba(67, 56, 202, 0.14);
		transform: translateY(-1px);
	}

	/* Hero illustration */
	.hero-visual {
		display: flex;
		justify-content: center;
		align-items: center;
		opacity: 0;
		animation: fadeLeft 0.9s 0.4s forwards;
		position: relative;
	}

	.hero-card-stack {
		position: relative;
		width: 100%;
		max-width: 400px;
	}

	.hero-main-card {
		background: white;
		border-radius: 24px;
		padding: 32px;
		box-shadow: 0 24px 80px rgba(67, 56, 202, 0.18);
		position: relative;
		z-index: 2;
	}

	.card-header-row {
		display: flex;
		align-items: center;
		gap: 12px;
		margin-bottom: 20px;
	}

	.avatar {
		width: 48px;
		height: 48px;
		border-radius: 50%;
		background: linear-gradient(135deg, var(--indigo), var(--sky));
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 20px;
		color: white;
	}

	.card-title {
		font-weight: 600;
		font-size: 1rem;
	}

	.card-sub {
		font-size: 0.82rem;
		color: var(--muted);
	}

	.appointment-list {
		display: flex;
		flex-direction: column;
		gap: 10px;
	}

	.apt-item {
		display: flex;
		align-items: center;
		gap: 12px;
		padding: 12px 14px;
		background: var(--soft);
		border-radius: 12px;
		font-size: 0.88rem;
	}

	.apt-icon {
		font-size: 16px;
	}

	.apt-info {
		flex: 1;
	}

	.apt-name {
		font-weight: 500;
	}

	.apt-time {
		font-size: 0.78rem;
		color: var(--muted);
	}

	.apt-badge {
		padding: 3px 10px;
		border-radius: 100px;
		font-size: 0.72rem;
		font-weight: 500;
	}

	.badge-green {
		background: #dcfce7;
		color: #16a34a;
	}

	.badge-blue {
		background: #dbeafe;
		color: #1d4ed8;
	}

	.badge-purple {
		background: #ede9fe;
		color: #7c3aed;
	}

	.floating-badge {
		position: absolute;
		background: white;
		border-radius: 14px;
		padding: 12px 16px;
		box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
		display: flex;
		align-items: center;
		gap: 10px;
		font-size: 0.82rem;
		font-weight: 500;
		z-index: 3;
	}

	.fb-top {
		top: -20px;
		right: -20px;
		animation: float 3s ease-in-out infinite;
	}

	.fb-bottom {
		bottom: 10px;
		left: -30px;
		animation: float 3.5s ease-in-out infinite reverse;
	}

	.fb-icon {
		font-size: 20px;
	}

	@keyframes float {

		0%,
		100% {
			transform: translateY(0px);
		}

		50% {
			transform: translateY(-8px);
		}
	}

	@keyframes fadeUp {
		from {
			opacity: 0;
			transform: translateY(24px);
		}

		to {
			opacity: 1;
			transform: translateY(0);
		}
	}

	@keyframes fadeLeft {
		from {
			opacity: 0;
			transform: translateX(30px);
		}

		to {
			opacity: 1;
			transform: translateX(0);
		}
	}

	/* SECTIONS COMMON */
	section {
		padding: 48px 4%;
	}
	@media (min-width: 768px) {
		section { padding: 72px 4%; }
	}
	@media (min-width: 1024px) {
		section { padding: 96px 5%; }
	}

	.section-inner {
		max-width: 1200px;
		margin: 0 auto;
	}

	.section-label {
		display: inline-block;
		font-size: 0.78rem;
		font-weight: 500;
		letter-spacing: 0.12em;
		text-transform: uppercase;
		color: var(--indigo);
		margin-bottom: 12px;
	}

	.section-heading {
		font-family: 'Playfair Display', serif;
		font-size: clamp(2rem, 3.5vw, 2.8rem);
		font-weight: 900;
		line-height: 1.15;
		color: var(--dark);
		margin-bottom: 16px;
	}

	.section-sub {
		font-size: 1rem;
		color: var(--muted);
		font-weight: 300;
		line-height: 1.7;
		max-width: 520px;
	}

	/* Reveal animation */
	.reveal {
		opacity: 0;
		transform: translateY(32px);
		transition: opacity 0.65s ease, transform 0.65s ease;
	}

	.reveal.visible {
		opacity: 1;
		transform: translateY(0);
	}

	/* VALUE PROPS */
	.value-props {
		background: white;
	}

	.value-header {
		text-align: center;
		margin-bottom: 60px;
	}

	.value-header .section-sub {
		max-width: 560px;
		margin: 0 auto;
	}

	.value-grid {
		display: grid;
		grid-template-columns: repeat(4, 1fr);
		gap: 24px;
	}

	.value-card {
		padding: 32px 28px;
		background: var(--off-white);
		border-radius: var(--radius);
		border: 1px solid rgba(99, 102, 241, 0.08);
		transition: all 0.3s;
		cursor: default;
	}

	.value-card:hover {
		background: white;
		box-shadow: var(--shadow-hover);
		transform: translateY(-6px);
		border-color: rgba(99, 102, 241, 0.2);
	}

	.value-icon {
		width: 52px;
		height: 52px;
		border-radius: 14px;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 24px;
		margin-bottom: 18px;
	}

	.vi-1 {
		background: linear-gradient(135deg, #ede9fe, #ddd6fe);
	}

	.vi-2 {
		background: linear-gradient(135deg, #dbeafe, #bfdbfe);
	}

	.vi-3 {
		background: linear-gradient(135deg, #dcfce7, #bbf7d0);
	}

	.vi-4 {
		background: linear-gradient(135deg, #fce7f3, #fbcfe8);
	}

	.value-card h3 {
		font-size: 1.05rem;
		font-weight: 600;
		margin-bottom: 8px;
	}

	.value-card p {
		font-size: 0.88rem;
		color: var(--muted);
		line-height: 1.6;
		font-weight: 300;
	}

	/* HOW IT WORKS */
	.how-it-works {
		background: var(--off-white);
	}

	.how-header {
		margin-bottom: 64px;
	}

	.steps-row {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 0;
		position: relative;
	}

	.steps-row::before {
		content: '';
		position: absolute;
		top: 36px;
		left: calc(16.66% + 36px);
		right: calc(16.66% + 36px);
		height: 2px;
		background: linear-gradient(90deg, var(--indigo), var(--sky));
		z-index: 0;
	}

	.step {
		text-align: center;
		padding: 0 24px;
		position: relative;
		z-index: 1;
	}

	.step-num {
		width: 72px;
		height: 72px;
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		margin: 0 auto 24px;
		font-size: 1.6rem;
		font-weight: 700;
		font-family: 'Playfair Display', serif;
		color: white;
		background: linear-gradient(135deg, var(--indigo), var(--blue));
		box-shadow: 0 8px 24px rgba(67, 56, 202, 0.35);
		transition: transform 0.3s;
	}

	.step:hover .step-num {
		transform: scale(1.1);
	}

	.step-icon-row {
		font-size: 24px;
		margin-bottom: 8px;
	}

	.step h3 {
		font-size: 1.1rem;
		font-weight: 600;
		margin-bottom: 10px;
	}

	.step p {
		font-size: 0.88rem;
		color: var(--muted);
		line-height: 1.65;
		font-weight: 300;
	}

	/* PROVIDERS */
	.providers {
		background: white;
	}

	.providers-header {
		margin-bottom: 44px;
	}

	.tabs {
		display: flex;
		gap: 8px;
		margin-bottom: 40px;
		flex-wrap: wrap;
	}

	.tab-btn {
		padding: 10px 24px;
		border-radius: 100px;
		border: 1.5px solid rgba(67, 56, 202, 0.2);
		background: transparent;
		font-size: 0.9rem;
		font-weight: 500;
		color: var(--muted);
		cursor: pointer;
		transition: all 0.2s;
		font-family: 'DM Sans', sans-serif;
	}

	.tab-btn.active {
		background: linear-gradient(135deg, var(--indigo), var(--blue));
		color: white;
		border-color: transparent;
		box-shadow: 0 4px 16px rgba(67, 56, 202, 0.3);
	}

	.tab-btn:not(.active):hover {
		border-color: var(--indigo);
		color: var(--indigo);
	}

	.tab-panel {
		display: none;
	}

	.tab-panel.active {
		display: block;
	}

	.provider-grid {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 24px;
	}

	.provider-card {
		background: var(--off-white);
		border-radius: var(--radius);
		padding: 28px;
		border: 1px solid rgba(99, 102, 241, 0.08);
		transition: all 0.3s;
		cursor: pointer;
	}

	.provider-card:hover {
		background: white;
		box-shadow: var(--shadow-hover);
		transform: translateY(-4px);
		border-color: rgba(99, 102, 241, 0.18);
	}

	.provider-top {
		display: flex;
		align-items: flex-start;
		justify-content: space-between;
		margin-bottom: 14px;
	}

	.provider-icon {
		font-size: 32px;
	}

	.provider-rating {
		display: flex;
		align-items: center;
		gap: 4px;
		font-size: 0.82rem;
		font-weight: 500;
		color: #f59e0b;
	}

	.provider-card h4 {
		font-size: 1rem;
		font-weight: 600;
		margin-bottom: 6px;
	}

	.provider-card p {
		font-size: 0.85rem;
		color: var(--muted);
		font-weight: 300;
		line-height: 1.6;
	}

	.provider-tags {
		display: flex;
		gap: 6px;
		flex-wrap: wrap;
		margin-top: 16px;
	}

	.provider-tag {
		padding: 3px 10px;
		background: var(--soft);
		border-radius: 100px;
		font-size: 0.75rem;
		color: var(--indigo);
		font-weight: 500;
	}

	/* SOCIAL PROOF */
	.social-proof {
		background: var(--off-white);
	}

	.stats-row {
		display: grid;
		grid-template-columns: repeat(4, 1fr);
		gap: 2px;
		margin-bottom: 60px;
	}

	.stat-block {
		text-align: center;
		padding: 36px 24px;
		background: white;
		border-radius: 0;
	}

	.stat-block:first-child {
		border-radius: var(--radius) 0 0 var(--radius);
	}

	.stat-block:last-child {
		border-radius: 0 var(--radius) var(--radius) 0;
	}

	.stat-num {
		font-family: 'Playfair Display', serif;
		font-size: 2.8rem;
		font-weight: 900;
		background: linear-gradient(135deg, var(--indigo), var(--sky));
		-webkit-background-clip: text;
		-webkit-text-fill-color: transparent;
		background-clip: text;
		line-height: 1;
		margin-bottom: 8px;
	}

	.stat-label {
		font-size: 0.88rem;
		color: var(--muted);
		font-weight: 400;
	}

	.testimonials-row {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 24px;
	}

	.testimonial {
		background: white;
		border-radius: var(--radius);
		padding: 30px;
		border: 1px solid rgba(99, 102, 241, 0.08);
		transition: all 0.3s;
	}

	.testimonial:hover {
		box-shadow: var(--shadow);
		transform: translateY(-3px);
	}

	.quote-mark {
		font-family: 'Playfair Display', serif;
		font-size: 3rem;
		line-height: 0.8;
		color: var(--indigo);
		opacity: 0.3;
		margin-bottom: 12px;
	}

	.testimonial p {
		font-size: 0.9rem;
		color: #374151;
		line-height: 1.7;
		font-weight: 300;
		font-style: italic;
		margin-bottom: 20px;
	}

	.testimonial-author {
		display: flex;
		align-items: center;
		gap: 12px;
	}

	.author-avatar {
		width: 40px;
		height: 40px;
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 16px;
		background: var(--soft);
	}

	.author-name {
		font-size: 0.88rem;
		font-weight: 600;
	}

	.author-role {
		font-size: 0.78rem;
		color: var(--muted);
	}

	/* FINAL CTA */
	.final-cta {
		background: linear-gradient(135deg, var(--indigo) 0%, #1e40af 60%, var(--blue) 100%);
		text-align: center;
		padding: 100px 5%;
		position: relative;
		overflow: hidden;
	}

	.final-cta::before {
		content: '';
		position: absolute;
		top: -100px;
		right: -100px;
		width: 400px;
		height: 400px;
		border-radius: 50%;
		background: rgba(255, 255, 255, 0.05);
		pointer-events: none;
	}

	.final-cta::after {
		content: '';
		position: absolute;
		bottom: -80px;
		left: -80px;
		width: 300px;
		height: 300px;
		border-radius: 50%;
		background: rgba(255, 255, 255, 0.05);
		pointer-events: none;
	}

	.final-cta h2 {
		font-family: 'Playfair Display', serif;
		font-size: clamp(2rem, 4vw, 3.2rem);
		font-weight: 900;
		color: white;
		margin-bottom: 16px;
		position: relative;
		z-index: 1;
	}

	.final-cta p {
		font-size: 1.05rem;
		color: rgba(255, 255, 255, 0.75);
		font-weight: 300;
		margin-bottom: 40px;
		position: relative;
		z-index: 1;
		max-width: 480px;
		margin: 0 auto 40px;
	}

	.final-cta-actions {
		display: flex;
		gap: 14px;
		justify-content: center;
		flex-wrap: wrap;
		position: relative;
		z-index: 1;
	}

	.cta-white {
		padding: 15px 36px;
		border-radius: 100px;
		font-size: 1rem;
		font-weight: 600;
		color: var(--indigo);
		background: white;
		border: none;
		cursor: pointer;
		transition: all 0.25s;
		font-family: 'DM Sans', sans-serif;
		text-decoration: none;
		box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
	}

	.cta-white:hover {
		transform: translateY(-2px);
		box-shadow: 0 16px 40px rgba(0, 0, 0, 0.25);
	}

	.cta-outline-white {
		padding: 15px 36px;
		border-radius: 100px;
		font-size: 1rem;
		font-weight: 500;
		color: white;
		background: rgba(255, 255, 255, 0.12);
		border: 1.5px solid rgba(255, 255, 255, 0.35);
		cursor: pointer;
		transition: all 0.25s;
		font-family: 'DM Sans', sans-serif;
		text-decoration: none;
	}

	.cta-outline-white:hover {
		background: rgba(255, 255, 255, 0.2);
	}

	/* FOOTER */
	footer {
		background: var(--dark);
		color: rgba(255, 255, 255, 0.6);
		padding: 60px 5% 40px;
	}

	.footer-inner {
		max-width: 1200px;
		margin: 0 auto;
		display: grid;
		grid-template-columns: 2fr 1fr 1fr 1fr;
		gap: 48px;
		margin-bottom: 48px;
	}

	.footer-logo {
		font-family: 'Playfair Display', serif;
		font-size: 1.4rem;
		font-weight: 900;
		color: white;
		margin-bottom: 14px;
		display: block;
		text-decoration: none;
	}

	.footer-logo span {
		color: var(--sky);
	}

	.footer-desc {
		font-size: 0.88rem;
		line-height: 1.7;
		font-weight: 300;
		margin-bottom: 24px;
	}

	.footer-social {
		display: flex;
		gap: 12px;
	}

	.social-icon {
		width: 36px;
		height: 36px;
		border-radius: 50%;
		background: rgba(255, 255, 255, 0.08);
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 14px;
		cursor: pointer;
		transition: background 0.2s;
		text-decoration: none;
		color: white;
	}

	.social-icon:hover {
		background: rgba(255, 255, 255, 0.18);
	}

	.footer-col h5 {
		color: white;
		font-size: 0.9rem;
		font-weight: 600;
		margin-bottom: 16px;
		letter-spacing: 0.03em;
	}

	.footer-col ul {
		list-style: none;
		display: flex;
		flex-direction: column;
		gap: 10px;
	}

	.footer-col ul li a {
		color: rgba(255, 255, 255, 0.5);
		text-decoration: none;
		font-size: 0.88rem;
		font-weight: 300;
		transition: color 0.2s;
	}

	.footer-col ul li a:hover {
		color: white;
	}

	.footer-bottom {
		max-width: 1200px;
		margin: 0 auto;
		border-top: 1px solid rgba(255, 255, 255, 0.08);
		padding-top: 28px;
		display: flex;
		justify-content: space-between;
		align-items: center;
		font-size: 0.82rem;
	}

	.footer-bottom-links {
		display: flex;
		gap: 24px;
	}

	.footer-bottom-links a {
		color: rgba(255, 255, 255, 0.4);
		text-decoration: none;
		transition: color 0.2s;
	}

	.footer-bottom-links a:hover {
		color: rgba(255, 255, 255, 0.8);
	}

	/* RESPONSIVE */
	@media (max-width: 1024px) {
		.value-grid {
			grid-template-columns: repeat(2, 1fr);
			gap: 20px;
		}
		.provider-grid {
			grid-template-columns: repeat(2, 1fr);
		}
		.hero h1 {
			font-size: clamp(2.2rem, 4vw, 3.2rem);
		}
		.footer-inner {
			grid-template-columns: 2fr 1fr 1fr;
			gap: 32px;
		}
	}

	@media (max-width: 768px) {
		nav,
		.header-actions {
			display: none;
		}

		nav.open {
			display: flex;
			flex-direction: column;
			position: fixed;
			top: 72px;
			left: 0;
			right: 0;
			background: white;
			padding: 24px 4%;
			box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
			gap: 20px;
			z-index: 99;
		}

		.header-actions.open {
			display: flex;
			flex-direction: column;
			position: fixed;
			top: calc(72px + 3*44px + 72px);
			left: 0;
			right: 0;
			background: white;
			padding: 0 4% 24px;
			z-index: 99;
			gap: 12px;
		}

		.hamburger {
			display: flex;
		}

		.hero-inner {
			grid-template-columns: 1fr;
			gap: 32px;
		}

		.hero-visual {
			order: -1;
		}

		.hero-card-stack {
			max-width: 100%;
			padding: 0 8px;
		}

		.hero p {
			max-width: 100%;
		}

		.value-grid {
			grid-template-columns: 1fr;
		}

		.steps-row {
			grid-template-columns: 1fr;
			gap: 36px;
		}

		.steps-row::before {
			display: none;
		}

		.provider-grid {
			grid-template-columns: 1fr;
			gap: 20px;
		}

		.tabs {
			gap: 6px;
			margin-bottom: 28px;
		}

		.tab-btn {
			padding: 8px 16px;
			font-size: 0.85rem;
		}

		.stats-row {
			grid-template-columns: repeat(2, 1fr);
			gap: 2px;
			margin-bottom: 40px;
		}

		.stat-block {
			padding: 24px 16px;
		}

		.stat-num {
			font-size: 2rem;
		}

		.stat-block:first-child {
			border-radius: var(--radius) 0 0 0;
		}

		.stat-block:nth-child(2) {
			border-radius: 0 var(--radius) 0 0;
		}

		.stat-block:last-child {
			border-radius: 0 0 var(--radius) 0;
		}

		.testimonials-row {
			grid-template-columns: 1fr;
			gap: 20px;
		}

		.final-cta {
			padding: 60px 4%;
		}

		.final-cta h2 {
			font-size: clamp(1.6rem, 5vw, 2.4rem);
		}

		.footer-inner {
			grid-template-columns: 1fr 1fr;
			gap: 28px;
			margin-bottom: 36px;
		}

		footer {
			padding: 40px 4% 28px;
		}

		.footer-bottom {
			flex-direction: column;
			gap: 16px;
			text-align: center;
			padding-top: 20px;
		}

		.footer-bottom-links {
			flex-wrap: wrap;
			justify-content: center;
		}
	}

	@media (max-width: 480px) {
		.hero-ctas {
			flex-direction: column;
			width: 100%;
		}

		.hero-ctas a {
			width: 100%;
			justify-content: center;
		}

		.cta-primary, .cta-secondary {
			padding: 14px 24px;
		}

		.value-card {
			padding: 24px 20px;
		}

		.stats-row {
			grid-template-columns: 1fr;
		}

		.stat-block:first-child {
			border-radius: var(--radius) var(--radius) 0 0;
		}

		.stat-block:nth-child(2) {
			border-radius: 0;
		}

		.stat-block:last-child {
			border-radius: 0 0 var(--radius) var(--radius);
		}

		.footer-inner {
			grid-template-columns: 1fr;
			text-align: center;
		}

		.footer-social {
			justify-content: center;
		}

		.final-cta-actions {
			flex-direction: column;
			width: 100%;
		}

		.final-cta-actions a {
			width: 100%;
			justify-content: center;
		}
	}

	/* Stagger delays for value cards */
	.value-card:nth-child(1) {
		transition-delay: 0s;
	}

	.value-card:nth-child(2) {
		transition-delay: 0.1s;
	}

	.value-card:nth-child(3) {
		transition-delay: 0.2s;
	}

	.value-card:nth-child(4) {
		transition-delay: 0.3s;
	}
	</style>

@stack('styles')
</head>

<body>

@include('frontend.layouts.header')

<div>
@yield('content')
</div>

@include('frontend.layouts.footer')
</body>

@stack('scripts')

</html>



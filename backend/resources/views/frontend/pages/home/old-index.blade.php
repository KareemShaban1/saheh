@extends('frontend.layouts.app')

@section('content')

<!-- Hero Section with Slider -->
<section id="home" class="relative lg:pt-20 pt-0 lg:p-0 h-full overflow-hidden" x-data="slider()">
	<!-- Animated Background -->
	<!-- <div class="absolute inset-0 bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
		<div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%233B82F6" fill-opacity="0.03"%3E%3Cpath d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-40"></div>
	</div> -->

	<div
		class="relative z-10 rounded-2xl bg-white/80 backdrop-blur-sm py-10 overflow-hidden m-5 lg:m-0 2xl:py-16 xl:py-8 lg:rounded-tl-2xl lg:rounded-bl-2xl shadow-2xl border border-blue-100/50">
		<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
			<div class="grid grid-cols-1 gap-14 items-center lg:grid-cols-12 lg:gap-32">
				<!-- LEFT CONTENT -->
				<div class="w-full xl:col-span-5 lg:col-span-6 2xl:-mx-5 xl:-mx-0">
					<div
						class="flex items-center text-sm font-medium text-gray-600 justify-center lg:justify-start mb-4">
						<span
							class="bg-gradient-to-r from-blue-500 to-indigo-600 py-2 px-4 rounded-full text-xs font-bold text-white mx-3 shadow-lg animate-pulse">
							#<span x-text="currentSlide + 1"></span>
						</span>
						<span class="text-indigo-600 font-semibold"
							x-text="slides[currentSlide].tagline"></span>
					</div>

					<h1 class="py-8 text-center text-gray-900 font-bold text-4xl lg:text-6xl lg:text-right leading-tight animate-fade-in"
						x-html="slides[currentSlide].title">
					</h1>

					<p class="text-gray-600 text-lg text-center lg:text-right leading-relaxed mb-8 animate-fade-in"
						x-text="slides[currentSlide].description">
					</p>

					<div
						class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start mb-6">
						<a :href="slides[currentSlide].link"
							class="group bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full py-4 px-8 text-base font-semibold text-white hover:from-blue-600 hover:to-indigo-700 transition-all duration-500 shadow-lg hover:shadow-xl transform hover:scale-105 text-center">
							<i
								class="fas fa-play-circle ml-2 group-hover:scale-110 transition-transform"></i>
							شاهد العرض التوضيحي
						</a>
						<a :href="slides[currentSlide].registerLink"
							class="group bg-white border-2 border-blue-500 rounded-full py-4 px-8 text-base font-semibold text-blue-600 hover:bg-blue-50 transition-all duration-500 shadow-lg hover:shadow-xl transform hover:scale-105 text-center">
							<i
								class="fas fa-user-plus ml-2 group-hover:rotate-12 transition-transform"></i>
							سجل الآن مجاناً
						</a>
					</div>

					<!-- Trust Indicators -->
					<div
						class="flex items-center justify-center lg:justify-start gap-6 mt-8">
						<div class="text-center group">
							<div class="text-2xl font-bold text-blue-600 transition-all duration-300 group-hover:scale-110"
								data-target="{{ $patientsCount ?? 0 }}">
								0</div>
							<div class="text-xs text-gray-500">مريض</div>
						</div>
						<div class="text-center group">
							<div class="text-2xl font-bold text-indigo-600 transition-all duration-300 group-hover:scale-110"
								data-target="{{ $doctorsCount ?? 0 }}">0
							</div>
							<div class="text-xs text-gray-500">طبيب</div>
						</div>
						<div class="text-center group">
							<div class="text-2xl font-bold text-purple-600 transition-all duration-300 group-hover:scale-110"
								data-target="{{ $clinicsCount ?? 0 }}">0
							</div>
							<div class="text-xs text-gray-500">عيادة</div>
						</div>
					</div>
				</div>

				<!-- RIGHT IMAGE SLIDER -->
				<div class="w-full xl:col-span-7 lg:col-span-6 block relative">
					<template x-for="(slide, index) in slides" :key="index">
						<div x-show="currentSlide === index"
							x-transition:enter="transition ease-out duration-700"
							x-transition:enter-start="opacity-0 transform scale-95 translate-x-10"
							x-transition:enter-end="opacity-100 transform scale-100 translate-x-0"
							x-transition:leave="transition ease-in duration-500"
							x-transition:leave-start="opacity-100 transform scale-100"
							x-transition:leave-end="opacity-0 transform scale-95 -translate-x-10"
							class="absolute inset-0 flex justify-center items-center">
							<div class="relative group">
								<div
									class="absolute -inset-4 bg-gradient-to-r from-blue-400 to-indigo-500 rounded-3xl blur-2xl opacity-30 group-hover:opacity-50 transition-opacity">
								</div>
								<img :src="slide.image"
									alt="Slide image"
									class="relative rounded-l-3xl w-full lg:h-auto object-cover shadow-2xl transition-all duration-700 transform group-hover:scale-105">
							</div>
						</div>
					</template>

					<!-- Slider Controls -->
					<div
						class="relative flex space-x-2 justify-center mt-8 lg:relative lg:bottom-[-13rem] lg:right-8">
						<template x-for="(slide, index) in slides" :key="index">
							<button @click="goToSlide(index)"
								class="w-3 h-3 rounded-full transition-all duration-300 transform hover:scale-125"
								:class="currentSlide === index ? 'bg-blue-600 w-8 shadow-lg' : 'bg-gray-300 hover:bg-gray-400'"></button>
						</template>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<!-- Statistics Section -->
@include('frontend.pages.home.partials.statistics',
compact('clinicsCount','medicalLabsCount','radiologyCentersCount','doctorsCount','patientsCount','reservationsCount'))

<!-- About Section -->
@include('frontend.pages.home.partials.about')

<!-- Features Section -->
<section id="features" class="py-20 bg-white relative overflow-hidden">
	<!-- Background Pattern -->
	<div class="absolute inset-0 opacity-5">
		<div class="absolute inset-0"
			style="background-image: radial-gradient(circle, #3B82F6 1px, transparent 1px); background-size: 50px 50px;">
		</div>
	</div>

	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
		<div class="text-center mb-16">
			<span
				class="inline-block bg-blue-100 text-blue-600 px-4 py-2 rounded-full text-sm font-semibold mb-4">
				المميزات
			</span>
			<h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
				المميزات الرئيسية
			</h2>
			<p class="text-xl text-gray-600 max-w-3xl mx-auto">
				نظام شامل لإدارة الرعاية الصحية مصمم خصيصاً للعيادات والمختبرات الطبية ومراكز
				الأشعة
			</p>
		</div>

		<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
			<!-- Feature Cards with Unified Design -->
			@php
			$features = [
			['icon' => 'users-cog', 'title' => 'إدارة متعددة المستأجرين', 'desc' => 'نظام متعدد
			المستأجرين يوفر عزل كامل للبيانات بين المنظمات المختلفة مع إدارة مركزية فعالة', 'color' =>
			'blue'],
			['icon' => 'user-md', 'title' => 'إدارة المرضى الشاملة', 'desc' => 'إدارة كاملة لملفات
			المرضى مع تاريخ طبي شامل، وصفات طبية، أمراض مزمنة، ونتائج الفحوصات', 'color' =>
			'emerald'],
			['icon' => 'calendar-check', 'title' => 'نظام الحجوزات الذكي', 'desc' => 'حجز مواعيد ذكي
			مع إدارة الفترات الزمنية، تتبع توفر الأطباء، وإشعارات تلقائية', 'color' => 'purple'],
			['icon' => 'file-medical', 'title' => 'السجلات الطبية الرقمية', 'desc' => 'إدارة رقمية
			كاملة للوصفات الطبية، تتبع الأمراض المزمنة، تخزين الصور الطبية، ونتائج المختبرات', 'color'
			=> 'rose'],
			['icon' => 'comments', 'title' => 'نظام التواصل الفوري', 'desc' => 'دردشة فورية بين أعضاء
			الفريق، مشاركة الملفات، دعم الإيموجي، ونظام إشعارات متقدم', 'color' => 'cyan'],
			['icon' => 'chart-line', 'title' => 'التحليلات والتقارير', 'desc' => 'لوحة تحكم شاملة مع
			مؤشرات الأداء الرئيسية، إحصائيات المرضى، تقارير الإيرادات، وتحليلات المواعيد', 'color' =>
			'amber'],
			['icon' => 'shield-alt', 'title' => 'الأمان والحماية', 'desc' => 'مصادقة متعددة المستويات،
			تحكم في الوصول بناءً على الأدوار، تشفير البيانات، وحماية CSRF', 'color' => 'teal'],
			['icon' => 'mobile-alt', 'title' => 'واجهة برمجة تطبيقات RESTful', 'desc' => 'واجهة برمجة
			تطبيقات كاملة لإدارة المرضى، الحجوزات، الوصول للسجلات الطبية، ودعم التطبيقات المحمولة',
			'color' => 'indigo'],
			['icon' => 'language', 'title' => 'دعم متعدد اللغات', 'desc' => 'واجهة بالعربية
			والإنجليزية مع دعم RTL كامل للعربية وإدارة محتوى محلي', 'color' => 'pink'],
			];
			$colorClasses = [
			'blue' => ['bg' => 'from-blue-500 to-blue-600', 'light' => 'from-blue-50 to-blue-100',
			'text' => 'text-blue-600'],
			'emerald' => ['bg' => 'from-emerald-500 to-emerald-600', 'light' => 'from-emerald-50
			to-emerald-100', 'text' => 'text-emerald-600'],
			'purple' => ['bg' => 'from-purple-500 to-purple-600', 'light' => 'from-purple-50
			to-purple-100', 'text' => 'text-purple-600'],
			'rose' => ['bg' => 'from-rose-500 to-rose-600', 'light' => 'from-rose-50 to-rose-100',
			'text' => 'text-rose-600'],
			'cyan' => ['bg' => 'from-cyan-500 to-cyan-600', 'light' => 'from-cyan-50 to-cyan-100',
			'text' => 'text-cyan-600'],
			'amber' => ['bg' => 'from-amber-500 to-amber-600', 'light' => 'from-amber-50
			to-amber-100', 'text' => 'text-amber-600'],
			'teal' => ['bg' => 'from-teal-500 to-teal-600', 'light' => 'from-teal-50 to-teal-100',
			'text' => 'text-teal-600'],
			'indigo' => ['bg' => 'from-indigo-500 to-indigo-600', 'light' => 'from-indigo-50
			to-indigo-100', 'text' => 'text-indigo-600'],
			'pink' => ['bg' => 'from-pink-500 to-pink-600', 'light' => 'from-pink-50 to-pink-100',
			'text' => 'text-pink-600'],
			];
			@endphp

			@foreach($features as $feature)
			<div
				class="group bg-gradient-to-br {{ $colorClasses[$feature['color']]['light'] }} p-6 rounded-2xl shadow-md hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-2 border border-white/50">
				<div
					class="w-14 h-14 bg-gradient-to-br {{ $colorClasses[$feature['color']]['bg'] }} rounded-xl flex items-center justify-center mb-4 shadow-lg group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
					<i class="fas fa-{{ $feature['icon'] }} text-2xl text-white"></i>
				</div>
				<h3 class="text-xl font-bold text-gray-900 mb-3">{{ $feature['title'] }}</h3>
				<p class="text-gray-600 leading-relaxed text-sm">{{ $feature['desc'] }}</p>
			</div>
			@endforeach
		</div>
	</div>
</section>

<!-- Organizatons Section -->
<section id="organizations"
	class="py-20 bg-gradient-to-br from-gray-50 via-blue-50/50 to-indigo-50/50 relative overflow-hidden">
	<!-- Decorative Elements -->
	<div
		class="absolute top-0 right-0 w-96 h-96 bg-blue-200 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse">
	</div>
	<div
		class="absolute bottom-0 left-0 w-96 h-96 bg-indigo-200 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse">
	</div>

	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
		<div class="text-center mb-16">
			<span
				class="inline-block bg-blue-100 text-blue-600 px-4 py-2 rounded-full text-sm font-semibold mb-4">
				المنظمات
			</span>
			<h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
				أنواع المنظمات المدعومة
			</h2>
			<p class="text-xl text-gray-600 max-w-3xl mx-auto">
				نظام
				شامل يدعم ثلاثة أنواع من منظمات الرعاية الصحية
			</p>
		</div>

		<div class="grid grid-cols-1 md:grid-cols-3 gap-8">
			<!-- Clinic Card -->
			<div
				class="group bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-4 border-t-4 border-blue-500">
				<div class="text-center mb-6">
					<div
						class="w-20 h-20 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
						<i class="fas fa-hospital text-4xl text-white"></i>
					</div>
					<h3 class="text-2xl font-bold text-gray-900 mb-2">العيادات الطبية</h3>
					<p class="text-gray-600">إدارة شاملة للعيادات مع جميع التخصصات</p>
				</div>
				<ul class="space-y-3 text-right mb-6">
					<li class="flex items-center justify-end group/item">
						<span
							class="text-gray-600 ml-3 group-hover/item:text-gray-900 transition-colors">إدارة
							المرضى والمواعيد</span>
						<i class="fas fa-check-circle text-emerald-500"></i>
					</li>
					<li class="flex items-center justify-end group/item">
						<span
							class="text-gray-600 ml-3 group-hover/item:text-gray-900 transition-colors">الوصفات
							الطبية والأمراض المزمنة</span>
						<i class="fas fa-check-circle text-emerald-500"></i>
					</li>
					<li class="flex items-center justify-end group/item">
						<span
							class="text-gray-600 ml-3 group-hover/item:text-gray-900 transition-colors">إدارة
							الأدوية والمخزون</span>
						<i class="fas fa-check-circle text-emerald-500"></i>
					</li>
					<li class="flex items-center justify-end group/item">
						<span
							class="text-gray-600 ml-3 group-hover/item:text-gray-900 transition-colors">إدارة
							الرسوم والفوترة</span>
						<i class="fas fa-check-circle text-emerald-500"></i>
					</li>
				</ul>
				<a href="{{ route('register-clinic') }}"
					class="block w-full bg-gradient-to-r from-blue-500 to-indigo-600 text-white text-center py-3 rounded-xl font-semibold hover:from-blue-600 hover:to-indigo-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105">
					سجل عيادتك الآن <i class="fas fa-arrow-left ml-2"></i>
				</a>
			</div>

			<!-- Medical Laboratory Card -->
			<div
				class="group bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-4 border-t-4 border-emerald-500">
				<div class="text-center mb-6">
					<div
						class="w-20 h-20 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
						<i class="fas fa-flask text-4xl text-white"></i>
					</div>
					<h3 class="text-2xl font-bold text-gray-900 mb-2">المختبرات الطبية
					</h3>
					<p class="text-gray-600">إدارة كاملة للمختبرات والتحاليل</p>
				</div>
				<ul class="space-y-3 text-right mb-6">
					<li class="flex items-center justify-end group/item">
						<span
							class="text-gray-600 ml-3 group-hover/item:text-gray-900 transition-colors">إدارة
							التحاليل الطبية</span>
						<i class="fas fa-check-circle text-emerald-500"></i>
					</li>
					<li class="flex items-center justify-end group/item">
						<span
							class="text-gray-600 ml-3 group-hover/item:text-gray-900 transition-colors">فئات
							الخدمات والتحاليل</span>
						<i class="fas fa-check-circle text-emerald-500"></i>
					</li>
					<li class="flex items-center justify-end group/item">
						<span
							class="text-gray-600 ml-3 group-hover/item:text-gray-900 transition-colors">إدارة
							النتائج والتقارير</span>
						<i class="fas fa-check-circle text-emerald-500"></i>
					</li>
					<li class="flex items-center justify-end group/item">
						<span
							class="text-gray-600 ml-3 group-hover/item:text-gray-900 transition-colors">إدارة
							الفواتير والرسوم</span>
						<i class="fas fa-check-circle text-emerald-500"></i>
					</li>
				</ul>
				<a href="{{ route('register-medical-laboratory') }}"
					class="block w-full bg-gradient-to-r from-emerald-500 to-teal-600 text-white text-center py-3 rounded-xl font-semibold hover:from-emerald-600 hover:to-teal-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105">
					سجل مختبرك الآن <i class="fas fa-arrow-left ml-2"></i>
				</a>
			</div>

			<!-- Radiology Center Card -->
			<div
				class="group bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-4 border-t-4 border-purple-500">
				<div class="text-center mb-6">
					<div
						class="w-20 h-20 bg-gradient-to-br from-purple-500 to-pink-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
						<i class="fas fa-x-ray text-4xl text-white"></i>
					</div>
					<h3 class="text-2xl font-bold text-gray-900 mb-2">مراكز الأشعة</h3>
					<p class="text-gray-600">إدارة متكاملة لمراكز التصوير الطبي</p>
				</div>
				<ul class="space-y-3 text-right mb-6">
					<li class="flex items-center justify-end group/item">
						<span
							class="text-gray-600 ml-3 group-hover/item:text-gray-900 transition-colors">إدارة
							الصور الطبية</span>
						<i class="fas fa-check-circle text-emerald-500"></i>
					</li>
					<li class="flex items-center justify-end group/item">
						<span
							class="text-gray-600 ml-3 group-hover/item:text-gray-900 transition-colors">أنواع
							الأشعة المختلفة</span>
						<i class="fas fa-check-circle text-emerald-500"></i>
					</li>
					<li class="flex items-center justify-end group/item">
						<span
							class="text-gray-600 ml-3 group-hover/item:text-gray-900 transition-colors">إدارة
							التقارير والنتائج</span>
						<i class="fas fa-check-circle text-emerald-500"></i>
					</li>
					<li class="flex items-center justify-end group/item">
						<span
							class="text-gray-600 ml-3 group-hover/item:text-gray-900 transition-colors">إدارة
							المعدات والخدمات</span>
						<i class="fas fa-check-circle text-emerald-500"></i>
					</li>
				</ul>
				<a href="{{ route('register-radiology-center') }}"
					class="block w-full bg-gradient-to-r from-purple-500 to-pink-600 text-white text-center py-3 rounded-xl font-semibold hover:from-purple-600 hover:to-pink-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105">
					سجل مركزك الآن <i class="fas fa-arrow-left ml-2"></i>
				</a>
			</div>
		</div>
	</div>
</section>

<!-- Services Section -->
@include('frontend.pages.home.partials.services')

<!-- Technology Stack Section -->
<section id="technology" class="py-20 bg-white relative overflow-hidden">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
		<div class="text-center mb-16">
			<span
				class="inline-block bg-blue-100 text-blue-600 px-4 py-2 rounded-full text-sm font-semibold mb-4">
				التقنيات
			</span>
			<h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
				التقنيات المستخدمة
			</h2>
			<p class="text-xl text-gray-600 max-w-3xl mx-auto">
				مبني بأحدث التقنيات والأدوات لضمان الأداء والأمان
			</p>
		</div>

		<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
			@php
			$techs = [
			['icon' => 'laravel', 'name' => 'Laravel 10', 'color' => 'red'],
			['icon' => 'php', 'name' => 'PHP 8.2+', 'color' => 'indigo'],
			['icon' => 'js', 'name' => 'JavaScript', 'color' => 'yellow'],
			['icon' => 'vuejs', 'name' => 'Vue.js', 'color' => 'emerald'],
			['icon' => 'bootstrap', 'name' => 'Bootstrap 5', 'color' => 'purple'],
			['icon' => 'database', 'name' => 'MySQL', 'color' => 'blue'],
			];
			@endphp

			@foreach($techs as $tech)
			<div
				class="group bg-gradient-to-br from-gray-50 to-gray-100 p-6 rounded-xl text-center hover:shadow-xl transition-all duration-300 transform hover:scale-110 border border-gray-200 hover:border-blue-300">
				<i
					class="fab fa-{{ $tech['icon'] }} text-5xl text-{{ $tech['color'] }}-500 mb-3 group-hover:scale-110 transition-transform duration-300"></i>
				<p
					class="font-semibold text-gray-700 group-hover:text-gray-900 transition-colors">
					{{ $tech['name'] }}</p>
			</div>
			@endforeach
		</div>
	</div>
</section>

<!-- Contact Section -->
@include('frontend.pages.home.partials.contact')

@endsection

@push('scripts')
<!-- Alpine.js -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<script>
function slider() {
	return {
		currentSlide: 0,
		slides: [{
				image: "{{ asset('images/clinic_dashboard_1.png') }}",
				tagline: "لوحة تحكم العيادات",
				title: "نظام إدارة العيادات <span class='text-blue-600'>الشامل</span>",
				description: "إدارة كاملة لعيادتك مع المرضى، المواعيد، الوصفات الطبية، وإدارة الموظفين في مكان واحد",
				link: "#",
				registerLink: "{{ route('register-clinic') }}"
			},
			{
				image: "{{ asset('images/clinic_dashboard_1.png') }}",
				tagline: "لوحة تحكم المختبرات",
				title: "نظام إدارة المختبرات <span class='text-emerald-600'>المتقدم</span>",
				description: "إدارة مختبرك الطبي مع المرضى، نتائج التحاليل، إدارة الخدمات، وإدارة الفواتير",
				link: "#",
				registerLink: "{{ route('register-medical-laboratory') }}"
			},
			{
				image: "{{ asset('images/clinic_dashboard_1.png') }}",
				tagline: "لوحة تحكم مراكز الأشعة",
				title: "نظام إدارة مراكز الأشعة <span class='text-purple-600'>المتكامل</span>",
				description: "إدارة مركز الأشعة الخاص بك مع المرضى، الصور الطبية، التقارير، وإدارة المعدات",
				link: "#",
				registerLink: "{{ route('register-radiology-center') }}"
			}
		],
		goToSlide(index) {
			this.currentSlide = index;
		},
		next() {
			this.currentSlide = (this.currentSlide + 1) % this.slides.length;
		},
		init() {
			setInterval(() => this.next(), 6000);
		}
	}
}

// Counter Animation with Intersection Observer
document.addEventListener('DOMContentLoaded', function() {
	const counters = document.querySelectorAll('[data-target]');

	const animateCounter = (counter) => {
		const target = parseInt(counter.getAttribute('data-target')) || 0;
		const duration = 2000;
		const increment = target / (duration / 16);
		let current = 0;

		const timer = setInterval(() => {
			current += increment;
			if (current >= target) {
				current = target;
				clearInterval(timer);
			}
			counter.textContent = Math.floor(
					current)
				.toLocaleString('ar-EG');
		}, 16);
	};

	const observer = new IntersectionObserver((entries) => {
		entries.forEach(entry => {
			if (entry.isIntersecting && !
				entry.target
				.classList.contains(
					'counted')
			) {
				entry.target
					.classList
					.add(
						'counted'
						);
				animateCounter(entry
					.target
					);
			}
		});
	}, {
		threshold: 0.5
	});

	counters.forEach(counter => observer.observe(counter));
});
</script>

<style>
@keyframes fade-in {
	from {
		opacity: 0;
		transform: translateY(20px);
	}

	to {
		opacity: 1;
		transform: translateY(0);
	}
}

.animate-fade-in {
	animation: fade-in 0.8s ease-out;
}
</style>
@endpush

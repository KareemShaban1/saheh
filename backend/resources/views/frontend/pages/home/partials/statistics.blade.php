<section class="relative py-20 bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-600 overflow-hidden">
	<!-- Animated Background -->
	<div class="absolute inset-0">
		<div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=" 60" height="60"
			viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg" %3E%3Cg fill="none"
			fill-rule="evenodd" %3E%3Cg fill="%23ffffff" fill-opacity="0.05" %3E%3Cpath
			d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z"
			/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')]"></div>
		<div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
		<div class="absolute bottom-0 left-0 w-96 h-96 bg-indigo-400/20 rounded-full blur-3xl"></div>
	</div>

	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
		<div class="text-center mb-16">
			<span
				class="inline-block bg-white/20 backdrop-blur-sm text-indigo-600 px-4 py-2 rounded-full text-sm font-semibold mb-4 border border-white/30 shadow-lg">
				الإحصائيات
			</span>
			<h2 class="text-4xl lgblac-5xl font-bold text-black mb-4 drop-shadow-lg">
				أرقام تتحدث عن نجاحنا
			</h2>
			<p class="text-xl text-black max-w-3xl mx-auto leading-relaxed">
				نظام شامل لإدارة الرعاية الصحية يخدم آلاف المنظمات والمرضى في جميع أنحاء المنطقة
			</p>
		</div>

		<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
			<!-- Clinics -->
			<div
				class="group bg-white/10 backdrop-blur-lg rounded-2xl p-6 text-center hover:bg-white/20 transition-all duration-500 transform hover:scale-110 hover:-translate-y-2 shadow-xl border border-white/20">
				<div
					class="bg-white/20 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
					<i class="fas fa-hospital text-3xl text-indigo-600"></i>
				</div>
				<h3 class="text-4xl font-bold text-black counter mb-2 drop-shadow-md"
					data-target="{{ $clinicsCount ?? 0 }}">{{ $clinicsCount ?? 0 }}</h3>
				<p class="text-black font-semibold">عيادة</p>
			</div>

			<!-- Medical Labs -->
			<div
				class="group bg-white/10 backdrop-blur-lg rounded-2xl p-6 text-center hover:bg-white/20 transition-all duration-500 transform hover:scale-110 hover:-translate-y-2 shadow-xl border border-white/20">
				<div
					class="bg-white/20 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
					<i class="fas fa-flask text-3xl text-indigo-600"></i>
				</div>
				<h3 class="text-4xl font-bold text-black counter mb-2 drop-shadow-md"
					data-target="{{ $medicalLabsCount ?? 0 }}">
					{{ $medicalLabsCount ?? 0 }}</h3>
				<p class="text-black font-semibold">مختبر طبي</p>
			</div>

			<!-- Radiology Centers -->
			<div
				class="group bg-white/10 backdrop-blur-lg rounded-2xl p-6 text-center hover:bg-white/20 transition-all duration-500 transform hover:scale-110 hover:-translate-y-2 shadow-xl border border-white/20">
				<div
					class="bg-white/20 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
					<i class="fas fa-x-ray text-3xl text-indigo-600"></i>
				</div>
				<h3 class="text-4xl font-bold text-black counter mb-2 drop-shadow-md"
					data-target="{{ $radiologyCentersCount ?? 0 }}">
					{{ $radiologyCentersCount ?? 0 }}</h3>
				<p class="text-black font-semibold">مركز أشعة</p>
			</div>

			<!-- Doctors -->
			<div
				class="group bg-white/10 backdrop-blur-lg rounded-2xl p-6 text-center hover:bg-white/20 transition-all duration-500 transform hover:scale-110 hover:-translate-y-2 shadow-xl border border-white/20">
				<div
					class="bg-white/20 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
					<i class="fas fa-user-md text-3xl text-indigo-600"></i>
				</div>
				<h3 class="text-4xl font-bold text-black counter mb-2 drop-shadow-md"
					data-target="{{ $doctorsCount ?? 0 }}">{{ $doctorsCount ?? 0 }}</h3>
				<p class="text-black font-semibold">طبيب</p>
			</div>

			<!-- Patients -->
			<div
				class="group bg-white/10 backdrop-blur-lg rounded-2xl p-6 text-center hover:bg-white/20 transition-all duration-500 transform hover:scale-110 hover:-translate-y-2 shadow-xl border border-white/20">
				<div
					class="bg-white/20 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
					<i class="fas fa-users text-3xl text-indigo-600"></i>
				</div>
				<h3 class="text-4xl font-bold text-black counter mb-2 drop-shadow-md"
					data-target="{{ $patientsCount ?? 0 }}">{{ $patientsCount ?? 0 }}</h3>
				<p class="text-black font-semibold">مريض</p>
			</div>

			<!-- Reservations -->
			<div
				class="group bg-white/10 backdrop-blur-lg rounded-2xl p-6 text-center hover:bg-white/20 transition-all duration-500 transform hover:scale-110 hover:-translate-y-2 shadow-xl border border-white/20">
				<div
					class="bg-white/20 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
					<i class="fas fa-calendar-check text-3xl text-indigo-600"></i>
				</div>
				<h3 class="text-4xl font-bold text-black counter mb-2 drop-shadow-md"
					data-target="{{ $reservationsCount ?? 0 }}">
					{{ $reservationsCount ?? 0 }}</h3>
				<p class="text-black font-semibold">حجز</p>
			</div>
		</div>

		<!-- Trust Badge -->
		<div class="mt-12 text-center">
			<p class="text-black text-lg mb-4 font-medium">موثوق به من قبل</p>
			<div class="flex items-center justify-center gap-4 flex-wrap">
				<div
					class="group bg-white/10 backdrop-blur-lg rounded-xl px-6 py-3 border border-white/20 hover:bg-white/20 transition-all duration-300 transform hover:scale-105">
					<span class="text-black font-semibold">منظمات صحية</span>
				</div>
				<div
					class="group bg-white/10 backdrop-blur-lg rounded-xl px-6 py-3 border border-white/20 hover:bg-white/20 transition-all duration-300 transform hover:scale-105">
					<span class="text-black font-semibold">مستشفيات</span>
				</div>
				<div
					class="group bg-white/10 backdrop-blur-lg rounded-xl px-6 py-3 border border-white/20 hover:bg-white/20 transition-all duration-300 transform hover:scale-105">
					<span class="text-black font-semibold">عيادات خاصة</span>
				</div>
			</div>
		</div>
	</div>
</section>
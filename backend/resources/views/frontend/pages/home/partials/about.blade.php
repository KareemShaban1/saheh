<section id="about"
	class="py-20 bg-gradient-to-br from-gray-50 via-blue-50/30 to-indigo-50/30 relative overflow-hidden">
	<!-- Decorative Elements -->
	<!-- <div class="absolute top-0 right-0 w-96 h-96 bg-blue-200/30 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-pulse"></div> -->
	<!-- <div class="absolute bottom-0 left-0 w-96 h-96 bg-indigo-200/30 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-pulse"></div> -->

	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
		<div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
			<!-- Image Section -->
			<div class="order-2 lg:order-1">
				<div class="relative group">
					<div
						class="absolute -inset-4 bg-gradient-to-r from-blue-400 via-indigo-400 to-purple-400 rounded-3xl blur-2xl opacity-30 group-hover:opacity-50 transition-opacity duration-500">
					</div>
					<img src="{{ asset('frontend/home/image/about-img.svg') }}"
						alt="عن كاري كير"
						class="relative rounded-3xl shadow-2xl transform group-hover:scale-105 transition-transform duration-500">
					<!-- Floating Badge -->
					<div
						class="absolute -bottom-6 -right-6 bg-gradient-to-r from-blue-500 to-indigo-600 text-white p-6 rounded-2xl shadow-2xl transform rotate-6 group-hover:rotate-0 transition-transform duration-300 border-4 border-white">
						<div class="text-3xl font-bold">10+</div>
						<div class="text-sm">سنوات خبرة</div>
					</div>
				</div>
			</div>

			<!-- Content Section -->
			<div class="order-1 lg:order-2">
				<span
					class="inline-block bg-blue-100 text-blue-600 px-4 py-2 rounded-full text-sm font-semibold mb-4 shadow-sm">
					من نحن
				</span>
				<h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-6 leading-tight">
					نحن نهتم <span class="text-blue-600">بصحتك</span> وحياتك الصحية
				</h2>
				<h3 class="text-2xl font-semibold text-indigo-600 mb-6">
					نظام شامل لإدارة الرعاية الصحية
				</h3>
				<p class="text-lg text-gray-700 mb-6 leading-relaxed">
					<strong class="text-blue-600">كاري كير</strong> هو نظام متعدد
					المستأجرين متقدم مصمم خصيصاً لإدارة العيادات الطبية والمختبرات الطبية
					ومراكز الأشعة. يوفر النظام حلاً رقمياً كاملاً لإدارة العمليات الطبية
					مع الحفاظ على عزل كامل للبيانات بين المنظمات المختلفة.
				</p>
				<p class="text-lg text-gray-700 mb-8 leading-relaxed">
					باستخدام أحدث التقنيات مثل Laravel 10 و PHP 8.2+، نقدم نظاماً آمناً
					وقابلاً للتطوير مع واجهة برمجة تطبيقات RESTful كاملة لدعم التطبيقات
					المحمولة والتكامل مع أنظمة أخرى.
				</p>

				<!-- Features Grid -->
				<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
					<div
						class="group flex items-center bg-white p-4 rounded-xl shadow-md hover:shadow-xl transition-all duration-300 transform hover:scale-105 border border-gray-100">
						<div
							class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-lg flex items-center justify-center ml-4 group-hover:scale-110 group-hover:rotate-3 transition-all duration-300 shadow-md">
							<i class="fas fa-check text-white text-xl"></i>
						</div>
						<span
							class="text-gray-800 font-semibold group-hover:text-gray-900 transition-colors">معمارية
							متعددة المستأجرين</span>
					</div>
					<div
						class="group flex items-center bg-white p-4 rounded-xl shadow-md hover:shadow-xl transition-all duration-300 transform hover:scale-105 border border-gray-100">
						<div
							class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center ml-4 group-hover:scale-110 group-hover:rotate-3 transition-all duration-300 shadow-md">
							<i class="fas fa-check text-white text-xl"></i>
						</div>
						<span
							class="text-gray-800 font-semibold group-hover:text-gray-900 transition-colors">تواصل
							فوري</span>
					</div>
					<div
						class="group flex items-center bg-white p-4 rounded-xl shadow-md hover:shadow-xl transition-all duration-300 transform hover:scale-105 border border-gray-100">
						<div
							class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg flex items-center justify-center ml-4 group-hover:scale-110 group-hover:rotate-3 transition-all duration-300 shadow-md">
							<i class="fas fa-check text-white text-xl"></i>
						</div>
						<span
							class="text-gray-800 font-semibold group-hover:text-gray-900 transition-colors">إدارة
							بيانات آمنة</span>
					</div>
					<div
						class="group flex items-center bg-white p-4 rounded-xl shadow-md hover:shadow-xl transition-all duration-300 transform hover:scale-105 border border-gray-100">
						<div
							class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-600 rounded-lg flex items-center justify-center ml-4 group-hover:scale-110 group-hover:rotate-3 transition-all duration-300 shadow-md">
							<i class="fas fa-check text-white text-xl"></i>
						</div>
						<span
							class="text-gray-800 font-semibold group-hover:text-gray-900 transition-colors">متجاوب
							مع الجوال</span>
					</div>
				</div>

				<!-- CTA Buttons -->
				<div class="flex flex-col sm:flex-row gap-4">
					<a href="#contact"
						class="group bg-gradient-to-r from-blue-500 to-indigo-600 text-white px-8 py-4 rounded-xl font-semibold hover:from-blue-600 hover:to-indigo-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 text-center">
						<i
							class="fas fa-envelope ml-2 group-hover:scale-110 transition-transform"></i>
						تواصل معنا
					</a>
					<a href="#features"
						class="group bg-white border-2 border-blue-500 text-blue-600 px-8 py-4 rounded-xl font-semibold hover:bg-blue-50 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 text-center">
						<i
							class="fas fa-info-circle ml-2 group-hover:rotate-12 transition-transform"></i>
						اعرف المزيد
					</a>
				</div>
			</div>
		</div>
	</div>
</section>
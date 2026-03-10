<nav class="py-5 lg:fixed w-full bg-white/95 backdrop-blur-lg shadow-lg transition-all duration-500 z-50 border-b border-gray-100"
	id="navbar">
	<div class="mx-auto max-w-[85rem] px-4 sm:px-6 lg:px-8">
		<div class="w-full flex flex-col lg:flex-row items-center justify-between">
			<!-- Logo -->
			<div class="flex justify-between items-center w-full lg:w-auto">
				<a href=""
					class="flex items-center text-primary-color hover:text-primary-color-hover transition-colors group">
					<div
						class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center ml-2 group-hover:scale-110 group-hover:rotate-3 transition-all duration-300 shadow-lg">
						<i class="fas fa-heartbeat text-xl text-white"></i>
					</div>
					<span
						class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
						صحيح
					</span>
				</a>
				<button data-collapse-toggle="navbar" type="button"
					class="inline-flex items-center p-2 ml-3 text-sm text-gray-500 rounded-lg lg:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 transition-colors"
					aria-controls="navbar-default" aria-expanded="false">
					<span class="sr-only">فتح القائمة</span>
					<svg class="w-6 h-6" aria-hidden="true" fill="currentColor"
						viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd"
							d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
							clip-rule="evenodd"></path>
					</svg>
				</button>
			</div>

			<!-- Navigation Links -->
			<div class="hidden w-full lg:flex lg:pl-11 max-lg:py-4 mx-auto justify-center"
				id="navbar">
				<ul
					class="flex lg:items-center flex-col max-lg:gap-4 mt-4 lg:mt-0 lg:flex-row max-lg:mb-4">
					<li class="mx-2">
						<a href="#home"
							class="text-gray-700 text-base font-medium hover:text-blue-600 transition-all duration-300 mb-2 block lg:mr-6 md:mb-0 relative group">
							الرئيسية
							<span
								class="absolute bottom-0 right-0 w-0 h-0.5 bg-blue-600 group-hover:w-full transition-all duration-300"></span>
						</a>
					</li>
					<li class="mx-2">
						<a href="#about"
							class="text-gray-700 text-base font-medium hover:text-blue-600 transition-all duration-300 mb-2 block lg:mr-6 md:mb-0 relative group">
							من نحن
							<span
								class="absolute bottom-0 right-0 w-0 h-0.5 bg-blue-600 group-hover:w-full transition-all duration-300"></span>
						</a>
					</li>
					<li class="mx-2">
						<a href="#features"
							class="text-gray-700 text-base font-medium hover:text-blue-600 transition-all duration-300 mb-2 block lg:mr-6 md:mb-0 relative group">
							المميزات
							<span
								class="absolute bottom-0 right-0 w-0 h-0.5 bg-blue-600 group-hover:w-full transition-all duration-300"></span>
						</a>
					</li>
					<li class="mx-2">
						<a href="#services"
							class="text-gray-700 text-base font-medium hover:text-blue-600 transition-all duration-300 mb-2 block lg:mr-6 md:mb-0 relative group">
							الخدمات
							<span
								class="absolute bottom-0 right-0 w-0 h-0.5 bg-blue-600 group-hover:w-full transition-all duration-300"></span>
						</a>
					</li>

					<!-- Clinics Dropdown -->
					<div class="relative group mx-2">
						<button
							class="flex items-center text-gray-700 hover:text-blue-600 font-medium transition-colors">
							{{ trans('frontend/home.Clinics') }}
							<i
								class="fas fa-chevron-down mr-1 text-xs transition-transform group-hover:rotate-180"></i>
						</button>
						<div
							class="absolute top-full right-0 mt-2 w-56 bg-white rounded-xl shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 border border-gray-100">
							<div class="py-2">
								<a href="{{ route('clinics') }}"
									class="block px-4 py-3 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors rounded-lg mx-2">
									<i
										class="fas fa-hospital ml-2 text-blue-500"></i>
									{{ trans('frontend/home.All Clinics') }}
								</a>
								<a href="{{ route('register-clinic') }}"
									class="block px-4 py-3 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors rounded-lg mx-2">
									<i
										class="fas fa-user-plus ml-2 text-emerald-500"></i>
									{{ trans('frontend/home.Register Clinic') }}
								</a>
								<a href="/clinic/login"
									class="block px-4 py-3 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors rounded-lg mx-2">
									<i
										class="fas fa-sign-in-alt ml-2 text-indigo-500"></i>
									{{ trans('frontend/home.Login Clinic') }}
								</a>
							</div>
						</div>
					</div>

					<!-- Medical Laboratory Dropdown -->
					<div class="relative group mx-2">
						<button
							class="flex items-center text-gray-700 hover:text-blue-600 font-medium transition-colors">
							{{ trans('frontend/home.Medical Laboratory') }}
							<i
								class="fas fa-chevron-down mr-1 text-xs transition-transform group-hover:rotate-180"></i>
						</button>
						<div
							class="absolute top-full right-0 mt-2 w-56 bg-white rounded-xl shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 border border-gray-100">
							<div class="py-2">
								<a href="{{ route('medical-laboratories') }}"
									class="block px-4 py-3 text-sm text-gray-700 hover:bg-emerald-50 hover:text-emerald-600 transition-colors rounded-lg mx-2">
									<i
										class="fas fa-flask ml-2 text-emerald-500"></i>
									{{ trans('frontend/home.All Medical Laboratory') }}
								</a>
								<a href="{{ route('register-medical-laboratory') }}"
									class="block px-4 py-3 text-sm text-gray-700 hover:bg-emerald-50 hover:text-emerald-600 transition-colors rounded-lg mx-2">
									<i
										class="fas fa-user-plus ml-2 text-emerald-500"></i>
									{{ trans('frontend/home.Register Medical Laboratory') }}
								</a>
								<a href="/medical-laboratory/login"
									class="block px-4 py-3 text-sm text-gray-700 hover:bg-emerald-50 hover:text-emerald-600 transition-colors rounded-lg mx-2">
									<i
										class="fas fa-sign-in-alt ml-2 text-emerald-500"></i>
									{{ trans('frontend/home.Login Medical Laboratory') }}
								</a>
							</div>
						</div>
					</div>

					<!-- Radiology Center Dropdown -->
					<div class="relative group mx-2">
						<button
							class="flex items-center text-gray-700 hover:text-blue-600 font-medium transition-colors">
							{{ trans('frontend/home.Radiology Center') }}
							<i
								class="fas fa-chevron-down mr-1 text-xs transition-transform group-hover:rotate-180"></i>
						</button>
						<div
							class="absolute top-full right-0 mt-2 w-56 bg-white rounded-xl shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 border border-gray-100">
							<div class="py-2">
								<a href="{{ route('radiology-centers') }}"
									class="block px-4 py-3 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition-colors rounded-lg mx-2">
									<i
										class="fas fa-x-ray ml-2 text-purple-500"></i>
									{{ trans('frontend/home.All Radiology Center') }}
								</a>
								<a href="{{ route('register-radiology-center') }}"
									class="block px-4 py-3 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition-colors rounded-lg mx-2">
									<i
										class="fas fa-user-plus ml-2 text-purple-500"></i>
									{{ trans('frontend/home.Register Radiology Center') }}
								</a>
								<a href="/radiology-center/login"
									class="block px-4 py-3 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition-colors rounded-lg mx-2">
									<i
										class="fas fa-sign-in-alt ml-2 text-purple-500"></i>
									{{ trans('frontend/home.Login Radiology Center') }}
								</a>
							</div>
						</div>
					</div>

					<li class="mx-2">
						<a href="#contact"
							class="text-gray-700 text-base font-medium hover:text-blue-600 transition-all duration-300 mb-2 block lg:mr-6 md:mb-0 relative group">
							تواصل معنا
							<span
								class="absolute bottom-0 right-0 w-0 h-0.5 bg-blue-600 group-hover:w-full transition-all duration-300"></span>
						</a>
					</li>
				</ul>
			</div>

			<!-- Action Buttons -->
			<div
				class="flex lg:items-center justify-start flex-col lg:flex-row max-lg:gap-4 w-full lg:w-auto lg:justify-end">
				<a href="/patient/login"
					class="group bg-indigo-50 text-blue-600 rounded-full cursor-pointer font-semibold text-center shadow-md transition-all duration-500 py-3 px-3 text-sm hover:bg-indigo-100 hover:shadow-lg transform hover:scale-105 w-full lg:w-[150px]">
					<i
						class="fas fa-sign-in-alt ml-2 group-hover:translate-x-1 transition-transform"></i>
					تسجيل الدخول
				</a>
				<a href="/patient/register"
					class="group bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-full cursor-pointer font-semibold text-center shadow-md transition-all duration-500 py-3 px-3 text-sm hover:from-blue-600 hover:to-indigo-700 hover:shadow-lg transform hover:scale-105 lg:mr-5 w-full lg:w-[150px]">
					<i
						class="fas fa-user-plus ml-2 group-hover:rotate-12 transition-transform"></i>
					إنشاء حساب
				</a>
			</div>
		</div>
	</div>
</nav>
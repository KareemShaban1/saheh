<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Kari Care - Comprehensive Healthcare Management System</title>
	<!-- Page Icon -->
	<link rel="shortcut icon" href="{{ asset('frontend/assets/img/heartbeat-solid.svg') }}" type="image/x-icon">


	<!-- tailwind -->
	<script src="https://cdn.tailwindcss.com"></script>


	<!-- font-awesome -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
	<!-- Tailwind CSS -->
	@vite(['resources/css/app.css', 'resources/js/app.js'])

	<link rel="stylesheet" href="{{ asset('frontend/home/css/rtl_style.css') }}">

	<style>
	body {
		direction: rtl;
		font-family: 'Almarai', sans-serif;
	}

	@keyframes fadeInUp {
		from {
			opacity: 0;
			transform: translateY(30px);
		}
		to {
			opacity: 1;
			transform: translateY(0);
		}
	}

	@keyframes fadeIn {
		from {
			opacity: 0;
		}
		to {
			opacity: 1;
		}
	}

	@keyframes slideInRight {
		from {
			opacity: 0;
			transform: translateX(-30px);
		}
		to {
			opacity: 1;
			transform: translateX(0);
		}
	}

	@keyframes pulse {
		0%, 100% {
			transform: scale(1);
		}
		50% {
			transform: scale(1.05);
		}
	}

	@keyframes float {
		0%, 100% {
			transform: translateY(0px);
		}
		50% {
			transform: translateY(-20px);
		}
	}

	.animate-fadeInUp {
		animation: fadeInUp 0.6s ease-out;
	}

	.animate-fadeIn {
		animation: fadeIn 0.8s ease-out;
	}

	.animate-slideInRight {
		animation: slideInRight 0.6s ease-out;
	}

	.animate-pulse-slow {
		animation: pulse 2s infinite;
	}

	.animate-float {
		animation: float 3s ease-in-out infinite;
	}

	.animate-on-scroll {
		opacity: 0;
		transform: translateY(30px);
		transition: opacity 0.6s ease-out, transform 0.6s ease-out;
	}

	.animate-on-scroll.visible {
		opacity: 1;
		transform: translateY(0);
	}

	.gradient-bg {
		background: linear-gradient(135deg, #3B82F6 0%, #6366F1 100%);
	}

	.healthcare-gradient {
		background: linear-gradient(135deg, #3B82F6 0%, #6366F1 100%);
	}

	.medical-gradient {
		background: linear-gradient(135deg, #10B981 0%, #14B8A6 100%);
	}

	.scroll-smooth {
		scroll-behavior: smooth;
	}

	/* Custom scrollbar */
	::-webkit-scrollbar {
		width: 10px;
	}

	::-webkit-scrollbar-track {
		background: #f1f1f1;
	}

	::-webkit-scrollbar-thumb {
		background: linear-gradient(135deg, #3B82F6, #6366F1);
		border-radius: 5px;
	}

	::-webkit-scrollbar-thumb:hover {
		background: linear-gradient(135deg, #2563EB, #4F46E5);
	}

	/* Smooth transitions for all interactive elements */
	a, button {
		transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
	}

	/* Glassmorphism effect */
	.glass {
		background: rgba(255, 255, 255, 0.1);
		backdrop-filter: blur(10px);
		border: 1px solid rgba(255, 255, 255, 0.2);
	}
	</style>
</head>

<body class="scroll-smooth">
	<!-- Header Section Starts -->
	<!-- <header class="bg-white shadow-lg sticky top-0 z-50">
		<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
			<div class="flex justify-between items-center py-4">
				<div class="flex items-center">
					<a href="#home"
						class="flex items-center space-x-2 text-2xl font-bold text-[#16a085] hover:text-blue-700 transition-colors">
						<i class="fas fa-heartbeat text-red-500"></i>
						<span>Kari Care</span>
					</a>
				</div>

				<nav class="hidden lg:flex items-center space-x-8">
					<a href="#home"
						class="text-gray-700 hover:text-blue-600 font-medium transition-colors"
						style="margin-left: 10px;">{{ trans('frontend/home.Home') }}</a>
					<a href="#about"
						class="text-gray-700 hover:text-blue-600 font-medium transition-colors">{{ trans('frontend/home.About Us') }}</a>
					<a href="#services"
						class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Services</a>
					<a href="#features"
						class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Features</a>
					<a href="#pricing"
						class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Pricing</a>
					<a href="#contact"
						class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Contact</a>

					<div class="relative group">
						<button
							class="flex items-center text-gray-700 hover:text-blue-600 font-medium transition-colors">
							{{ trans('frontend/home.Clinics') }} <i
								class="fas fa-chevron-down ml-1 text-xs"></i>
						</button>
						<div
							class="absolute top-full left-0 mt-2 w-48 bg-white rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300">
							<div class="py-2">
								<a href="{{ route('clinics') }}"
									class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">{{ trans('frontend/home.All Clinics') }}</a>
								<a href="{{ route('register-clinic') }}"
									class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">{{ trans('frontend/home.Register Clinic') }}</a>
								<a href="/clinic/login"
									class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">{{ trans('frontend/home.Login Clinic') }}</a>
							</div>
						</div>
					</div>

					<div class="relative group">
						<button
							class="flex items-center text-gray-700 hover:text-blue-600 font-medium transition-colors">
							{{ trans('frontend/home.Medical Laboratory') }} <i
								class="fas fa-chevron-down ml-1 text-xs"></i>
						</button>
						<div
							class="absolute top-full left-0 mt-2 w-48 bg-white rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300">
							<div class="py-2">
								<a href="#"
									class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">{{ trans('frontend/home.All Medical Laboratory') }}</a>
								<a href="{{ route('register-medical-laboratory') }}"
									class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">{{ trans('frontend/home.Register Medical Laboratory') }}</a>
								<a href="/medical-laboratory/login"
									class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">{{ trans('frontend/home.Login Medical Laboratory') }}</a>
							</div>
						</div>
					</div>

					<div class="relative group">
						<button
							class="flex items-center text-gray-700 hover:text-blue-600 font-medium transition-colors">
							{{ trans('frontend/home.Radiology Center') }} <i
								class="fas fa-chevron-down ml-1 text-xs"></i>
						</button>
						<div
							class="absolute top-full left-0 mt-2 w-48 bg-white rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300">
							<div class="py-2">
								<a href="#"
									class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">{{ trans('frontend/home.All Radiology Center') }}</a>
								<a href="{{ route('register-radiology-center') }}"
									class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">{{ trans('frontend/home.Register Radiology Center') }}</a>
								<a href="/radiology-center/login"
									class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">{{ trans('frontend/home.Login Radiology Center') }}</a>
							</div>
						</div>
					</div>
				</nav>

				<div class="lg:hidden">
					<button id="mobile-menu-btn"
						class="text-gray-700 hover:text-blue-600 focus:outline-none">
						<i class="fas fa-bars text-xl"></i>
					</button>
				</div>
			</div>

			<div id="mobile-menu" class="lg:hidden hidden">
				<div class="px-2 pt-2 pb-3 space-y-1 bg-white border-t">
					<a href="#home"
						class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-blue-600">{{ trans('frontend/home.Home') }}</a>
					<a href="#about"
						class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-blue-600">{{ trans('frontend/home.About Us') }}</a>
					<a href="#services"
						class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-blue-600">Services</a>
					<a href="#features"
						class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-blue-600">Features</a>
					<a href="#pricing"
						class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-blue-600">Pricing</a>
					<a href="#contact"
						class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-blue-600">Contact</a>

					<div class="relative">
						<button type="button"
							class="flex w-full items-center justify-between px-3 py-2 text-base font-medium text-gray-700 hover:text-blue-600"
							data-dropdown-toggle="clinicsDropdown">
							{{ trans('frontend/home.Clinics') }}
							<i class="fas fa-chevron-down ml-1 text-xs"></i>
						</button>
						<div id="clinicsDropdown"
							class="hidden mt-1 ml-4 border-l pl-3 space-y-1 transition-all">
							<a href="{{ route('clinics') }}"
								class="block px-2 py-1 text-sm text-gray-700 hover:text-blue-600">{{ trans('frontend/home.All Clinics') }}</a>
							<a href="{{ route('register-clinic') }}"
								class="block px-2 py-1 text-sm text-gray-700 hover:text-blue-600">{{ trans('frontend/home.Register Clinic') }}</a>
							<a href="/clinic/login"
								class="block px-2 py-1 text-sm text-gray-700 hover:text-blue-600">{{ trans('frontend/home.Login Clinic') }}</a>
						</div>
					</div>

					<div class="relative">
						<button type="button"
							class="flex w-full items-center justify-between px-3 py-2 text-base font-medium text-gray-700 hover:text-blue-600"
							data-dropdown-toggle="labDropdown">
							{{ trans('frontend/home.Medical Laboratory') }}
							<i class="fas fa-chevron-down ml-1 text-xs"></i>
						</button>
						<div id="labDropdown"
							class="hidden mt-1 ml-4 border-l pl-3 space-y-1 transition-all">
							<a href="#"
								class="block px-2 py-1 text-sm text-gray-700 hover:text-blue-600">{{ trans('frontend/home.All Medical Laboratory') }}</a>
							<a href="{{ route('register-medical-laboratory') }}"
								class="block px-2 py-1 text-sm text-gray-700 hover:text-blue-600">{{ trans('frontend/home.Register Medical Laboratory') }}</a>
							<a href="/medical-laboratory/login"
								class="block px-2 py-1 text-sm text-gray-700 hover:text-blue-600">{{ trans('frontend/home.Login Medical Laboratory') }}</a>
						</div>
					</div>

					<div class="relative">
						<button type="button"
							class="flex w-full items-center justify-between px-3 py-2 text-base font-medium text-gray-700 hover:text-blue-600"
							data-dropdown-toggle="radiologyDropdown">
							{{ trans('frontend/home.Radiology Center') }}
							<i class="fas fa-chevron-down ml-1 text-xs"></i>
						</button>
						<div id="radiologyDropdown"
							class="hidden mt-1 ml-4 border-l pl-3 space-y-1 transition-all">
							<a href="#"
								class="block px-2 py-1 text-sm text-gray-700 hover:text-blue-600">{{ trans('frontend/home.All Radiology Center') }}</a>
							<a href="{{ route('register-radiology-center') }}"
								class="block px-2 py-1 text-sm text-gray-700 hover:text-blue-600">{{ trans('frontend/home.Register Radiology Center') }}</a>
							<a href="/radiology-center/login"
								class="block px-2 py-1 text-sm text-gray-700 hover:text-blue-600">{{ trans('frontend/home.Login Radiology Center') }}</a>
						</div>
					</div>

				</div>
			</div>
		</div>
	</header> -->
	<!-- Header Section End -->

	@include('frontend.layouts.nav')

	<main>
		@yield('content')
	</main>

	<!-- Footer section Starts  -->
	<footer class="bg-gray-900 text-white">
		<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
			<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
				<div>
					<h3 class="text-lg font-semibold mb-4 text-blue-400">
						{{ trans('frontend/home.Quick_Links') }}
					</h3>
					<ul class="space-y-2">
						<li><a href="#home"
								class="text-gray-300 hover:text-white transition-colors flex items-center"><i
									class="fas fa-chevron-left mr-2 text-xs"></i>
								{{ trans('frontend/home.Home') }}</a>
						</li>
						<li><a href="#about"
								class="text-gray-300 hover:text-white transition-colors flex items-center"><i
									class="fas fa-chevron-left mr-2 text-xs"></i>
								{{ trans('frontend/home.About Us') }}</a>
						</li>
						<li><a href="#services"
								class="text-gray-300 hover:text-white transition-colors flex items-center"><i
									class="fas fa-chevron-left mr-2 text-xs"></i>
								Services</a></li>
						<li><a href="#features"
								class="text-gray-300 hover:text-white transition-colors flex items-center"><i
									class="fas fa-chevron-left mr-2 text-xs"></i>
								Features</a></li>
						<li><a href="#contact"
								class="text-gray-300 hover:text-white transition-colors flex items-center"><i
									class="fas fa-chevron-left mr-2 text-xs"></i>
								Contact</a></li>
					</ul>
				</div>

				<div>
					<h3 class="text-lg font-semibold mb-4 text-blue-400">
						{{ trans('frontend/home.Our_Services') }}
					</h3>
					<ul class="space-y-2">
						<li><a href="#"
								class="text-gray-300 hover:text-white transition-colors flex items-center"><i
									class="fas fa-chevron-left mr-2 text-xs"></i>
								{{ trans('frontend/home.Analysis') }}</a>
						</li>
						<li><a href="#"
								class="text-gray-300 hover:text-white transition-colors flex items-center"><i
									class="fas fa-chevron-left mr-2 text-xs"></i>
								{{ trans('frontend/home.Rays') }}</a>
						</li>
						<li><a href="#"
								class="text-gray-300 hover:text-white transition-colors flex items-center"><i
									class="fas fa-chevron-left mr-2 text-xs"></i>
								Clinic Management</a></li>
						<li><a href="#"
								class="text-gray-300 hover:text-white transition-colors flex items-center"><i
									class="fas fa-chevron-left mr-2 text-xs"></i>
								Patient Records</a></li>
						<li><a href="#"
								class="text-gray-300 hover:text-white transition-colors flex items-center"><i
									class="fas fa-chevron-left mr-2 text-xs"></i>
								Appointment Booking</a></li>
					</ul>
				</div>

				<!-- Contact Info -->
				<div>
					<h3 class="text-lg font-semibold mb-4 text-blue-400">
						{{ trans('frontend/home.Contact_Info') }}
					</h3>
					<ul class="space-y-2">
						<li class="flex items-center text-gray-300 gap-3"><i
								class="fas fa-phone mr-3 text-blue-400"></i>
							+20 111 111 1111</li>
						<li class="flex items-center text-gray-300 gap-3"><i
								class="fas fa-envelope mr-3 text-blue-400"></i>
							info@karicare.com</li>
						<li class="flex items-center text-gray-300 gap-3"><i
								class="fas fa-map-marker-alt mr-3 text-blue-400"></i>
							Egypt, Middle East</li>
						<li class="flex items-center text-gray-300 gap-3"><i
								class="fas fa-clock mr-3 text-blue-400"></i>
							24/7 Support</li>
					</ul>
				</div>

				<!-- Follow Us -->
				<div>
					<h3 class="text-lg font-semibold mb-4 text-blue-400">
						{{ trans('frontend/home.Follow_Us') }}
					</h3>
					<div class="flex space-x-4 mb-4">
						<a href="#"
							class="text-gray-300 hover:text-blue-400 transition-colors"><i
								class="fab fa-facebook-f text-xl"></i></a>
						<a href="#"
							class="text-gray-300 hover:text-blue-400 transition-colors"><i
								class="fab fa-twitter text-xl"></i></a>
						<a href="#"
							class="text-gray-300 hover:text-blue-400 transition-colors"><i
								class="fab fa-linkedin text-xl"></i></a>
						<a href="#"
							class="text-gray-300 hover:text-blue-400 transition-colors"><i
								class="fab fa-instagram text-xl"></i></a>
					</div>
					<p class="text-gray-300 text-sm">Stay connected with us for the latest
						updates and healthcare news.</p>
				</div>
			</div>

			<!-- Bottom Footer -->
			<div class="border-t border-gray-700 mt-8 pt-8">
				<div class="flex flex-col md:flex-row justify-between items-center">
					<div class="text-gray-300 text-sm">
						<p>&copy; {{ date('Y') }} Kari Care. All rights reserved.
						</p>
					</div>
					<div class="flex space-x-6 mt-4 md:mt-0">
						<a href="#"
							class="text-gray-300 hover:text-white text-sm transition-colors">Privacy
							Policy</a>
						<a href="#"
							class="text-gray-300 hover:text-white text-sm transition-colors">Terms
							of Service</a>
						<a href="#"
							class="text-gray-300 hover:text-white text-sm transition-colors">Cookie
							Policy</a>
					</div>
				</div>
			</div>
		</div>
	</footer>
	<!-- Footer section End  -->











	<!-- JavaScript -->
	<script>
	// Mobile menu toggle
	document.getElementById('mobile-menu-btn').addEventListener('click', function() {
		const mobileMenu = document.getElementById('mobile-menu');
		mobileMenu.classList.toggle('hidden');
	});

	// Smooth scrolling for anchor links
	document.querySelectorAll('a[href^="#"]').forEach(anchor => {
		anchor.addEventListener('click', function(e) {
			e.preventDefault();
			const target = document.querySelector(this.getAttribute(
				'href'));
			if (target) {
				target.scrollIntoView({
					behavior: 'smooth',
					block: 'start'
				});
			}
		});
	});

	// Counter animation
	function animateCounters() {
		const counters = document.querySelectorAll('.counter');
		counters.forEach(counter => {
			const target = parseInt(counter.getAttribute('data-target'));
			const duration = 2000; // 2 seconds
			const increment = target / (duration / 16); // 60fps
			let current = 0;

			const timer = setInterval(() => {
				current += increment;
				if (current >= target) {
					current = target;
					clearInterval(timer);
				}
				counter.textContent = Math.floor(current);
			}, 16);
		});
	}

	// Intersection Observer for scroll animations
	const observerOptions = {
		threshold: 0.1,
		rootMargin: '0px 0px -50px 0px'
	};

	const observer = new IntersectionObserver((entries) => {
		entries.forEach(entry => {
			if (entry.isIntersecting) {
				entry.target.classList.add('visible', 'animate-fadeInUp');
				if (entry.target.classList.contains('counter')) {
					animateCounters();
				}
				// Unobserve after animation to prevent re-triggering
				observer.unobserve(entry.target);
			}
		});
	}, observerOptions);

	// Observe elements for animation
	document.addEventListener('DOMContentLoaded', function() {
		const animatedElements = document.querySelectorAll('.animate-on-scroll');
		animatedElements.forEach(el => observer.observe(el));
		
		// Navbar scroll effect
		const navbar = document.getElementById('navbar');
		if (navbar) {
			window.addEventListener('scroll', function() {
				if (window.scrollY > 50) {
					navbar.classList.add('shadow-xl');
					navbar.classList.remove('shadow-lg');
				} else {
					navbar.classList.remove('shadow-xl');
					navbar.classList.add('shadow-lg');
				}
			});
		}
	});

	document.addEventListener('DOMContentLoaded', function() {
		document.querySelectorAll('[data-dropdown-toggle]').forEach(button => {
			button.addEventListener('click', () => {
				const targetId = button
					.getAttribute(
						'data-dropdown-toggle'
					);
				const target = document
					.getElementById(
						targetId
					);

				// Close other open dropdowns (optional)
				document.querySelectorAll(
						'#mobile-menu [id$="Dropdown"]'
					)
					.forEach(dropdown => {
						if (dropdown
							.id !==
							targetId
						)
							dropdown
							.classList
							.add(
								'hidden'
							);
					});

				// Toggle this one
				target.classList.toggle(
					'hidden');
			});
		});
	});
	</script>

	@stack('scripts')
</body>

</html>
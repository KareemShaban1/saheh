@extends('frontend.layouts.app')

@section('content')

<!-- Radiology Center Hero Section -->
<section class="bg-gradient-to-r from-indigo-600 to-indigo-800 py-20">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
		<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-center">
			<div class="lg:col-span-2">
				<div class="flex items-center mb-4">
					@if($radiologyCenter->logo)
					<img src="{{ asset('storage/' . $radiologyCenter->logo) }}"
						alt="{{ $radiologyCenter->name }}"
						class="w-16 h-16 rounded-full mr-4">
					@else
					<div
						class="w-16 h-16 bg-white rounded-full flex items-center justify-center mr-4">
						<i class="fas fa-x-ray text-2xl text-indigo-600"></i>
					</div>
					@endif
					<div>
						<h1 class="text-4xl font-bold text-white">
							{{ $radiologyCenter->name }}</h1>
						<p class="text-indigo-100">Advanced Medical Imaging Services
						</p>
					</div>
				</div>
				<p class="text-xl text-indigo-100 mb-6">{{ $radiologyCenter->description }}</p>
				<div class="flex flex-wrap gap-4">
					<div class="flex items-center text-white">
						<i class="fas fa-map-marker-alt mr-2"></i>
						<span>{{ $radiologyCenter->address }},
							{{ $radiologyCenter->area->name ?? '' }},
							{{ $radiologyCenter->city->name ?? '' }}</span>
					</div>
					<div class="flex items-center text-white">
						<i class="fas fa-phone mr-2"></i>
						<span>{{ $radiologyCenter->phone }}</span>
					</div>
					<div class="flex items-center text-white">
						<i class="fas fa-envelope mr-2"></i>
						<span>{{ $radiologyCenter->email }}</span>
					</div>
				</div>
			</div>
			<div class="text-center">
				<div class="bg-white/10 backdrop-blur-sm rounded-lg p-6">
					<h3 class="text-white text-lg font-semibold mb-4">Quick Actions</h3>
					<div class="space-y-3">
						<a href="#services"
							class="block w-full bg-white text-indigo-600 py-3 px-6 rounded-lg font-semibold hover:bg-indigo-50 transition-colors">
							<i class="fas fa-x-ray mr-2"></i> View Services
						</a>
						<a href="#contact"
							class="block w-full border-2 border-white text-white py-3 px-6 rounded-lg font-semibold hover:bg-white hover:text-indigo-600 transition-colors">
							<i class="fas fa-phone mr-2"></i> Contact Center
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<!-- Radiology Center Information Section -->
<section class="py-16">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
		<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
			<!-- Main Information -->
			<div class="lg:col-span-2">
				<div class="bg-white rounded-xl shadow-lg p-8 mb-8">
					<h2 class="text-3xl font-bold text-gray-900 mb-6">About
						{{ $radiologyCenter->name }}</h2>
					<p class="text-gray-600 leading-relaxed mb-6">
						{{ $radiologyCenter->description }}</p>

					<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
						<div class="flex items-center">
							<div
								class="bg-indigo-100 w-12 h-12 rounded-lg flex items-center justify-center mr-4">
								<i
									class="fas fa-x-ray text-indigo-600"></i>
							</div>
							<div>
								<h4 class="font-semibold text-gray-900">
									Center Type</h4>
								<p class="text-gray-600">Radiology
									Center</p>
							</div>
						</div>
						<div class="flex items-center">
							<div
								class="bg-green-100 w-12 h-12 rounded-lg flex items-center justify-center mr-4">
								<i
									class="fas fa-calendar text-green-600"></i>
							</div>
							<div>
								<h4 class="font-semibold text-gray-900">
									Established</h4>
								<p class="text-gray-600">
									{{ $radiologyCenter->start_date ? \Carbon\Carbon::parse($radiologyCenter->start_date)->format('Y') : 'N/A' }}
								</p>
							</div>
						</div>
						<div class="flex items-center">
							<div
								class="bg-blue-100 w-12 h-12 rounded-lg flex items-center justify-center mr-4">
								<i
									class="fas fa-clock text-blue-600"></i>
							</div>
							<div>
								<h4 class="font-semibold text-gray-900">
									Status</h4>
								<p class="text-gray-600">
									{{ $radiologyCenter->status ? 'Active' : 'Inactive' }}
								</p>
							</div>
						</div>
						<div class="flex items-center">
							<div
								class="bg-yellow-100 w-12 h-12 rounded-lg flex items-center justify-center mr-4">
								<i
									class="fas fa-certificate text-yellow-600"></i>
							</div>
							<div>
								<h4 class="font-semibold text-gray-900">
									Accreditation</h4>
								<p class="text-gray-600">Certified
									Imaging Center</p>
							</div>
						</div>
					</div>
				</div>

				<!-- Services Section -->
				<div class="bg-white rounded-xl shadow-lg p-8 mb-8">
					<h2 class="text-3xl font-bold text-gray-900 mb-6">Imaging Services
					</h2>
					<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
						<div class="space-y-4">
							<h3
								class="text-xl font-semibold text-gray-900 mb-4">
								X-Ray Services</h3>
							<div class="space-y-2">
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Chest
										X-Ray</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Bone
										X-Ray</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Dental
										X-Ray</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Abdominal
										X-Ray</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Spine
										X-Ray</span>
								</div>
							</div>
						</div>
						<div class="space-y-4">
							<h3
								class="text-xl font-semibold text-gray-900 mb-4">
								CT Scan</h3>
							<div class="space-y-2">
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Head
										CT</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Chest
										CT</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Abdominal
										CT</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Pelvic
										CT</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">CT
										Angiography</span>
								</div>
							</div>
						</div>
						<div class="space-y-4">
							<h3
								class="text-xl font-semibold text-gray-900 mb-4">
								MRI Services</h3>
							<div class="space-y-2">
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Brain
										MRI</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Spine
										MRI</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Joint
										MRI</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Abdominal
										MRI</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Cardiac
										MRI</span>
								</div>
							</div>
						</div>
						<div class="space-y-4">
							<h3
								class="text-xl font-semibold text-gray-900 mb-4">
								Ultrasound</h3>
							<div class="space-y-2">
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Abdominal
										Ultrasound</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Pelvic
										Ultrasound</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Cardiac
										Echo</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Doppler
										Studies</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Pregnancy
										Ultrasound</span>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Equipment & Technology -->
				<div class="bg-white rounded-xl shadow-lg p-8">
					<h2 class="text-3xl font-bold text-gray-900 mb-6">Advanced Imaging
						Equipment</h2>
					<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
						<div class="text-center">
							<div
								class="bg-indigo-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
								<i
									class="fas fa-x-ray text-2xl text-indigo-600"></i>
							</div>
							<h3 class="font-semibold text-gray-900 mb-2">
								Digital X-Ray</h3>
							<p class="text-gray-600 text-sm">High-resolution
								digital X-ray systems for clear imaging
							</p>
						</div>
						<div class="text-center">
							<div
								class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
								<i
									class="fas fa-cube text-2xl text-blue-600"></i>
							</div>
							<h3 class="font-semibold text-gray-900 mb-2">CT
								Scanner</h3>
							<p class="text-gray-600 text-sm">Multi-slice CT
								scanners for detailed cross-sectional
								imaging</p>
						</div>
						<div class="text-center">
							<div
								class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
								<i
									class="fas fa-magnet text-2xl text-green-600"></i>
							</div>
							<h3 class="font-semibold text-gray-900 mb-2">MRI
								Scanner</h3>
							<p class="text-gray-600 text-sm">High-field MRI
								systems for soft tissue imaging</p>
						</div>
					</div>
				</div>
			</div>

			<!-- Sidebar -->
			<div class="space-y-8">
				<!-- Contact Information -->
				<div class="bg-white rounded-xl shadow-lg p-6">
					<h3 class="text-xl font-bold text-gray-900 mb-4">Contact Information
					</h3>
					<div class="space-y-4">
						<div class="flex items-center">
							<i class="fas fa-phone text-indigo-600 mr-3"></i>
							<span
								class="text-gray-700">{{ $radiologyCenter->phone }}</span>
						</div>
						<div class="flex items-center">
							<i
								class="fas fa-envelope text-indigo-600 mr-3"></i>
							<span
								class="text-gray-700">{{ $radiologyCenter->email }}</span>
						</div>
						<div class="flex items-start">
							<i
								class="fas fa-map-marker-alt text-indigo-600 mr-3 mt-1"></i>
							<div>
								<p class="text-gray-700">
									{{ $radiologyCenter->address }}
								</p>
								<p class="text-gray-600 text-sm">
									{{ $radiologyCenter->area->name ?? '' }},
									{{ $radiologyCenter->city->name ?? '' }}
								</p>
								<p class="text-gray-600 text-sm">
									{{ $radiologyCenter->governorate->name ?? '' }}
								</p>
							</div>
						</div>
						@if($radiologyCenter->website)
						<div class="flex items-center">
							<i class="fas fa-globe text-indigo-600 mr-3"></i>
							<a href="{{ $radiologyCenter->website }}"
								target="_blank"
								class="text-indigo-600 hover:text-indigo-700">{{ $radiologyCenter->website }}</a>
						</div>
						@endif
					</div>
				</div>

				<!-- Operating Hours -->
				<div class="bg-white rounded-xl shadow-lg p-6">
					<h3 class="text-xl font-bold text-gray-900 mb-4">Operating Hours</h3>
					<div class="space-y-3">
						<div class="flex justify-between">
							<span class="text-gray-700">Monday - Friday</span>
							<span class="text-gray-600">7:00 AM - 8:00
								PM</span>
						</div>
						<div class="flex justify-between">
							<span class="text-gray-700">Saturday</span>
							<span class="text-gray-600">8:00 AM - 4:00
								PM</span>
						</div>
						<div class="flex justify-between">
							<span class="text-gray-700">Sunday</span>
							<span class="text-gray-600">Emergency Only</span>
						</div>
					</div>
				</div>

				<!-- Services -->
				<div class="bg-white rounded-xl shadow-lg p-6">
					<h3 class="text-xl font-bold text-gray-900 mb-4">Quick Services</h3>
					<div class="space-y-3">
						<div class="flex items-center">
							<i class="fas fa-check text-green-500 mr-3"></i>
							<span class="text-gray-700">X-Ray Imaging</span>
						</div>
						<div class="flex items-center">
							<i class="fas fa-check text-green-500 mr-3"></i>
							<span class="text-gray-700">CT Scans</span>
						</div>
						<div class="flex items-center">
							<i class="fas fa-check text-green-500 mr-3"></i>
							<span class="text-gray-700">MRI Scans</span>
						</div>
						<div class="flex items-center">
							<i class="fas fa-check text-green-500 mr-3"></i>
							<span class="text-gray-700">Ultrasound</span>
						</div>
						<div class="flex items-center">
							<i class="fas fa-check text-green-500 mr-3"></i>
							<span class="text-gray-700">Digital Reports</span>
						</div>
					</div>
				</div>

				<!-- Reviews -->
				@if($radiologyCenter->reviews->count() > 0)
				<div class="bg-white rounded-xl shadow-lg p-6">
					<h3 class="text-xl font-bold text-gray-900 mb-4">Patient Reviews</h3>
					<div class="space-y-4">
						@foreach($radiologyCenter->reviews->take(3) as $review)
						<div class="border-l-4 border-indigo-500 pl-4">
							<div class="flex items-center mb-2">
								<div class="flex text-yellow-400 mr-2">
									@for($i = 0; $i < 5; $i++) <i
										class="fas fa-star">
										</i>
										@endfor
								</div>
								<span
									class="text-sm text-gray-600">{{ $review->created_at->format('M d, Y') }}</span>
							</div>
							<p class="text-gray-700 text-sm">
								{{ $review->comment }}</p>
						</div>
						@endforeach
					</div>
				</div>
				@endif
			</div>
		</div>
	</div>
</section>

<!-- Services Section -->
<section id="services" class="bg-gray-50 py-16">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
		<div class="text-center mb-12">
			<h2 class="text-3xl font-bold text-gray-900 mb-4">Our Imaging Services</h2>
			<p class="text-xl text-gray-600">Advanced diagnostic imaging with state-of-the-art
				technology</p>
		</div>
		<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
			<div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
				<div
					class="bg-indigo-100 w-16 h-16 rounded-full flex items-center justify-center mb-4">
					<i class="fas fa-x-ray text-2xl text-indigo-600"></i>
				</div>
				<h3 class="text-xl font-semibold text-gray-900 mb-2">X-Ray Services</h3>
				<p class="text-gray-600 mb-4">Digital X-ray imaging for bones, chest, and soft
					tissues with immediate results.</p>
				<ul class="space-y-1 text-sm text-gray-600">
					<li>• Chest X-Ray</li>
					<li>• Bone X-Ray</li>
					<li>• Dental X-Ray</li>
					<li>• Abdominal X-Ray</li>
				</ul>
			</div>
			<div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
				<div
					class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mb-4">
					<i class="fas fa-cube text-2xl text-blue-600"></i>
				</div>
				<h3 class="text-xl font-semibold text-gray-900 mb-2">CT Scans</h3>
				<p class="text-gray-600 mb-4">Multi-slice CT scanning for detailed
					cross-sectional imaging of internal organs.</p>
				<ul class="space-y-1 text-sm text-gray-600">
					<li>• Head CT</li>
					<li>• Chest CT</li>
					<li>• Abdominal CT</li>
					<li>• CT Angiography</li>
				</ul>
			</div>
			<div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
				<div
					class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mb-4">
					<i class="fas fa-magnet text-2xl text-green-600"></i>
				</div>
				<h3 class="text-xl font-semibold text-gray-900 mb-2">MRI Scans</h3>
				<p class="text-gray-600 mb-4">High-field MRI imaging for soft tissues, brain,
					and musculoskeletal system.</p>
				<ul class="space-y-1 text-sm text-gray-600">
					<li>• Brain MRI</li>
					<li>• Spine MRI</li>
					<li>• Joint MRI</li>
					<li>• Cardiac MRI</li>
				</ul>
			</div>
		</div>
	</div>
</section>

<!-- Contact Section -->
<section id="contact" class="py-16">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
		<div class="text-center mb-12">
			<h2 class="text-3xl font-bold text-gray-900 mb-4">Contact {{ $radiologyCenter->name }}
			</h2>
			<p class="text-xl text-gray-600">Get in touch for imaging services and appointments</p>
		</div>
		<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
			<div class="bg-white rounded-xl shadow-lg p-8">
				<h3 class="text-2xl font-bold text-gray-900 mb-6">Contact Information</h3>
				<div class="space-y-6">
					<div class="flex items-center">
						<div
							class="bg-indigo-100 w-12 h-12 rounded-lg flex items-center justify-center mr-4">
							<i class="fas fa-phone text-indigo-600"></i>
						</div>
						<div>
							<h4 class="font-semibold text-gray-900">Phone</h4>
							<p class="text-gray-600">
								{{ $radiologyCenter->phone }}</p>
						</div>
					</div>
					<div class="flex items-center">
						<div
							class="bg-blue-100 w-12 h-12 rounded-lg flex items-center justify-center mr-4">
							<i class="fas fa-envelope text-blue-600"></i>
						</div>
						<div>
							<h4 class="font-semibold text-gray-900">Email</h4>
							<p class="text-gray-600">
								{{ $radiologyCenter->email }}</p>
						</div>
					</div>
					<div class="flex items-start">
						<div
							class="bg-green-100 w-12 h-12 rounded-lg flex items-center justify-center mr-4 mt-1">
							<i
								class="fas fa-map-marker-alt text-green-600"></i>
						</div>
						<div>
							<h4 class="font-semibold text-gray-900">Address
							</h4>
							<p class="text-gray-600">
								{{ $radiologyCenter->address }}</p>
							<p class="text-gray-600">
								{{ $radiologyCenter->area->name ?? '' }},
								{{ $radiologyCenter->city->name ?? '' }}
							</p>
							<p class="text-gray-600">
								{{ $radiologyCenter->governorate->name ?? '' }}
							</p>
						</div>
					</div>
				</div>
			</div>
			<div class="bg-white rounded-xl shadow-lg p-8">
				<h3 class="text-2xl font-bold text-gray-900 mb-6">Send us a Message</h3>
				<form class="space-y-4">
					<div>
						<label
							class="block text-sm font-medium text-gray-700 mb-2">Name</label>
						<input type="text"
							class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
					</div>
					<div>
						<label
							class="block text-sm font-medium text-gray-700 mb-2">Email</label>
						<input type="email"
							class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
					</div>
					<div>
						<label
							class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
						<input type="text"
							class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
					</div>
					<div>
						<label
							class="block text-sm font-medium text-gray-700 mb-2">Message</label>
						<textarea rows="4"
							class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></textarea>
					</div>
					<button type="submit"
						class="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 transition-colors">
						Send Message
					</button>
				</form>
			</div>
		</div>
	</div>
</section>

@endsection
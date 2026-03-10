@extends('frontend.layouts.app')

@section('content')

<!-- Medical Laboratory Hero Section -->
<section class="bg-gradient-to-r from-purple-600 to-purple-800 py-20">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
		<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-center">
			<div class="lg:col-span-2">
				<div class="flex items-center mb-4">
					@if($medicalLaboratory->logo)
					<img src="{{ asset('storage/' . $medicalLaboratory->logo) }}"
						alt="{{ $medicalLaboratory->name }}"
						class="w-16 h-16 rounded-full mr-4">
					@else
					<div
						class="w-16 h-16 bg-white rounded-full flex items-center justify-center mr-4">
						<i class="fas fa-flask text-2xl text-purple-600"></i>
					</div>
					@endif
					<div>
						<h1 class="text-4xl font-bold text-white">
							{{ $medicalLaboratory->name }}</h1>
						<p class="text-purple-100">Advanced Medical Laboratory
							Services</p>
					</div>
				</div>
				<p class="text-xl text-purple-100 mb-6">{{ $medicalLaboratory->description }}
				</p>
				<div class="flex flex-wrap gap-4">
					<div class="flex items-center text-white">
						<i class="fas fa-map-marker-alt mr-2"></i>
						<span>{{ $medicalLaboratory->address }},
							{{ $medicalLaboratory->area->name ?? '' }},
							{{ $medicalLaboratory->city->name ?? '' }}</span>
					</div>
					<div class="flex items-center text-white">
						<i class="fas fa-phone mr-2"></i>
						<span>{{ $medicalLaboratory->phone }}</span>
					</div>
					<div class="flex items-center text-white">
						<i class="fas fa-envelope mr-2"></i>
						<span>{{ $medicalLaboratory->email }}</span>
					</div>
				</div>
			</div>
			<div class="text-center">
				<div class="bg-white/10 backdrop-blur-sm rounded-lg p-6">
					<h3 class="text-white text-lg font-semibold mb-4">Quick Actions</h3>
					<div class="space-y-3">
						<a href="#services"
							class="block w-full bg-white text-purple-600 py-3 px-6 rounded-lg font-semibold hover:bg-purple-50 transition-colors">
							<i class="fas fa-flask mr-2"></i> View Services
						</a>
						<a href="#contact"
							class="block w-full border-2 border-white text-white py-3 px-6 rounded-lg font-semibold hover:bg-white hover:text-purple-600 transition-colors">
							<i class="fas fa-phone mr-2"></i> Contact Lab
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<!-- Laboratory Information Section -->
<section class="py-16">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
		<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
			<!-- Main Information -->
			<div class="lg:col-span-2">
				<div class="bg-white rounded-xl shadow-lg p-8 mb-8">
					<h2 class="text-3xl font-bold text-gray-900 mb-6">About
						{{ $medicalLaboratory->name }}</h2>
					<p class="text-gray-600 leading-relaxed mb-6">
						{{ $medicalLaboratory->description }}</p>

					<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
						<div class="flex items-center">
							<div
								class="bg-purple-100 w-12 h-12 rounded-lg flex items-center justify-center mr-4">
								<i
									class="fas fa-flask text-purple-600"></i>
							</div>
							<div>
								<h4 class="font-semibold text-gray-900">
									Laboratory Type</h4>
								<p class="text-gray-600">Medical
									Laboratory</p>
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
									{{ $medicalLaboratory->start_date ? \Carbon\Carbon::parse($medicalLaboratory->start_date)->format('Y') : 'N/A' }}
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
									{{ $medicalLaboratory->status ? 'Active' : 'Inactive' }}
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
									Laboratory</p>
							</div>
						</div>
					</div>
				</div>

				<!-- Services Section -->
				<div class="bg-white rounded-xl shadow-lg p-8 mb-8">
					<h2 class="text-3xl font-bold text-gray-900 mb-6">Laboratory Services
					</h2>
					<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
						<div class="space-y-4">
							<h3
								class="text-xl font-semibold text-gray-900 mb-4">
								Clinical Chemistry</h3>
							<div class="space-y-2">
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Blood
										Glucose</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Lipid
										Profile</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Liver
										Function
										Tests</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Kidney
										Function
										Tests</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span
										class="text-gray-700">Electrolytes</span>
								</div>
							</div>
						</div>
						<div class="space-y-4">
							<h3
								class="text-xl font-semibold text-gray-900 mb-4">
								Hematology</h3>
							<div class="space-y-2">
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Complete
										Blood Count</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Blood
										Typing</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Coagulation
										Studies</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Hemoglobin
										Analysis</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">ESR
										& CRP</span>
								</div>
							</div>
						</div>
						<div class="space-y-4">
							<h3
								class="text-xl font-semibold text-gray-900 mb-4">
								Microbiology</h3>
							<div class="space-y-2">
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Urine
										Culture</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Blood
										Culture</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Stool
										Analysis</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Sputum
										Culture</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Antibiotic
										Sensitivity</span>
								</div>
							</div>
						</div>
						<div class="space-y-4">
							<h3
								class="text-xl font-semibold text-gray-900 mb-4">
								Immunology</h3>
							<div class="space-y-2">
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Hormone
										Assays</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Tumor
										Markers</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Autoimmune
										Tests</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Allergy
										Testing</span>
								</div>
								<div class="flex items-center">
									<i
										class="fas fa-check text-green-500 mr-3"></i>
									<span class="text-gray-700">Pregnancy
										Tests</span>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Equipment & Technology -->
				<div class="bg-white rounded-xl shadow-lg p-8">
					<h2 class="text-3xl font-bold text-gray-900 mb-6">Advanced Equipment &
						Technology</h2>
					<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
						<div class="text-center">
							<div
								class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
								<i
									class="fas fa-microscope text-2xl text-purple-600"></i>
							</div>
							<h3 class="font-semibold text-gray-900 mb-2">
								Automated Analyzers</h3>
							<p class="text-gray-600 text-sm">State-of-the-art
								automated systems for accurate results
							</p>
						</div>
						<div class="text-center">
							<div
								class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
								<i
									class="fas fa-thermometer-half text-2xl text-blue-600"></i>
							</div>
							<h3 class="font-semibold text-gray-900 mb-2">
								Quality Control</h3>
							<p class="text-gray-600 text-sm">Rigorous quality
								control measures for reliable results
							</p>
						</div>
						<div class="text-center">
							<div
								class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
								<i
									class="fas fa-shield-alt text-2xl text-green-600"></i>
							</div>
							<h3 class="font-semibold text-gray-900 mb-2">
								Safety Standards</h3>
							<p class="text-gray-600 text-sm">Highest safety
								and hygiene standards maintained</p>
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
							<i class="fas fa-phone text-purple-600 mr-3"></i>
							<span
								class="text-gray-700">{{ $medicalLaboratory->phone }}</span>
						</div>
						<div class="flex items-center">
							<i
								class="fas fa-envelope text-purple-600 mr-3"></i>
							<span
								class="text-gray-700">{{ $medicalLaboratory->email }}</span>
						</div>
						<div class="flex items-start">
							<i
								class="fas fa-map-marker-alt text-purple-600 mr-3 mt-1"></i>
							<div>
								<p class="text-gray-700">
									{{ $medicalLaboratory->address }}
								</p>
								<p class="text-gray-600 text-sm">
									{{ $medicalLaboratory->area->name ?? '' }},
									{{ $medicalLaboratory->city->name ?? '' }}
								</p>
								<p class="text-gray-600 text-sm">
									{{ $medicalLaboratory->governorate->name ?? '' }}
								</p>
							</div>
						</div>
						@if($medicalLaboratory->website)
						<div class="flex items-center">
							<i class="fas fa-globe text-purple-600 mr-3"></i>
							<a href="{{ $medicalLaboratory->website }}"
								target="_blank"
								class="text-purple-600 hover:text-purple-700">{{ $medicalLaboratory->website }}</a>
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
							<span class="text-gray-600">8:00 AM - 6:00
								PM</span>
						</div>
						<div class="flex justify-between">
							<span class="text-gray-700">Saturday</span>
							<span class="text-gray-600">8:00 AM - 2:00
								PM</span>
						</div>
						<div class="flex justify-between">
							<span class="text-gray-700">Sunday</span>
							<span class="text-gray-600">Closed</span>
						</div>
					</div>
				</div>

				<!-- Services -->
				<div class="bg-white rounded-xl shadow-lg p-6">
					<h3 class="text-xl font-bold text-gray-900 mb-4">Quick Services</h3>
					<div class="space-y-3">
						<div class="flex items-center">
							<i class="fas fa-check text-green-500 mr-3"></i>
							<span class="text-gray-700">Blood Tests</span>
						</div>
						<div class="flex items-center">
							<i class="fas fa-check text-green-500 mr-3"></i>
							<span class="text-gray-700">Urine Analysis</span>
						</div>
						<div class="flex items-center">
							<i class="fas fa-check text-green-500 mr-3"></i>
							<span class="text-gray-700">Microbiology
								Tests</span>
						</div>
						<div class="flex items-center">
							<i class="fas fa-check text-green-500 mr-3"></i>
							<span class="text-gray-700">Hormone Testing</span>
						</div>
						<div class="flex items-center">
							<i class="fas fa-check text-green-500 mr-3"></i>
							<span class="text-gray-700">Rapid Results</span>
						</div>
					</div>
				</div>

				<!-- Reviews -->
				@if($medicalLaboratory->reviews->count() > 0)
				<div class="bg-white rounded-xl shadow-lg p-6">
					<h3 class="text-xl font-bold text-gray-900 mb-4">Patient Reviews</h3>
					<div class="space-y-4">
						@foreach($medicalLaboratory->reviews->take(3) as $review)
						<div class="border-l-4 border-purple-500 pl-4">
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
			<h2 class="text-3xl font-bold text-gray-900 mb-4">Our Laboratory Services</h2>
			<p class="text-xl text-gray-600">Comprehensive diagnostic services with accurate and
				timely results</p>
		</div>
		<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
			<div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
				<div
					class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mb-4">
					<i class="fas fa-tint text-2xl text-purple-600"></i>
				</div>
				<h3 class="text-xl font-semibold text-gray-900 mb-2">Blood Tests</h3>
				<p class="text-gray-600 mb-4">Complete blood count, biochemistry, and
					specialized blood tests with fast turnaround times.</p>
				<ul class="space-y-1 text-sm text-gray-600">
					<li>• Complete Blood Count (CBC)</li>
					<li>• Lipid Profile</li>
					<li>• Liver Function Tests</li>
					<li>• Kidney Function Tests</li>
				</ul>
			</div>
			<div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
				<div
					class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mb-4">
					<i class="fas fa-vial text-2xl text-blue-600"></i>
				</div>
				<h3 class="text-xl font-semibold text-gray-900 mb-2">Urine Analysis</h3>
				<p class="text-gray-600 mb-4">Comprehensive urine testing for routine checkups
					and specialized diagnostics.</p>
				<ul class="space-y-1 text-sm text-gray-600">
					<li>• Routine Urine Analysis</li>
					<li>• Urine Culture</li>
					<li>• 24-Hour Urine Collection</li>
					<li>• Pregnancy Tests</li>
				</ul>
			</div>
			<div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
				<div
					class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mb-4">
					<i class="fas fa-bacteria text-2xl text-green-600"></i>
				</div>
				<h3 class="text-xl font-semibold text-gray-900 mb-2">Microbiology</h3>
				<p class="text-gray-600 mb-4">Advanced microbiological testing for infections
					and bacterial identification.</p>
				<ul class="space-y-1 text-sm text-gray-600">
					<li>• Blood Culture</li>
					<li>• Stool Analysis</li>
					<li>• Sputum Culture</li>
					<li>• Antibiotic Sensitivity</li>
				</ul>
			</div>
		</div>
	</div>
</section>

<!-- Contact Section -->
<section id="contact" class="py-16">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
		<div class="text-center mb-12">
			<h2 class="text-3xl font-bold text-gray-900 mb-4">Contact {{ $medicalLaboratory->name }}
			</h2>
			<p class="text-xl text-gray-600">Get in touch for laboratory services and appointments</p>
		</div>
		<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
			<div class="bg-white rounded-xl shadow-lg p-8">
				<h3 class="text-2xl font-bold text-gray-900 mb-6">Contact Information</h3>
				<div class="space-y-6">
					<div class="flex items-center">
						<div
							class="bg-purple-100 w-12 h-12 rounded-lg flex items-center justify-center mr-4">
							<i class="fas fa-phone text-purple-600"></i>
						</div>
						<div>
							<h4 class="font-semibold text-gray-900">Phone</h4>
							<p class="text-gray-600">
								{{ $medicalLaboratory->phone }}</p>
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
								{{ $medicalLaboratory->email }}</p>
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
								{{ $medicalLaboratory->address }}</p>
							<p class="text-gray-600">
								{{ $medicalLaboratory->area->name ?? '' }},
								{{ $medicalLaboratory->city->name ?? '' }}
							</p>
							<p class="text-gray-600">
								{{ $medicalLaboratory->governorate->name ?? '' }}
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
							class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
					</div>
					<div>
						<label
							class="block text-sm font-medium text-gray-700 mb-2">Email</label>
						<input type="email"
							class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
					</div>
					<div>
						<label
							class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
						<input type="text"
							class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
					</div>
					<div>
						<label
							class="block text-sm font-medium text-gray-700 mb-2">Message</label>
						<textarea rows="4"
							class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"></textarea>
					</div>
					<button type="submit"
						class="w-full bg-purple-600 text-white py-3 rounded-lg font-semibold hover:bg-purple-700 transition-colors">
						Send Message
					</button>
				</form>
			</div>
		</div>
	</div>
</section>

@endsection
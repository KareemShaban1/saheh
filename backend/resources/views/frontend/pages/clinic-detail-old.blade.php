@extends('frontend.layouts.app')

@section('content')

<!-- Clinic Hero Section -->
<section class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-20">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
		<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-center mt-10">
			<div class="lg:col-span-2">
				<div class="flex items-center gap-4 mb-4">
					@if($clinic->logo)
					<img src="{{ asset('storage/' . $clinic->logo) }}"
						alt="{{ $clinic->name }}"
						class="w-16 h-16 rounded-full mr-4">
					@else
					<div
						class="w-16 h-16 bg-white rounded-full flex items-center justify-center mr-4">
						<i class="fas fa-hospital text-2xl text-blue-600"></i>
					</div>
					@endif
					<div>
						<h1 class="text-4xl font-bold text-white">
							{{ $clinic->name }}</h1>
						<p class="text-white">
							{{ $clinic->specialty->name ?? 'General Clinic' }}
						</p>
					</div>
				</div>
				<p class="text-xl text-white mb-6">{{ $clinic->description }}</p>
				<div class="flex flex-wrap gap-4">
					<div class="flex items-center text-white gap-2">
						<i class="fas fa-map-marker-alt mr-2"></i>
						<span>{{ $clinic->address }},
							{{ $clinic->area->name ?? '' }},
							{{ $clinic->city->name ?? '' }}</span>
					</div>
					<div class="flex items-center text-white gap-2">
						<i class="fas fa-phone mr-2"></i>
						<span>{{ $clinic->phone }}</span>
					</div>
					<div class="flex items-center text-white gap-2">
						<i class="fas fa-envelope mr-2"></i>
						<span>{{ $clinic->email }}</span>
					</div>
				</div>
			</div>
			<div class="text-center">
				<div class="bg-white/10 backdrop-blur-sm rounded-lg p-6">
					<h3 class="text-white text-lg font-semibold mb-4">Quick Actions</h3>
					<div class="space-y-3">
						<a href="#appointment"
							class="block w-full bg-white text-blue-600 py-3 px-6 rounded-lg font-semibold hover:bg-blue-50 transition-colors">
							<i class="fas fa-calendar-plus mr-2"></i> Book
							Appointment
						</a>
						<a href="#contact"
							class="block w-full border-2 border-white text-white py-3 px-6 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition-colors">
							<i class="fas fa-phone mr-2"></i> Contact Clinic
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<!-- Clinic Information Section -->
<section class="py-16">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
		<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
			<!-- Main Information -->
			<div class="lg:col-span-2">
				<div class="bg-white/10 backdrop-blur-sm rounded-xl shadow-lg p-8 mb-8">
					<h2 class="text-3xl font-bold text-gray-900 mb-6">About
						{{ $clinic->name }}</h2>
					<p class="text-gray-600 leading-relaxed mb-6">
						{{ $clinic->description }}</p>

					<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
						<div class="flex items-center gap-4">
							<div
								class="bg-blue-100 w-12 h-12 rounded-lg flex items-center justify-center mr-4">
								<i
									class="fas fa-user-md text-blue-600"></i>
							</div>
							<div>
								<h4 class="font-semibold text-gray-900">
									specialty</h4>
								<p class="text-gray-600">
									{{ $clinic->specialty->name ?? 'General Practice' }}
								</p>
							</div>
						</div>
						<div class="flex items-center gap-4">
							<div
								class="bg-green-100 w-12 h-12 rounded-lg flex items-center justify-center mr-4">
								<i
									class="fas fa-calendar text-green-600"></i>
							</div>
							<div>
								<h4 class="font-semibold text-gray-900">
									Established</h4>
								<p class="text-gray-600">
									{{ $clinic->start_date ? \Carbon\Carbon::parse($clinic->start_date)->format('Y') : 'N/A' }}
								</p>
							</div>
						</div>
						<div class="flex items-center gap-4">
							<div
								class="bg-purple-100 w-12 h-12 rounded-lg flex items-center justify-center mr-4">
								<i
									class="fas fa-users text-purple-600"></i>
							</div>
							<div>
								<h4 class="font-semibold text-gray-900">
									Doctors</h4>
								<p class="text-gray-600">
									{{ $clinic->doctors->count() }}
									Available</p>
							</div>
						</div>
						<div class="flex items-center gap-4">
							<div
								class="bg-yellow-100 w-12 h-12 rounded-lg flex items-center justify-center mr-4">
								<i
									class="fas fa-clock text-yellow-600"></i>
							</div>
							<div>
								<h4 class="font-semibold text-gray-900">
									Status</h4>
								<p class="text-gray-600">
									{{ $clinic->is_active ? 'Active' : 'Inactive' }}
								</p>
							</div>
						</div>
					</div>
				</div>

				<!-- Doctors Section -->
				@if($clinic->doctors->count() > 0)
				<div class="bg-white/10 backdrop-blur-sm rounded-xl shadow-lg p-8">
					<h2 class="text-3xl font-bold text-gray-900 mb-6">Our Doctors</h2>
					<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
						@foreach($clinic->doctors as $doctor)
						<div
							class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
							<div class="flex items-center gap-4 mb-4">
								<div
									class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mr-4">
									<i
										class="fas fa-user-md text-2xl text-blue-600"></i>
								</div>
								<div>
									<h3
										class="text-xl font-semibold text-gray-900">
										{{ $doctor->user->name }}
									</h3>
									<p class="text-gray-600">
										{{ $doctor->specialty->name ?? 'General Practice' }}
									</p>
								</div>
							</div>
							<div
								class="flex items-center text-gray-600 gap-2 mb-2">
								<i class="fas fa-phone mr-2"></i>
								<span>{{ $doctor->phone }}</span>
							</div>
							<div
								class="flex items-center text-gray-600 gap-2">
								<i class="fas fa-envelope mr-2"></i>
								<span>{{ $doctor->user->email }}</span>
							</div>
							<div class="mt-4">
								<a href="{{ route('doctor.detail', $doctor->id) }}"
									class="text-blue-600 hover:text-blue-700 font-semibold">
									View Profile <i
										class="fas fa-arrow-left ml-1"></i>
								</a>
							</div>
						</div>
						@endforeach
					</div>
				</div>
				@endif
			</div>

			<!-- Sidebar -->
			<div class="space-y-8">
				<!-- Contact Information -->
				<div class="bg-white/10 backdrop-blur-sm rounded-xl shadow-lg p-6">
					<h3 class="text-xl font-bold text-gray-900 mb-4">Contact Information
					</h3>
					<div class="space-y-4">
						<div class="flex items-center gap-2">
							<i class="fas fa-phone text-blue-600 mr-3"></i>
							<span
								class="text-gray-700">{{ $clinic->phone }}</span>
						</div>
						<div class="flex items-center gap-2">
							<i class="fas fa-envelope text-blue-600 mr-3"></i>
							<span
								class="text-gray-700">{{ $clinic->email }}</span>
						</div>
						<div class="flex items-start gap-2">
							<i
								class="fas fa-map-marker-alt text-blue-600 mr-3 mt-1"></i>
							<div>
								<p class="text-gray-700">
									{{ $clinic->address }}</p>
								<p class="text-gray-600 text-sm">
									{{ $clinic->area->name ?? '' }},
									{{ $clinic->city->name ?? '' }}
								</p>
								<p class="text-gray-600 text-sm">
									{{ $clinic->governorate->name ?? '' }}
								</p>
							</div>
						</div>
						@if($clinic->website)
						<div class="flex items-center">
							<i class="fas fa-globe text-blue-600 mr-3"></i>
							<a href="{{ $clinic->website }}" target="_blank"
								class="text-blue-600 hover:text-blue-700">{{ $clinic->website }}</a>
						</div>
						@endif
					</div>
				</div>

				<!-- Services -->
				<div class="bg-white/10 backdrop-blur-sm rounded-xl shadow-lg p-6">
					<h3 class="text-xl font-bold text-gray-900 mb-4">Services</h3>
					<div class="space-y-3">
						<div class="flex items-center">
							<i class="fas fa-check text-green-500 mr-3"></i>
							<span class="text-gray-700">General
								Consultation</span>
						</div>
						<div class="flex items-center">
							<i class="fas fa-check text-green-500 mr-3"></i>
							<span class="text-gray-700">Appointment
								Booking</span>
						</div>
						<div class="flex items-center">
							<i class="fas fa-check text-green-500 mr-3"></i>
							<span class="text-gray-700">Medical Records</span>
						</div>
						<div class="flex items-center">
							<i class="fas fa-check text-green-500 mr-3"></i>
							<span class="text-gray-700">Prescription
								Management</span>
						</div>
						<div class="flex items-center">
							<i class="fas fa-check text-green-500 mr-3"></i>
							<span class="text-gray-700">Follow-up Care</span>
						</div>
					</div>
				</div>

				<!-- Reviews -->
				@if($clinic->reviews->count() > 0)
				<div class="bg-white/10 backdrop-blur-sm rounded-xl shadow-lg p-6">
					<h3 class="text-xl font-bold text-gray-900 mb-4">Patient Reviews</h3>
					<div class="space-y-4">
						@foreach($clinic->reviews->take(3) as $review)
						<div class="border-l-4 border-blue-500 pl-4">
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

<!-- Appointment Booking Section -->
<section id="appointment" class="bg-gray-50 py-16">
	<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
		<div class="text-center mb-12">
			<h2 class="text-3xl font-bold text-gray-900 mb-4">Book an Appointment</h2>
			<p class="text-xl text-gray-600">Schedule your visit with our experienced doctors</p>
		</div>
		<div class="bg-white/10 backdrop-blur-sm rounded-xl shadow-lg p-8">
			<form class="space-y-6">
				<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
					<div>
						<label class="block text-sm font-medium text-gray-700 mb-2">Full
							Name</label>
						<input type="text"
							class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
							placeholder="Enter your full name">
					</div>
					<div>
						<label class="block text-sm font-medium text-gray-700 mb-2">Phone
							Number</label>
						<input type="tel"
							class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
							placeholder="Enter your phone number">
					</div>
				</div>
				<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
					<div>
						<label
							class="block text-sm font-medium text-gray-700 mb-2">Email</label>
						<input type="email"
							class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
							placeholder="Enter your email">
					</div>
					<div>
						<label class="block text-sm font-medium text-gray-700 mb-2">Preferred
							Doctor</label>
						<select
							class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
							<option value="">Select a doctor</option>
							@foreach($clinic->doctors as $doctor)
							<option value="{{ $doctor->id }}">
								{{ $doctor->user->name }} -
								{{ $doctor->specialty->name ?? 'General Practice' }}
							</option>
							@endforeach
						</select>
					</div>
				</div>
				<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
					<div>
						<label class="block text-sm font-medium text-gray-700 mb-2">Preferred
							Date</label>
						<input type="date"
							class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
					</div>
					<div>
						<label class="block text-sm font-medium text-gray-700 mb-2">Preferred
							Time</label>
						<select
							class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
							<option value="">Select time</option>
							<option value="09:00">9:00 AM</option>
							<option value="10:00">10:00 AM</option>
							<option value="11:00">11:00 AM</option>
							<option value="14:00">2:00 PM</option>
							<option value="15:00">3:00 PM</option>
							<option value="16:00">4:00 PM</option>
						</select>
					</div>
				</div>
				<div>
					<label class="block text-sm font-medium text-gray-700 mb-2">Reason for
						Visit</label>
					<textarea rows="4"
						class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
						placeholder="Describe your symptoms or reason for the appointment"></textarea>
				</div>
				<div class="text-center">
					<button type="submit"
						class="bg-blue-600 text-black px-8 py-4 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
						<i class="fas fa-calendar-plus mr-2"></i> Book Appointment
					</button>
				</div>
			</form>
		</div>
	</div>
</section>

<!-- Contact Section -->
<section id="contact" class="py-16">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
		<div class="text-center mb-12">
			<h2 class="text-3xl font-bold text-gray-900 mb-4">Get in Touch</h2>
			<p class="text-xl text-gray-600">We're here to help with your healthcare needs</p>
		</div>
		<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
			<div class="bg-white/10 backdrop-blur-sm rounded-xl shadow-lg p-8">
				<h3 class="text-2xl font-bold text-gray-900 mb-6">Contact Information</h3>
				<div class="space-y-6">
					<div class="flex items-center">
						<div
							class="bg-blue-100 w-12 h-12 rounded-lg flex items-center justify-center mr-4">
							<i class="fas fa-phone text-blue-600"></i>
						</div>
						<div>
							<h4 class="font-semibold text-gray-900">Phone</h4>
							<p class="text-gray-600">{{ $clinic->phone }}</p>
						</div>
					</div>
					<div class="flex items-center">
						<div
							class="bg-green-100 w-12 h-12 rounded-lg flex items-center justify-center mr-4">
							<i class="fas fa-envelope text-green-600"></i>
						</div>
						<div>
							<h4 class="font-semibold text-gray-900">Email</h4>
							<p class="text-gray-600">{{ $clinic->email }}</p>
						</div>
					</div>
					<div class="flex items-start">
						<div
							class="bg-purple-100 w-12 h-12 rounded-lg flex items-center justify-center mr-4 mt-1">
							<i
								class="fas fa-map-marker-alt text-purple-600"></i>
						</div>
						<div>
							<h4 class="font-semibold text-gray-900">Address
							</h4>
							<p class="text-gray-600">{{ $clinic->address }}
							</p>
							<p class="text-gray-600">
								{{ $clinic->area->name ?? '' }},
								{{ $clinic->city->name ?? '' }}</p>
							<p class="text-gray-600">
								{{ $clinic->governorate->name ?? '' }}
							</p>
						</div>
					</div>
				</div>
			</div>
			<div class="bg-white/10 backdrop-blur-sm rounded-xl shadow-lg p-8">
				<h3 class="text-2xl font-bold text-gray-900 mb-6">Send us a Message</h3>
				<form class="space-y-4">
					<div>
						<label
							class="block text-sm font-medium text-gray-700 mb-2">Name</label>
						<input type="text"
							class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
					</div>
					<div>
						<label
							class="block text-sm font-medium text-gray-700 mb-2">Email</label>
						<input type="email"
							class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
					</div>
					<div>
						<label
							class="block text-sm font-medium text-gray-700 mb-2">Message</label>
						<textarea rows="4"
							class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
					</div>
					<button type="submit"
						class="w-full bg-blue-600 text-black py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
						Send Message
					</button>
				</form>
			</div>
		</div>
	</div>
</section>

@endsection
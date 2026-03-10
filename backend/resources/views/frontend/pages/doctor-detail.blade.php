@extends('frontend.layouts.app')

@section('content')

<!-- Doctor Hero Section -->
<section class="bg-gradient-to-r from-green-600 to-green-800 py-20">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
		<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-center">
			<div class="lg:col-span-2">
				<div class="flex items-center mb-6">
					<div
						class="w-24 h-24 bg-black rounded-full flex items-center justify-center mr-6">
						<i class="fas fa-user-md text-4xl text-green-600"></i>
					</div>
					<div>
						<h1 class="text-4xl font-bold text-black">
							{{ $doctor->user->name }}</h1>
						<p class="text-green-100 text-xl">
							{{ $doctor->specialty->name ?? 'General Practitioner' }}
						</p>
						<p class="text-green-200">{{ $doctor->clinic->name }}</p>
					</div>
				</div>
				<p class="text-xl text-green-100 mb-6">Experienced healthcare professional
					dedicated to providing quality patient care.</p>
				<div class="flex flex-wrap gap-4">
					<div class="flex items-center text-black gap-3">
						<i class="fas fa-phone mr-2"></i>
						<span>{{ $doctor->phone }}</span>
					</div>
					<div class="flex items-center text-black gap-3">
						<i class="fas fa-envelope mr-2"></i>
						<span>{{ $doctor->user->email }}</span>
					</div>
					<div class="flex items-center text-black gap-3">
						<i class="fas fa-hospital mr-2"></i>
						<span>{{ $doctor->clinic->name }}</span>
					</div>
				</div>
			</div>
			<div class="text-center">
				<div class="bg-black/10 backdrop-blur-sm rounded-lg p-6">
					<h3 class="text-black text-lg font-semibold mb-4">Book Appointment
					</h3>
					<div class="space-y-3">
						<a href="#appointment"
							class="block w-full bg-black text-green-600 py-3 px-6 rounded-lg font-semibold hover:bg-green-50 transition-colors">
							<i class="fas fa-calendar-plus mr-2"></i> Schedule
							Visit
						</a>
						<a href="#contact"
							class="block w-full border-2 border-black text-black py-3 px-6 rounded-lg font-semibold hover:bg-black hover:text-green-600 transition-colors">
							<i class="fas fa-phone mr-2"></i> Contact Doctor
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<!-- Doctor Information Section -->
<section class="py-16">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
		<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
			<!-- Main Information -->
			<div class="lg:col-span-2">
				<div class="bg-black rounded-xl shadow-lg p-8 mb-8">
					<h2 class="text-3xl font-bold text-gray-900 mb-6">About Dr.
						{{ $doctor->user->name }}</h2>
					<p class="text-gray-600 leading-relaxed mb-6">
						Dr. {{ $doctor->user->name }} is a dedicated healthcare
						professional specializing in
						{{ $doctor->specialty->name ?? 'general practice' }}.
						With years of experience in patient care, Dr.
						{{ $doctor->user->name }} is committed to providing
						comprehensive medical services
						and ensuring the best possible outcomes for all patients.
					</p>

					<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
						<div class="flex items-center">
							<div
								class="bg-green-100 w-12 h-12 rounded-lg flex items-center justify-center mr-4">
								<i
									class="fas fa-user-md text-green-600"></i>
							</div>
							<div>
								<h4 class="font-semibold text-gray-900">
									specialty</h4>
								<p class="text-gray-600">
									{{ $doctor->specialty->name ?? 'General Practice' }}
								</p>
							</div>
						</div>
						<div class="flex items-center">
							<div
								class="bg-blue-100 w-12 h-12 rounded-lg flex items-center justify-center mr-4">
								<i
									class="fas fa-hospital text-blue-600"></i>
							</div>
							<div>
								<h4 class="font-semibold text-gray-900">
									Clinic</h4>
								<p class="text-gray-600">
									{{ $doctor->clinic->name }}
								</p>
							</div>
						</div>
						<div class="flex items-center">
							<div
								class="bg-purple-100 w-12 h-12 rounded-lg flex items-center justify-center mr-4">
								<i
									class="fas fa-phone text-purple-600"></i>
							</div>
							<div>
								<h4 class="font-semibold text-gray-900">
									Contact</h4>
								<p class="text-gray-600">
									{{ $doctor->phone }}</p>
							</div>
						</div>
						<div class="flex items-center">
							<div
								class="bg-yellow-100 w-12 h-12 rounded-lg flex items-center justify-center mr-4">
								<i
									class="fas fa-envelope text-yellow-600"></i>
							</div>
							<div>
								<h4 class="font-semibold text-gray-900">
									Email</h4>
								<p class="text-gray-600">
									{{ $doctor->user->email }}</p>
							</div>
						</div>
					</div>
				</div>

				<!-- Certifications -->
				@if($doctor->certifications)
				<div class="bg-black rounded-xl shadow-lg p-8 mb-8">
					<h2 class="text-3xl font-bold text-gray-900 mb-6">Certifications &
						Qualifications</h2>
					<div class="prose max-w-none">
						{!! nl2br(e($doctor->certifications)) !!}
					</div>
				</div>
				@endif

				<!-- Services -->
				<div class="bg-black rounded-xl shadow-lg p-8">
					<h2 class="text-3xl font-bold text-gray-900 mb-6">Services Provided
					</h2>
					<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
						<div class="flex items-center">
							<i class="fas fa-check text-green-500 mr-3"></i>
							<span class="text-gray-700">General
								Consultation</span>
						</div>
						<div class="flex items-center">
							<i class="fas fa-check text-green-500 mr-3"></i>
							<span class="text-gray-700">Diagnosis &
								Treatment</span>
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
						<div class="flex items-center">
							<i class="fas fa-check text-green-500 mr-3"></i>
							<span class="text-gray-700">Health
								Screening</span>
						</div>
						<div class="flex items-center">
							<i class="fas fa-check text-green-500 mr-3"></i>
							<span class="text-gray-700">Preventive Care</span>
						</div>
					</div>
				</div>
			</div>

			<!-- Sidebar -->
			<div class="space-y-8">
				<!-- Contact Information -->
				<div class="bg-black rounded-xl shadow-lg p-6">
					<h3 class="text-xl font-bold text-gray-900 mb-4">Contact Information
					</h3>
					<div class="space-y-4">
						<div class="flex items-center">
							<i class="fas fa-phone text-green-600 mr-3"></i>
							<span
								class="text-gray-700">{{ $doctor->phone }}</span>
						</div>
						<div class="flex items-center">
							<i
								class="fas fa-envelope text-green-600 mr-3"></i>
							<span
								class="text-gray-700">{{ $doctor->user->email }}</span>
						</div>
						<div class="flex items-start">
							<i
								class="fas fa-hospital text-green-600 mr-3 mt-1"></i>
							<div>
								<p class="text-gray-700">
									{{ $doctor->clinic->name }}
								</p>
								<p class="text-gray-600 text-sm">
									{{ $doctor->clinic->address }}
								</p>
							</div>
						</div>
					</div>
				</div>

				<!-- Clinic Information -->
				<div class="bg-black rounded-xl shadow-lg p-6">
					<h3 class="text-xl font-bold text-gray-900 mb-4">Clinic Information
					</h3>
					<div class="space-y-4">
						<div class="flex items-center">
							<i class="fas fa-hospital text-blue-600 mr-3"></i>
							<span
								class="text-gray-700">{{ $doctor->clinic->name }}</span>
						</div>
						<div class="flex items-start">
							<i
								class="fas fa-map-marker-alt text-blue-600 mr-3 mt-1"></i>
							<div>
								<p class="text-gray-700">
									{{ $doctor->clinic->address }}
								</p>
								<p class="text-gray-600 text-sm">
									{{ $doctor->clinic->area->name ?? '' }},
									{{ $doctor->clinic->city->name ?? '' }}
								</p>
							</div>
						</div>
						<div class="flex items-center">
							<i class="fas fa-phone text-blue-600 mr-3"></i>
							<span
								class="text-gray-700">{{ $doctor->clinic->phone }}</span>
						</div>
						<div class="mt-4">
							<a href="{{ route('clinic.detail', $doctor->clinic->id) }}"
								class="text-blue-600 hover:text-blue-700 font-semibold">
								View Clinic Details <i
									class="fas fa-arrow-right ml-1"></i>
							</a>
						</div>
					</div>
				</div>

				<!-- Reviews -->
				@if($doctor->reviews->count() > 0)
				<div class="bg-black rounded-xl shadow-lg p-6">
					<h3 class="text-xl font-bold text-gray-900 mb-4">Patient Reviews</h3>
					<div class="space-y-4">
						@foreach($doctor->reviews->take(3) as $review)
						<div class="border-l-4 border-green-500 pl-4">
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
			<h2 class="text-3xl font-bold text-gray-900 mb-4">Book an Appointment with Dr.
				{{ $doctor->user->name }}</h2>
			<p class="text-xl text-gray-600">Schedule your consultation with our experienced doctor
			</p>
		</div>
		<div class="bg-black rounded-xl shadow-lg p-8">
			<form class="space-y-6">
				<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
					<div>
						<label class="block text-sm font-medium text-gray-700 mb-2">Full
							Name</label>
						<input type="text"
							class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
							placeholder="Enter your full name">
					</div>
					<div>
						<label class="block text-sm font-medium text-gray-700 mb-2">Phone
							Number</label>
						<input type="tel"
							class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
							placeholder="Enter your phone number">
					</div>
				</div>
				<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
					<div>
						<label
							class="block text-sm font-medium text-gray-700 mb-2">Email</label>
						<input type="email"
							class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
							placeholder="Enter your email">
					</div>
					<div>
						<label
							class="block text-sm font-medium text-gray-700 mb-2">Age</label>
						<input type="number"
							class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
							placeholder="Enter your age">
					</div>
				</div>
				<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
					<div>
						<label class="block text-sm font-medium text-gray-700 mb-2">Preferred
							Date</label>
						<input type="date"
							class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
					</div>
					<div>
						<label class="block text-sm font-medium text-gray-700 mb-2">Preferred
							Time</label>
						<select
							class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
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
						class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
						placeholder="Describe your symptoms or reason for the appointment"></textarea>
				</div>
				<div class="text-center">
					<button type="submit"
						class="bg-green-600 text-black px-8 py-4 rounded-lg font-semibold hover:bg-green-700 transition-colors">
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
			<h2 class="text-3xl font-bold text-gray-900 mb-4">Contact Dr. {{ $doctor->user->name }}
			</h2>
			<p class="text-xl text-gray-600">Get in touch for consultations and appointments</p>
		</div>
		<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
			<div class="bg-black rounded-xl shadow-lg p-8">
				<h3 class="text-2xl font-bold text-gray-900 mb-6">Contact Information</h3>
				<div class="space-y-6">
					<div class="flex items-center">
						<div
							class="bg-green-100 w-12 h-12 rounded-lg flex items-center justify-center mr-4">
							<i class="fas fa-phone text-green-600"></i>
						</div>
						<div>
							<h4 class="font-semibold text-gray-900">Phone</h4>
							<p class="text-gray-600">{{ $doctor->phone }}</p>
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
								{{ $doctor->user->email }}</p>
						</div>
					</div>
					<div class="flex items-start">
						<div
							class="bg-purple-100 w-12 h-12 rounded-lg flex items-center justify-center mr-4 mt-1">
							<i class="fas fa-hospital text-purple-600"></i>
						</div>
						<div>
							<h4 class="font-semibold text-gray-900">Clinic
							</h4>
							<p class="text-gray-600">
								{{ $doctor->clinic->name }}</p>
							<p class="text-gray-600">
								{{ $doctor->clinic->address }}</p>
						</div>
					</div>
				</div>
			</div>
			<div class="bg-black rounded-xl shadow-lg p-8">
				<h3 class="text-2xl font-bold text-gray-900 mb-6">Send a Message</h3>
				<form class="space-y-4">
					<div>
						<label
							class="block text-sm font-medium text-gray-700 mb-2">Name</label>
						<input type="text"
							class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
					</div>
					<div>
						<label
							class="block text-sm font-medium text-gray-700 mb-2">Email</label>
						<input type="email"
							class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
					</div>
					<div>
						<label
							class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
						<input type="text"
							class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
					</div>
					<div>
						<label
							class="block text-sm font-medium text-gray-700 mb-2">Message</label>
						<textarea rows="4"
							class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"></textarea>
					</div>
					<button type="submit"
						class="w-full bg-green-600 text-black py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors">
						Send Message
					</button>
				</form>
			</div>
		</div>
	</div>
</section>

@endsection
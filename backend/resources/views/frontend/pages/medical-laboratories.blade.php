@extends('frontend.layouts.app')

@section('content')

<!-- Clinics Hero Section -->
<section class="bg-gradient-to-r from-blue-600 to-blue-800 py-20 mt-10">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
		<div class="text-center">
			<h1 class="text-5xl font-bold text-white mt-10">Our Medical Laboratories</h1>
			<p class="text-xl text-blue-100 mb-8">Discover our network of healthcare facilities
				providing quality medical care</p>
			<div class="flex justify-center">
				<a href="#medical-laboratories"
					class="bg-white text-blue-600 px-8 py-4 rounded-lg font-semibold hover:bg-blue-50 transition-colors inline-flex items-center">
					View All Medical Laboratories <i class="fas fa-arrow-down ml-2"></i>
				</a>
			</div>
		</div>
	</div>
</section>

<!-- Clinics Grid Section -->
<section id="medical-laboratories" class="py-20">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
		<div class="text-center mb-16">
			<h2 class="text-4xl font-bold text-gray-900 mb-4">Healthcare Facilities</h2>
			<p class="text-xl text-gray-600">Choose from our network of specialized clinics and
				medical centers</p>
		</div>

		@if($medicalLaboratories->count() > 0)
		<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
			@foreach($medicalLaboratories as $medicalLaboratory)
			<div
				class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
				<div class="p-6">
					<div class="flex items-center mb-4 gap-4">
						@if($medicalLaboratory->logo)
						<img src="{{ asset('storage/' . $medicalLaboratory->logo) }}"
							alt="{{ $medicalLaboratory->name }}"
							class="w-16 h-16 rounded-full mr-4">
						@else
						<div
							class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mr-4">
							<i
								class="fas fa-hospital text-2xl text-blue-600"></i>
						</div>
						@endif
						<div>
							<h3 class="text-xl font-bold text-gray-900">
								{{ $medicalLaboratory->name }}</h3>

						</div>
					</div>

					<p class="text-gray-600 mb-4 line-clamp-3">
						{{ Str::limit($medicalLaboratory->description, 120) }}</p>

					<div class="space-y-2 mb-4">
						<div class="flex items-center text-gray-600 gap-2">
							<i
								class="fas fa-map-marker-alt mr-2 text-blue-600"></i>
							<span class="text-sm">{{ $medicalLaboratory->address }},
								{{ $medicalLaboratory->area->name ?? '' }}</span>
						</div>
						<div class="flex items-center text-gray-600 gap-2">
							<i class="fas fa-phone mr-2 text-blue-600"></i>
							<span
								class="text-sm">{{ $medicalLaboratory->phone }}</span>
						</div>

					</div>

					<div class="flex items-center justify-between">
						<div class="flex items-center">
							<div class="flex text-yellow-400 mr-2">
								@for($i = 0; $i < 5; $i++) <i
									class="fas fa-star"></i>
									@endfor
							</div>
							<span class="text-sm text-gray-600">({{ $medicalLaboratory->reviews->count() }}
								reviews)</span>
						</div>
						<span
							class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-semibold">
							{{ $medicalLaboratory->is_active ? 'Active' : 'Inactive' }}
						</span>
					</div>
				</div>

				<div class="px-6 pb-6">
					<div class="flex space-x-3">
						<a href="{{ route('medical-laboratory.detail', $medicalLaboratory->id) }}"
							class="flex-1 bg-blue-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-blue-700 transition-colors text-center">
							<i class="fas fa-eye mr-2"></i> View Details
						</a>
						<a href="tel:{{ $medicalLaboratory->phone }}"
							class="bg-green-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-green-700 transition-colors">
							<i class="fas fa-phone"></i>
						</a>
					</div>
				</div>
			</div>
			@endforeach
		</div>
		@else
		<div class="text-center py-16">
			<div
				class="bg-gray-100 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6">
				<i class="fas fa-hospital text-3xl text-gray-400"></i>
			</div>
			<h3 class="text-2xl font-semibold text-gray-900 mb-4">No Medical Laboratories Available
			</h3>
			<p class="text-gray-600 mb-8">We're working on adding more healthcare facilities to our
				network.</p>
			<a href="{{ route('register-medical-laboratory') }}"
				class="bg-blue-600 text-white px-8 py-4 rounded-lg font-semibold hover:bg-blue-700 transition-colors inline-flex items-center">
				<i class="fas fa-plus mr-2"></i> Register Your Medical Laboratory
			</a>
		</div>
		@endif
	</div>
</section>

<!-- Call to Action Section -->
<section class="bg-gray-50 py-16">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
		<div class="text-center">
			<h2 class="text-3xl font-bold text-gray-900 mb-4">Join Our Healthcare Network</h2>
			<p class="text-xl text-gray-600 mb-8">Are you a healthcare provider? Register your medical
				laboratory
				with us and reach more patients.</p>
			<div class="flex flex-col sm:flex-row gap-4 justify-center">
				<a href="{{ route('register-medical-laboratory') }}"
					class="bg-blue-600 text-white px-8 py-4 rounded-lg font-semibold hover:bg-blue-700 transition-colors inline-flex items-center justify-center">
					<i class="fas fa-hospital mr-2"></i> Register Medical Laboratory
				</a>
				<a href="#contact"
					class="border-2 border-blue-600 text-blue-600 px-8 py-4 rounded-lg font-semibold hover:bg-blue-600 hover:text-white transition-colors inline-flex items-center justify-center">
					<i class="fas fa-info-circle mr-2"></i> Learn More
				</a>
			</div>
		</div>
	</div>
</section>

@endsection
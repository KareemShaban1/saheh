@extends('backend.dashboards.clinic.layouts.master')



@push('styles')
<style>
.dental-chart-container {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 40px;
	margin: 2rem 0;
	user-select: none;
}

.arch {
	display: flex;
	justify-content: center;
	flex-wrap: nowrap;
	gap: 8px;
}

.tooth {
	position: relative;
	width: 48px;
	height: 48px;
	border: 2px solid #d1d5db;
	border-radius: 8px;
	background: #f9fafb;
	display: flex;
	align-items: center;
	justify-content: center;
	cursor: pointer;
	transition: background 0.2s ease;
}

.tooth-number {
	position: absolute;
	top: -18px;
	left: 50%;
	transform: translateX(-50%);
	font-size: 12px;
	color: #6b7280;
}

.tooth-status {
	position: absolute;
	bottom: -16px;
	left: 50%;
	transform: translateX(-50%);
	font-size: 10px;
	color: #4b5563;
	text-transform: capitalize;
}

.tooth-surfaces {
	position: relative;
	width: 100%;
	height: 100%;
}

.surface {
	position: absolute;
	transition: background 0.2s ease;
}

.surface.buccal {
	top: 0;
	left: 10%;
	right: 10%;
	height: 20%;
}

.surface.lingual {
	bottom: 0;
	left: 10%;
	right: 10%;
	height: 20%;
}

.surface.mesial {
	top: 10%;
	bottom: 10%;
	left: 0;
	width: 20%;
}

.surface.distal {
	top: 10%;
	bottom: 10%;
	right: 0;
	width: 20%;
}

.surface.occlusal {
	top: 35%;
	left: 35%;
	width: 30%;
	height: 30%;
	border-radius: 50%;
}

.surface:hover {
	background: rgba(96, 165, 250, 0.3);
}

.surface.active {
	background: rgba(34, 197, 94, 0.6);
}

.upper-arch .tooth {
	transform: rotateX(10deg);
}

.lower-arch .tooth {
	transform: rotateX(-10deg);
}
</style>
@endpush

@section('content')
@section('page-header')
<h4 class="page-title">{{__('Tooth Record')}}</h4>

<div class="page-title-right">

</div>
@endsection
<div class="container mt-4">
	<h3>Dental Chart — {{ $patient->name }}</h3>
	<p class="text-muted">Click a tooth to edit its status or add notes. Click individual surfaces for more
		detail.</p>

	<div id="dental-chart" class="dental-chart-container">
		{{-- Upper arch --}}
		<div class="arch upper-arch">
			@for ($i = 1; $i <= 16; $i++) <div class="tooth" data-tooth="{{ $i }}">
				<div class="tooth-number">{{ $i }}</div>
				<div class="tooth-surfaces">
					<div class="surface buccal" data-surface="buccal"></div>
					<div class="surface lingual" data-surface="lingual"></div>
					<div class="surface mesial" data-surface="mesial"></div>
					<div class="surface distal" data-surface="distal"></div>
					<div class="surface occlusal" data-surface="occlusal"></div>
				</div>
				<div class="tooth-status text-xs">Healthy</div>
		</div>
		@endfor
	</div>

	{{-- Lower arch --}}
	<div class="arch lower-arch">
		@for ($i = 17; $i <= 32; $i++) <div class="tooth" data-tooth="{{ $i }}">
			<div class="tooth-number">{{ $i }}</div>
			<div class="tooth-surfaces">
				<div class="surface buccal" data-surface="buccal"></div>
				<div class="surface lingual" data-surface="lingual"></div>
				<div class="surface mesial" data-surface="mesial"></div>
				<div class="surface distal" data-surface="distal"></div>
				<div class="surface occlusal" data-surface="occlusal"></div>
			</div>
			<div class="tooth-status text-xs">Healthy</div>
	</div>
	@endfor
</div>
</div>

<div class="mt-4">
	<strong>Legend:</strong>
	<span class="inline-block px-3 py-1 bg-green-200 rounded text-sm mx-1">Healthy</span>
	<span class="inline-block px-3 py-1 bg-red-300 rounded text-sm mx-1">Decayed</span>
	<span class="inline-block px-3 py-1 bg-yellow-300 rounded text-sm mx-1">Filled</span>
	<span class="inline-block px-3 py-1 bg-gray-400 rounded text-sm mx-1">Missing</span>
	<span class="inline-block px-3 py-1 bg-purple-300 rounded text-sm mx-1">Root Canal</span>
	<span class="inline-block px-3 py-1 bg-blue-300 rounded text-sm mx-1">Crown</span>
</div>
</div>

<!-- Modal -->
<div class="modal fade" id="toothModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog">
		<form id="toothForm" class="modal-content">
			@csrf
			<div class="modal-header">
				<h5 class="modal-title">Tooth <span id="modalToothNumber"></span></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"
					aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<input type="hidden" name="tooth_number" id="tooth_number">

				<div class="mb-3">
					<label class="form-label">Status</label>
					<select name="status" id="tooth_status" class="form-select">
						<option value="healthy">Healthy</option>
						<option value="decayed">Decayed</option>
						<option value="filled">Filled</option>
						<option value="missing">Missing</option>
						<option value="root_canal">Root Canal</option>
						<option value="crown">Crown</option>
					</select>
				</div>

				<div class="mb-3">
					<label class="form-label">Notes</label>
					<textarea name="notes" id="tooth_notes" class="form-control"
						rows="4"></textarea>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" id="deleteTooth"
					class="btn btn-outline-danger me-auto">Remove Record</button>
				<button type="button" class="btn btn-secondary"
					data-bs-dismiss="modal">Cancel</button>
				<button type="submit" class="btn btn-primary">Save Tooth</button>
			</div>
		</form>
	</div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
	const patientId = "{{ $patient->id }}";
	const token = document.querySelector('meta[name="csrf-token"]').content;
	const toothModal = new bootstrap.Modal(document.getElementById('toothModal'));
	let currentTooth = null;

	// Click on tooth (not surface)
	document.querySelectorAll('.tooth').forEach(elem => {
		elem.addEventListener('click', e => {
			if (e.target.classList
				.contains('surface')
			)
				return; // ignore surface clicks

			const toothNumber = elem
				.dataset.tooth;
			const status = elem.dataset
				.status ||
				'healthy';
			const notes = elem.dataset
				.notes || '';

			currentTooth = toothNumber;
			document.getElementById(
					'modalToothNumber'
				)
				.innerText =
				toothNumber;
			document.getElementById(
					'tooth_number'
				).value =
				toothNumber;
			document.getElementById(
					'tooth_status'
				).value =
				status;
			document.getElementById(
					'tooth_notes'
				).value =
				notes;

			toothModal.show();
		});
	});

	// Click on surfaces
	document.querySelectorAll('.surface').forEach(surface => {
		surface.addEventListener('click', e => {
			e.stopPropagation();

			const tooth = surface.closest(
				'.tooth');
			const toothId = tooth.dataset
				.tooth;
			const surfaceType = surface
				.dataset.surface;

			surface.classList.toggle(
				'active');

			window.selectedSurfaces =
				window
				.selectedSurfaces ||
				{};
			window.selectedSurfaces[
					toothId] =
				window
				.selectedSurfaces[
					toothId
				] || [];

			if (surface.classList
				.contains('active')
			) {
				window.selectedSurfaces[
						toothId
					]
					.push(
						surfaceType
					);
			} else {
				window.selectedSurfaces[
						toothId
					] =
					window
					.selectedSurfaces[
						toothId
					]
					.filter(s =>
						s !==
						surfaceType
					);
			}

			console.log(`Tooth ${toothId} surfaces:`,
				window
				.selectedSurfaces[
					toothId
				]
			);
		});
	});

	// Save via AJAX
	document.getElementById('toothForm').addEventListener('submit', function(e) {
		e.preventDefault();
		const data = {
			tooth_number: this.tooth_number.value,
			status: this.status.value,
			notes: this.notes.value,
			_token: token
		};

		fetch(`/clinic/tooth-record/store/${patientId}`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-CSRF-TOKEN': token
				},
				body: JSON.stringify(data)
			}).then(r => r.json())
			.then(res => {
				if (res.success) {
					const toothEl =
						document
						.querySelector(
							`.tooth[data-tooth="${data.tooth_number}"]`
						);
					if (toothEl) {
						const colors = {
							healthy: 'bg-green-200',
							decayed: 'bg-red-300',
							filled: 'bg-yellow-300',
							missing: 'bg-gray-400',
							root_canal: 'bg-purple-300',
							crown: 'bg-blue-300'
						};
						toothEl.className =
							toothEl
							.className
							.replace(/\bbg-\S+/g,
								''
							)
							.trim();
						toothEl.classList
							.add(colors[data
									.status
								] ||
								'bg-white'
							);
						toothEl.dataset
							.status =
							data
							.status;
						toothEl.dataset
							.notes =
							data
							.notes;
						toothEl.querySelector(
								'.tooth-status'
							)
							.innerText =
							data
							.status
							.replace('_',
								' '
							);
					}
					toothModal.hide();
				} else {
					alert(res.message ||
						'Could not save'
					);
				}
			}).catch(err => {
				console.error(err);
				alert('Save failed');
			});
	});

	// Delete record
	document.getElementById('deleteTooth').addEventListener('click', function() {
		if (!currentTooth) return;
		if (!confirm('Remove tooth record?')) return;

		fetch(`/clinic/tooth-record/delete/${patientId}`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-CSRF-TOKEN': token
				},
				body: JSON.stringify({
					tooth_number: currentTooth
				})
			}).then(r => r.json())
			.then(res => {
				if (res.success) {
					const toothEl =
						document
						.querySelector(
							`.tooth[data-tooth="${currentTooth}"]`
						);
					if (toothEl) {
						toothEl.className =
							toothEl
							.className
							.replace(/\bbg-\S+/g,
								''
							)
							.trim();
						toothEl.classList
							.add(
								'bg-green-200'
							);
						toothEl.dataset
							.status =
							'healthy';
						toothEl.dataset
							.notes =
							'';
						toothEl.querySelector(
								'.tooth-status'
							)
							.innerText =
							'Healthy';
					}
					toothModal.hide();
				}
			}).catch(err => {
				console.error(err);
				alert('Delete failed');
			});
	});
});

document.addEventListener('DOMContentLoaded', function() {
	const records = @json($records);
	const colors = {
		healthy: 'bg-green-200',
		decayed: 'bg-red-300',
		filled: 'bg-yellow-300',
		missing: 'bg-gray-400',
		root_canal: 'bg-purple-300',
		crown: 'bg-blue-300'
	};

	if (Array.isArray(records)) {
		records.forEach(record => {
			const toothEl = document.querySelector(
				`.tooth[data-tooth="${record.tooth_number}"]`
			);
			if (toothEl) {
				// Clean old bg colors
				toothEl.className = toothEl.className
					.replace(/\bbg-\S+/g, '')
					.trim();
				toothEl.classList.add(colors[record
						.status
					] ||
					'bg-green-200');
				toothEl.dataset.status = record.status;
				toothEl.dataset.notes = record.notes ||
					'';
				toothEl.querySelector('.tooth-status')
					.innerText = record.status
					.replace('_', ' ');
			}
		});
	}
});
</script>
@endpush

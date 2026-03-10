<!-- jquery -->
<script src="{{ asset('backend/assets/js/jquery-3.3.1.min.js') }}"></script>

<script src="{{ asset('backend/assets/js/bootstrap.min.js') }}"></script>

<!-- plugins-jquery -->
<script src="{{ asset('backend/assets/js/plugins-jquery.js') }}"></script>

<!-- plugin_path -->
<script>
var plugin_path = "{{ asset('backend/assets/js/') }}";
</script>

<!-- datepicker -->
<script src="{{ asset('backend/assets/js/datepicker.js') }}"></script>
<!-- sweetalert2 -->
<!-- <script src="{{ asset('backend/assets/js/sweetalert2.js') }}"></script> -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<!-- <script src="{{ asset('backend/assets/js/popper.min.js') }}"></script> -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>


<script src="{{ asset('backend/assets/js/toastr.js') }}"></script>

<script>
@if(session('toast_success'))
toastr.success("{{ session('toast_success') }}", "", {
	"timeOut": 1000
}); // Set timeOut to 1000 milliseconds (1 second)
@endif
@if(session('toast_error'))
toastr.error("{{ session('toast_error') }}", "", {
	"timeOut": 1000
}); // Set timeOut to 1000 milliseconds (1 second)
@endif
</script>

<script src="{{ asset('backend/assets/js/custom.min.js') }}?v=1.0.2"></script>


<script src="{{ asset('backend/assets/jquery-ui/jquery-ui.min.js') }}?v=1.0.2"></script>
<script src="{{asset('backend/assets/datatable/jquery.dataTables.min.js')}}?v=1.0.2"></script>
<script src="{{asset('backend/assets/datatable/dataTables.bootstrap5.js')}}?v=1.0.2"></script>
<script src="{{asset('backend/assets/datatable/dataTables.responsive.min.js')}}?v=1.0.2"></script>
<script src="{{asset('backend/assets/datatable/responsive.bootstrap5.min.js')}}?v=1.0.2"></script>

<script>
const languages = {
	@if(App::getLocale() == 'en')
	en: {
		paginate: {
			previous: "<i class='mdi mdi-chevron-left'></i> Previous",
			next: "Next <i class='mdi mdi-chevron-right'></i>"
		},
		info: "Showing records _START_ to _END_ of _TOTAL_",
		lengthMenu: "Display _MENU_ records",
		search: "_INPUT_",
		searchPlaceholder: "Search...",
		zeroRecords: "No matching records found",
		infoEmpty: "No records to display",
		infoFiltered: "(filtered from _MAX_ total records)"
	},
	@else
	ar: {
		paginate: {
			previous: "<i class='mdi mdi-chevron-right'></i> السابق",
			next: "التالي <i class='mdi mdi-chevron-left'></i>"
		},
		info: "عرض السجلات من _START_ إلى _END_ من إجمالي _TOTAL_ سجلات",
		lengthMenu: "عرض _MENU_ سجلات",
		search: "_INPUT_",
		searchPlaceholder: "بحث...",
		zeroRecords: "لا توجد سجلات مطابقة",
		infoEmpty: "لا توجد سجلات للعرض",
		infoFiltered: "(تمت التصفية من إجمالي _MAX_ سجلات)"
	}
	@endif
};

const language = '{{ App::getLocale() }}';
</script>


<script>
document.addEventListener('DOMContentLoaded', function() {
	const searchInput = document.getElementById('sidebarSearch');
	const navLinks = document.querySelectorAll('#sidebarnav li');
	const collapsibleMenus = document.querySelectorAll('#sidebarnav ul.collapse');

	searchInput.addEventListener('keyup', function() {
		const filter = this.value.toLowerCase().trim();

		if (filter === '') {
			// ✅ Show all items again
			navLinks.forEach(li => li.style.display = '');

			// ✅ Collapse all expanded menus
			collapsibleMenus.forEach(menu => menu.classList
				.remove('show'));
			return;
		}

		navLinks.forEach(li => {
			const text = li.textContent
				.toLowerCase();
			if (text.includes(filter)) {
				li.style.display =
					'';

				// ✅ Expand parent menus if nested
				const parentMenu =
					li
					.closest(
						'ul.collapse'
						);
				if (parentMenu)
					parentMenu
					.classList
					.add(
						'show'
						);
			} else {
				li.style.display =
					'none';
			}
		});
	});
});
</script>



<script>
(function() {
	const startTime = performance.now();

	window.addEventListener('load', async function() {
		const loadTime = performance.now() - startTime;
		const loadSeconds = (loadTime / 1000).toFixed(2);

		if (loadTime > 2000) {
			let causes = [];

			// --- 1. Large images (>1MP)
			const largeImages = [...document.images].filter(
				img => img.naturalWidth * img
				.naturalHeight > 1000000
			);
			if (largeImages.length > 0) {
				causes.push(
					`⚠️ ${largeImages.length} large images`);
				console.groupCollapsed(
					"🖼️ Large Images (>1MP)"
					);
				largeImages.forEach(img => {
					console.log({
						src: img.src,
						width: img.naturalWidth,
						height: img
							.naturalHeight,
						totalPixels: img
							.naturalWidth *
							img
							.naturalHeight
					});
				});
				console.groupEnd();
			}

			// --- 2. Too many scripts
			const scripts = document.querySelectorAll(
				'script[src]');
			if (scripts.length > 20) {
				causes.push(
					`⚠️ Too many scripts loaded (${scripts.length})`);
				console.groupCollapsed(
					"📜 Loaded Scripts");
				scripts.forEach(script => console.log(
					script.src
					));
				console.groupEnd();
			}

			// --- 3. Slow network resources (>1s)
			const slowResources = performance
				.getEntriesByType('resource')
				.filter(r => r.duration > 1000);
			if (slowResources.length > 0) {
				causes.push(
					`🐢 ${slowResources.length} slow network resources`);
				console.groupCollapsed(
					"🐢 Slow Network Resources (>1s)"
					);
				slowResources.forEach(r => {
					console.log({
						name: r.name,
						type: r.initiatorType,
						duration: r
							.duration
							.toFixed(
								2) +
							'ms',
						transferSize: r
							.transferSize
					});
				});
				console.groupEnd();
			}

			// --- Summary Log
			console.group(
				`🚨 Slow Page: ${window.location.pathname}`);
			console.log(`Load time: ${loadSeconds}s`);
			console.log("Possible causes:", causes.length ?
				causes : "Unknown");
			console.groupEnd();

			// --- Suggestions
			console.groupCollapsed(
				"%c💡 Optimization Suggestions",
				"color: orange; font-weight: bold"
				);
			console.log(
				"- Optimize images (resize, compress, or use WebP/lazy loading)");
			console.log("- Minify and combine JS/CSS files");
			console.log(
				"- Use browser caching (Cache-Control headers)");
			console.log("- Defer non-critical scripts");
			console.log(
			"- Avoid large third-party resources");
			console.groupEnd();

			// --- Send to backend
			try {
				await fetch("/api/performance-log", {
					method: "POST",
					headers: {
						"Content-Type": "application/json",
						"X-CSRF-TOKEN": document
							.querySelector(
								'meta[name="csrf-token"]'
								)
							?.content
					},
					body: JSON.stringify({
						url: window
							.location
							.href,
						load_time: loadSeconds,
						causes,
						details: {
							large_images: largeImages
								.map(img =>
									img
									.src
									),
							slow_resources: slowResources
								.map(r => ({
									name: r.name,
									duration: r
										.duration,
									type: r.initiatorType
								}))
						},
						user_agent: navigator
							.userAgent,
						timestamp: new Date()
							.toISOString()
					})
				});
			} catch (e) {
				console.warn("Failed to log performance issue",
					e);
			}
		}
	});
})();
</script>



@livewireScripts
@stack('scripts')
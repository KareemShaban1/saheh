<div class="container-fluid">
	<div class="row">

		<!-- Left Sidebar start-->
		<div class="side-menu-fixed">
			<div class="scrollbar side-menu-bg">
				<!-- 🔍 Sidebar Search -->
				<div class="sidebar-search m-3">
					<input type="text" id="sidebarSearch" class="form-control"
						style="border-radius: 15px;"
						placeholder="{{ __('Search Menu') }}" />
				</div>
				<ul class="nav navbar-nav side-menu" id="sidebarnav">

					@include('backend.shared.menu', [
					'id' => 'dashboard',
					'icon' => 'fa-solid fa-house-user',
					'label' => trans('backend/sidebar_trans.Dashboard'),
					'items' => [
					['route' => 'clinic.dashboard', 'label' =>
					trans('backend/sidebar_trans.Dashboard')],
					'permission' => 'view-dashboard'
					],
					'permissions' => ['view-dashboard'],
					'guard' => 'web'
					])

					@include('backend.shared.menu', [
					'id' => 'events-menu',
					'icon' => 'fa-sharp fa-solid fa-list',
					'label' => trans('backend/sidebar_trans.Events'),
					'permissions' => ['view-events', 'edit-events', 'delete-events'],
					'guard' => 'web',
					'items' => [
					['route' => 'clinic.events.show', 'label' =>
					trans('backend/sidebar_trans.Calendar'),
					'permission' => 'view-events'],
					['route' => 'clinic.events.index', 'label' =>
					trans('backend/sidebar_trans.All_Events'),
					'permission' => 'view-events'],
					['route' => 'clinic.events.trash', 'label' =>
					trans('backend/sidebar_trans.Deleted_Events'),
					'permission' => 'view-events'],

					]
					])

					@include('backend.shared.menu', [
					'id' => 'chats-menu',
					'icon' => 'fa-solid fa-message',
					'label' => trans('backend/sidebar_trans.Chats'),
					'permissions' => ['view-users'],
					'guard' => 'web',
					'items' => [
					['route' => 'clinic.chats.index', 'label' =>
					trans('backend/sidebar_trans.Chats'),
					'permission' => 'view-users'],
					]
					])

					@include('backend.shared.menu', [
					'id' => 'announcements-menu',
					'icon' => 'fa-solid fa-star',
					'label' => trans('backend/sidebar_trans.Announcements_&_Reviews'),
					'permissions' => ['view-users'],
					'guard' => 'web',
					'items' => [
					['route' => 'clinic.announcements.index', 'label' =>
					trans('backend/sidebar_trans.Announcements'),
					'permission' => 'view-users'],
					['route' => 'clinic.reviews.index', 'label' =>
					trans('backend/sidebar_trans.Reviews'),
					'permission' => 'view-users'],
					]
					])


					@include('backend.shared.menu', [
					'id' => 'users-menu',
					'icon' => 'fa-solid fa-user',
					'label' => trans('backend/sidebar_trans.User_Management'),
					'permissions' => ['view-users'],
					'guard' => 'web',
					'items' => [
					['route' => 'clinic.users.index', 'label' =>
					trans('backend/sidebar_trans.All_Users'),
					'permission' => 'view-users'],
					['route' => 'clinic.roles.index', 'label' =>
					trans('backend/sidebar_trans.All_Roles'),
					'permission' => 'view-roles'],
					]
					])

					@include('backend.shared.menu', [
					'id' => 'doctors-menu',
					'icon' => 'fa-solid fa-user-md',
					'label' => trans('backend/sidebar_trans.Doctors'),
					'permissions' => ['view-doctors', 'edit-doctor', 'delete-doctor'],
					'guard' => 'web',
					'items' => [
					['route' => 'clinic.doctors.index', 'label' =>
					trans('backend/sidebar_trans.All_Doctors'),
					'permission' => 'view-doctors'],
					]
					])




					<!-- @include('backend.shared.menu', [
                    'id' => 'types-menu',
                    'icon' => 'fa-sharp fa-solid fa-list',
                    'label' => trans('backend/sidebar_trans.Types'),
                    'permissions' => ['view-types', 'edit-type', 'delete-type'],
                    'guard' => 'web',
                    'items' => [
                    ['route' => 'clinic.type.index', 'label' => trans('backend/sidebar_trans.All_Types'),
                    'permission' => 'view-types'],
                    ]
                    ]) -->

					<!-- menu Patients-->
					@include('backend.shared.menu', [
					'id' => 'patients-menu',
					'icon' => 'fa-solid fa-hospital-user',
					'label' => trans('backend/sidebar_trans.Patients'),
					'permissions' => ['view-patients', 'edit-patients',
					'delete-patients'],
					'guard' => 'web',
					'items' => [
					['route' => 'clinic.patients.add_patient_code', 'label' =>
					trans('backend/sidebar_trans.Add_Patient_Using_Code')],
					['route' => 'clinic.patients.add', 'label' =>
					trans('backend/sidebar_trans.Add_Patient'),
					'permission' => 'view-patients'],
					['route' => 'clinic.patients.index', 'label' =>
					trans('backend/sidebar_trans.All_Patients'),
					'permission' => 'view-patients'],
					['route' => 'clinic.patients.trash', 'label' =>
					trans('backend/sidebar_trans.Deleted_Patients'),
					'permission' => 'view-patients'],

					]
					])

					<!--  'permissions' => ['view-organization-inventories', 'edit-organization-inventories', 'delete-organization-inventories'],-->
					@include('backend.shared.menu', [
					'id' => 'organization-inventories-menu',
					'icon' => 'fa-solid fa-boxes-stacked',
					'label' => trans('backend/sidebar_trans.Organization_Inventory'),
					'guard' => 'web',
					'items' => [
					['route' => 'clinic.organization-inventories.index', 'label' =>
					trans('backend/sidebar_trans.All_Organization_Inventory')
					],
					]
					])



					@include('backend.shared.menu', [
					'id' => 'Services-menu',
					'icon' => 'fa-sharp fa-solid fa-list',
					'label' => trans('backend/sidebar_trans.services'),
					'guard' => 'web',
					'items' => [
					['route' => 'clinic.Services.index', 'label' =>
					trans('backend/sidebar_trans.All_services'),
					],
					]
					])




					<!-- menu number of Reservations-->
					@include('backend.shared.menu', [
					'id' => 'num_of_reservations-menu',
					'icon' => 'fa-sharp fa-solid fa-list',
					'label' =>
					trans('backend/sidebar_trans.Controll_Number_of_Reservations'),
					'permissions' => ['view-reservation-number',
					'edit-reservation-number', 'delete-reservation-number'],
					'guard' => 'web',
					'items' => [

					['route' => 'clinic.reservation_numbers.index', 'label' =>
					trans('backend/sidebar_trans.Number_of_Reservations'),
					'permission' => 'view-reservation-number'],

					['route' => 'clinic.reservation_slots.index', 'label' =>
					trans('backend/sidebar_trans.Reservation_Slots'),
					'permission' => 'view-reservation-number'],

					]
					])






					<!-- menu Reservations-->
					@include('backend.shared.menu', [
					'id' => 'reservations-menu',
					'icon' => 'fa fa-stethoscope',
					'label' => trans('backend/sidebar_trans.Reservations'),
					'permissions' => ['view-reservations', 'edit-reservations',
					'delete-reservations'],
					'guard' => 'web',
					'items' => [
					['route' => 'clinic.reservations.today_reservations', 'label' =>
					trans('backend/sidebar_trans.Today_Reservations'),
					'permission' => 'view-reservations'],

					['route' => 'clinic.reservations.index', 'label' =>
					trans('backend/sidebar_trans.All_Reservations'),
					'permission' => 'view-reservations'],
					['route' => 'clinic.reservations.trash', 'label' =>
					trans('backend/sidebar_trans.Deleted_Reservations'),
					'permission' => 'view-reservations'],

					]
					])


					<!-- @include('backend.shared.menu', [
                    'id' => 'online_reservations-menu',
                    'icon' => 'fa fa-stethoscope',
                    'label' => trans('backend/sidebar_trans.Online_Reservations'),
                    'permissions' => ['view-online-reservations', 'edit-online-reservations', 'delete-online-reservations'],
                    'guard' => 'web',
                    'items' => [
                    ['route' => 'clinic.online_reservations.index', 'label' => trans('backend/sidebar_trans.All_Online_Reservations')],

                    ]
                    ]) -->


					@include('backend.shared.menu', [
					'id' => 'glasses_distances-menu',
					'icon' => 'fa fa-stethoscope',
					'label' => trans('backend/sidebar_trans.Glasses_Distances'),
					'permissions' => ['view-glasses-distances', 'edit-glasses-distances',
					'delete-glasses-distances'],
					'guard' => 'web',
					'items' => [
					['route' => 'clinic.glasses_distance.index', 'label' =>
					trans('backend/sidebar_trans.Glasses_Distances'),
					'permission' => 'view-glasses-distances'],

					]
					])


					<!-- @include('backend.shared.menu', [
                    'id' => 'rays-menu',
                    'icon' => 'fa fa-stethoscope',
                    'label' => trans('backend/sidebar_trans.Rays'),
                    'permissions' => ['view-rays', 'edit-rays', 'delete-rays'],
                    'guard' => 'web',
                    'items' => [
                    ['route' => 'clinic.rays.index', 'label' => trans('backend/sidebar_trans.All_Rays'),
                    'permission' => 'view-rays'],

                    ]
                    ]) -->



					@include('backend.shared.menu', [
					'id' => 'medicine-menu',
					'icon' => 'fa-solid fa-pills',
					'label' => trans('backend/sidebar_trans.Medicine'),
					'permissions' => ['view-medicine', 'edit-medicine',
					'delete-medicine'],
					'guard' => 'web',
					'items' => [
					['route' => 'clinic.medicines.index', 'label' =>
					trans('backend/sidebar_trans.All_Medicine')],

					]
					])




					@include('backend.shared.menu', [
					'id' => 'fees-menu',
					'icon' => 'fa-solid fa-dollar-sign',
					'label' => trans('backend/sidebar_trans.Fees'),
					'permissions' => ['view-fees', 'edit-fees', 'delete-fees'],
					'guard' => 'web',
					'items' => [
					['route' => 'clinic.fees.index', 'label' =>
					trans('backend/sidebar_trans.All_Fees'),
					'permission' => 'view-fees'],

					]
					])




					@include('backend.shared.menu', [
					'id' => 'settings-menu',
					'icon' => 'fa-solid fa-cogs',
					'label' => trans('backend/sidebar_trans.Settings'),
					'permissions' => ['view-users', 'edit-users', 'delete-users'],
					'guard' => 'web',
					'items' => [
					['route' => 'clinic.settings.index', 'label' =>
					trans('backend/sidebar_trans.Settings'),
					'permission' => 'view-users'],

					]
					])




			</div>
		</div>

		<!-- Left Sidebar End-->

		<!--=================================

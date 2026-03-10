<div class="container-fluid">
    <div class="row">
        <!-- Left Sidebar start-->
        <div class="side-menu-fixed">
            <div class="scrollbar side-menu-bg">
                <ul class="nav navbar-nav side-menu" id="sidebarnav">



                    <li>
                        <a href="{{ Route('radiologyCenter.dashboard') }}"><i class="fa-solid fa-house-user"></i><span
                                class="right-nav-text">
                                {{ trans('backend/sidebar_trans.Dashboard') }}</span> </a>
                    </li>

                    <li class="mt-10 mb-10 text-muted pl-4 font-medium menu-title"> </li>

                    @include('backend.shared.menu', [
                    'id' => 'announcements-menu',
                    'icon' => 'fa-solid fa-star',
                    'label' => trans('backend/announcements_trans.Announcements'),
                    'permissions' => ['view-users'],
                    'guard' => 'radiology_center',
                    'items' => [
                    ['route' => 'radiologyCenter.announcements.index', 'label' => trans('backend/announcements_trans.Announcements'),
                    'permission' => 'view-users'],
                    ]
                    ])

                    <!-- menu Users-->
                    <li>
                        <a href="javascript:void(0);" data-toggle="collapse" data-target="#users-menu">
                            <div class="pull-left"><i class="fa fa-user"></i><span
                                    class="right-nav-text">{{ trans('backend/sidebar_trans.Users') }}</span></div>
                            <div class="pull-right"><i class="ti-plus"></i></div>
                            <div class="clearfix"></div>
                        </a>
                        <ul id="users-menu" class="collapse" data-parent="#sidebarnav">

                            <li> <a
                                    href="{{ Route('radiologyCenter.users.index') }}">{{ trans('backend/sidebar_trans.All_Users') }}</a>
                            </li>

                        </ul>
                    </li>



                    <li>
                        <a href="javascript:void(0);" data-toggle="collapse" data-target="#roles-menu">
                            <div class="pull-left"><i class="fa fa-user"></i><span
                                    class="right-nav-text">{{ trans('backend/sidebar_trans.Roles') }}</span></div>
                            <div class="pull-right"><i class="ti-plus"></i></div>
                            <div class="clearfix"></div>
                        </a>
                        <ul id="roles-menu" class="collapse" data-parent="#sidebarnav">

                            <li> <a
                                    href="{{ Route('radiologyCenter.roles.index') }}">{{ trans('backend/sidebar_trans.All_Roles') }}</a>
                            </li>

                        </ul>
                    </li>

                      <!--  'permissions' => ['view-organization-inventories', 'edit-organization-inventories', 'delete-organization-inventories'],-->
                      @include('backend.shared.menu', [
                    'id' => 'organization-inventories-menu',
                    'icon' => 'fa-solid fa-boxes-stacked',
                    'label' => trans('backend/sidebar_trans.Organization_Inventory'),
                    'guard' => 'radiology_center',
                    'items' => [
                    ['route' => 'radiologyCenter.organization-inventories.index', 'label' => trans('backend/sidebar_trans.All_Organization_Inventory')
                    ],
                    ]
                    ])

                    <!-- menu Types-->
                    <li>
                        <a href="javascript:void(0);" data-toggle="collapse" data-target="#types-menu">
                            <div class="pull-left"><i class="fa-sharp fa-solid fa-list"></i><span
                                    class="right-nav-text">{{ trans('backend/sidebar_trans.Types') }}</span></div>
                            <div class="pull-right"><i class="ti-plus"></i></div>
                            <div class="clearfix"></div>
                        </a>
                        <ul id="types-menu" class="collapse" data-parent="#sidebarnav">
                            <li> <a
                                    href="{{ Route('radiologyCenter.type.index') }}">{{ trans('backend/sidebar_trans.All_Types') }}</a>
                            </li>
                        </ul>
                    </li>

                    <li>
                        <a href="javascript:void(0);" data-toggle="collapse" data-target="#reviews-menu">
                            <div class="pull-left"><i class="fa-solid fa-star"></i><span
                                    class="right-nav-text">{{ trans('backend/sidebar_trans.Reviews') }}</span></div>
                            <div class="pull-right"><i class="ti-plus"></i></div>
                            <div class="clearfix"></div>
                        </a>
                        <ul id="reviews-menu" class="collapse" data-parent="#sidebarnav">
                            <li> <a
                                    href="{{ Route('radiologyCenter.reviews.index') }}">{{ trans('backend/sidebar_trans.All_Reviews') }}</a>
                            </li>
                        </ul>
                    </li>

                    <!-- menu Service Fees-->
                    <li>
                        <a href="javascript:void(0);" data-toggle="collapse" data-target="#Services-menu">
                            <div class="pull-left"><i class="fa-sharp fa-solid fa-list"></i><span
                                    class="right-nav-text">{{ trans('backend/sidebar_trans.services') }}</span></div>
                            <div class="pull-right"><i class="ti-plus"></i></div>
                            <div class="clearfix"></div>
                        </a>
                        <ul id="Services-menu" class="collapse" data-parent="#sidebarnav">
                            <li> <a href="{{ Route('radiologyCenter.Services.index') }}">
                                    {{ trans('backend/sidebar_trans.All_services') }} </a> </li>

                        </ul>
                    </li>


                    <li>
                        <a href="javascript:void(0);" data-toggle="collapse" data-target="#calendar-menu">
                            <div class="pull-left"><i class="ti-calendar"></i><span
                                    class="right-nav-text">{{ trans('backend/sidebar_trans.Events') }}</span></div>
                            <div class="pull-right"><i class="ti-plus"></i></div>
                            <div class="clearfix"></div>
                        </a>
                        <ul id="calendar-menu" class="collapse" data-parent="#sidebarnav">
                            <li> <a
                                    href="{{ Route('radiologyCenter.events.show') }}">{{ trans('backend/sidebar_trans.Calendar') }}</a>
                            </li>
                            <li> <a
                                    href="{{ Route('radiologyCenter.events.index') }}">{{ trans('backend/sidebar_trans.All_Events') }}</a>
                            </li>
                            <li> <a
                                    href="{{ Route('radiologyCenter.events.trash') }}">{{ trans('backend/sidebar_trans.Deleted_Events') }}</a>
                            </li>
                        </ul>
                    </li>

                    <!-- menu Patients-->
                    <li>
                        <a href="javascript:void(0);" data-toggle="collapse" data-target="#patients-menu">
                            <div class="pull-left"><i class="fa-solid fa-hospital-user"></i><span
                                    class="right-nav-text">{{ trans('backend/sidebar_trans.Patients') }}</span>
                            </div>
                            <div class="pull-right"><i class="ti-plus"></i></div>
                            <div class="clearfix"></div>
                        </a>
                        <ul id="patients-menu" class="collapse" data-parent="#sidebarnav">

                            <li>
                                <a href="{{ Route('radiologyCenter.patients.add_patient_code') }}">
                                    {{ trans('backend/sidebar_trans.Add_Patient_Using_Code') }}
                                </a>
                            </li>


                            @can('add-patient')
                            <li> <a href="{{ Route('radiologyCenter.patients.add') }}">
                                    {{ trans('backend/sidebar_trans.Add_Patient') }}</a> </li>
                            @endcan
                            @can('view-patients')
                            <li> <a href="{{ Route('radiologyCenter.patients.index') }}">{{ trans('backend/sidebar_trans.All_Patients') }}
                                </a> </li>
                            @endcan
                            @can('delete-patient')
                            <li> <a href="{{ Route('radiologyCenter.patients.trash') }}">
                                    {{ trans('backend/sidebar_trans.Deleted_Patients') }} </a> </li>
                            @endcan
                        </ul>
                    </li>



                    <!-- menu Reservations-->
                    <!-- <li>
                        <a href="javascript:void(0);" data-toggle="collapse" data-target="#reservations-menu">
                            <div class="pull-left"><i class="fa fa-stethoscope"></i><span
                                    class="right-nav-text">{{ trans('backend/sidebar_trans.Reservations') }}</span>
                            </div>
                            <div class="pull-right"><i class="ti-plus"></i></div>
                            <div class="clearfix"></div>
                        </a>
                        <ul id="reservations-menu" class="collapse" data-parent="#sidebarnav">
                            <li> <a href="{{ Route('clinic.reservations.index') }}">
                                    {{ trans('backend/sidebar_trans.All_Reservations') }}</a> </li>
                            <li> <a href="{{ Route('clinic.reservations.trash') }}">
                                    {{ trans('backend/sidebar_trans.Deleted_Reservations') }} </a> </li>

                        </ul>
                    </li> -->

                    <!-- menu Online Reservations-->
                    <!-- <li>
                        <a data-toggle="collapse" data-target="#online_reservations-menu">
                            <div class="pull-left"><i class="fa fa-stethoscope"></i><span
                                    class="right-nav-text">{{ trans('backend/sidebar_trans.Online_Reservations') }}</span>
                            </div>
                            <div class="pull-right"><i class="ti-plus"></i></div>
                            <div class="clearfix"></div>
                        </a>
                        <ul id="online_reservations-menu" class="collapse" data-parent="#sidebarnav">
                            <li>
                                <a href="{{ Route('clinic.online_reservations.index') }}">
                                    {{ trans('backend/sidebar_trans.All_Online_Reservations') }}</a>
                            </li>

                        </ul>
                    </li> -->


                    <li>
                        <a href="javascript:void(0);" data-toggle="collapse" data-target="#rays-menu">
                            <div class="pull-left"><i class="fa fa-stethoscope"></i><span
                                    class="right-nav-text">{{ trans('backend/sidebar_trans.Rays') }}</span>
                            </div>
                            <div class="pull-right"><i class="ti-plus"></i></div>
                            <div class="clearfix"></div>
                        </a>
                        <ul id="rays-menu" class="collapse" data-parent="#sidebarnav">

                            <li> <a href="{{ route('radiologyCenter.rays.index') }}">
                                    {{ trans('backend/sidebar_trans.All_Rays') }}</a> </li>

                        </ul>
                    </li>



                    <!-- menu Fees-->
                    <li>
                        <a href="javascript:void(0);" data-toggle="collapse" data-target="#fees-menu">
                            <div class="pull-left"><i class="fa-solid fa-dollar-sign"></i><span
                                    class="right-nav-text">{{ trans('backend/sidebar_trans.Fees') }}</span></div>
                            <div class="pull-right"><i class="ti-plus"></i></div>
                            <div class="clearfix"></div>
                        </a>
                        <ul id="fees-menu" class="collapse" data-parent="#sidebarnav">
                            <li> <a
                                    href="{{ Route('radiologyCenter.fees.index') }}">{{ trans('backend/sidebar_trans.All_Fees') }}</a>
                            </li>
                        </ul>
                    </li>



                    <li>
                        <a href="{{ Route('radiologyCenter.settings.index') }}"><i class="fa-solid fa-cogs"></i><span
                                class="right-nav-text">
                                {{ trans('backend/sidebar_trans.Settings') }}</span> </a>
                    </li>



            </div>
        </div>

        <!-- Left Sidebar End-->

        <!--=================================

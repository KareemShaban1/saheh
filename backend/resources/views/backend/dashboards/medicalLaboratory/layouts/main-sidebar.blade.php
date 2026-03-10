<div class="container-fluid">
    <div class="row">
        <!-- Left Sidebar start-->
        <div class="side-menu-fixed">
            <div class="scrollbar side-menu-bg">
                <ul class="nav navbar-nav side-menu" id="sidebarnav">





                    
                     <!-- dashboard -->
                     @include('backend.shared.menu', [
                    'id' => 'dashboard-menu',
                    'icon' => 'fa-solid fa-house-user',
                    'label' => trans('backend/sidebar_trans.Dashboard'),
                    'permissions' => ['view-dashboard'],
                    'guard' => 'medical_laboratory',
                    'items' => [
                    ['route' => 'medicalLaboratory.dashboard', 'label' => trans('backend/sidebar_trans.Dashboard'),
                    'permission' => 'view-dashboard'],
                    ]
                    ])

                    

                    <li class="mt-10 mb-10 text-muted pl-4 font-medium menu-title"> </li>

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
                                    href="{{ Route('medicalLaboratory.users.index') }}">{{ trans('backend/sidebar_trans.All_Users') }}</a>
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
                                    href="{{ Route('medicalLaboratory.roles.index') }}">{{ trans('backend/sidebar_trans.All_Roles') }}</a>
                            </li>

                        </ul>
                    </li>

                    <!-- chats -->
                    @include('backend.shared.menu', [
                    'id' => 'chats-menu',
                    'icon' => 'fa-solid fa-message',
                    'label' => trans('backend/sidebar_trans.Chats'),
                    'permissions' => ['view-users'],
                    'guard' => 'medical_laboratory',
                    'items' => [
                    ['route' => 'medicalLaboratory.chats.index', 'label' => trans('backend/sidebar_trans.Chats'),
                    'permission' => 'view-users'],
                    ]
                    ])

                    
                    @include('backend.shared.menu', [
                    'id' => 'announcements-menu',
                    'icon' => 'fa-solid fa-star',
                    'label' => trans('backend/announcements_trans.Announcements'),
                    'permissions' => ['view-users'],
                    'guard' => 'medical_laboratory',
                    'items' => [
                    ['route' => 'medicalLaboratory.announcements.index', 'label' => trans('backend/announcements_trans.Announcements'),
                    'permission' => 'view-users'],
                    ]
                    ])

                   

                    <!-- menu Types-->
                    <!-- <li>
                        <a href="javascript:void(0);" data-toggle="collapse" data-target="#types-menu">
                            <div class="pull-left"><i class="fa-sharp fa-solid fa-list"></i><span
                                    class="right-nav-text">{{ trans('backend/sidebar_trans.Types') }}</span></div>
                            <div class="pull-right"><i class="ti-plus"></i></div>
                            <div class="clearfix"></div>
                        </a>
                        <ul id="types-menu" class="collapse" data-parent="#sidebarnav">
                            <li> <a
                                    href="{{ Route('medicalLaboratory.type.index') }}">{{ trans('backend/sidebar_trans.All_Types') }}</a>
                            </li>
                        </ul>
                    </li> -->


                      <!--  'permissions' => ['view-organization-inventories', 'edit-organization-inventories', 'delete-organization-inventories'],-->
                      @include('backend.shared.menu', [
                    'id' => 'organization-inventories-menu',
                    'icon' => 'fa-solid fa-boxes-stacked',
                    'label' => trans('backend/sidebar_trans.Organization_Inventory'),
                    'guard' => 'medical_laboratory',
                    'items' => [
                    ['route' => 'medicalLaboratory.organization-inventories.index', 'label' => trans('backend/sidebar_trans.All_Organization_Inventory')
                    ],
                    ]
                    ])

                    

                    <li>
                        <a href="javascript:void(0);" data-toggle="collapse" data-target="#reviews-menu">
                            <div class="pull-left"><i class="fa-solid fa-star"></i><span
                                    class="right-nav-text">{{ trans('backend/sidebar_trans.Reviews') }}</span></div>
                            <div class="pull-right"><i class="ti-plus"></i></div>
                            <div class="clearfix"></div>
                        </a>
                        <ul id="reviews-menu" class="collapse" data-parent="#sidebarnav">
                            <li> <a
                                    href="{{ Route('medicalLaboratory.reviews.index') }}">{{ trans('backend/sidebar_trans.All_Reviews') }}</a>
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
                                <a href="{{ Route('medicalLaboratory.patients.add_patient_code') }}">
                                    {{ trans('backend/sidebar_trans.Add_Patient_Using_Code') }}
                                </a>
                            </li>


                            @can('add-patient')
                            <li> <a href="{{ Route('medicalLaboratory.patients.add') }}">
                                    {{ trans('backend/sidebar_trans.Add_Patient') }}</a> </li>
                            @endcan
                            @can('view-patients')
                            <li> <a href="{{ Route('medicalLaboratory.patients.index') }}">{{ trans('backend/sidebar_trans.All_Patients') }}
                                </a> </li>
                            @endcan
                            @can('delete-patient')
                            <li> <a href="{{ Route('medicalLaboratory.patients.trash') }}">
                                    {{ trans('backend/sidebar_trans.Deleted_Patients') }} </a> </li>
                            @endcan
                        </ul>
                    </li>



                     <!-- menu Service Category -->
                     <li>
                        <a href="javascript:void(0);" data-toggle="collapse" data-target="#serviceCategories-menu">
                            <div class="pull-left"><i class="fa-sharp fa-solid fa-list"></i><span
                                    class="right-nav-text">{{ trans('backend/sidebar_trans.Service_Categories') }}</span></div>
                            <div class="pull-right"><i class="ti-plus"></i></div>
                            <div class="clearfix"></div>
                        </a>
                        <ul id="serviceCategories-menu" class="collapse" data-parent="#sidebarnav">
                            <li> <a href="{{ Route('medicalLaboratory.serviceCategory.index') }}">
                                    {{ trans('backend/sidebar_trans.All_Service_Categories') }} </a> </li>

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
                            <li> <a href="{{ Route('medicalLaboratory.labService.index') }}">
                                    {{ trans('backend/sidebar_trans.All_services') }} </a> </li>

                        </ul>
                    </li>


                    <!-- <li>
                        <a href="javascript:void(0);" data-toggle="collapse" data-target="#calendar-menu">
                            <div class="pull-left"><i class="ti-calendar"></i><span
                                    class="right-nav-text">{{ trans('backend/sidebar_trans.Events') }}</span></div>
                            <div class="pull-right"><i class="ti-plus"></i></div>
                            <div class="clearfix"></div>
                        </a>
                        <ul id="calendar-menu" class="collapse" data-parent="#sidebarnav">
                            <li> <a
                                    href="{{ Route('medicalLaboratory.events.show') }}">{{ trans('backend/sidebar_trans.Calendar') }}</a>
                            </li>
                            <li> <a
                                    href="{{ Route('medicalLaboratory.events.index') }}">{{ trans('backend/sidebar_trans.All_Events') }}</a>
                            </li>
                            <li> <a
                                    href="{{ Route('medicalLaboratory.events.trash') }}">{{ trans('backend/sidebar_trans.Deleted_Events') }}</a>
                            </li>
                        </ul>
                    </li> -->

                    


                    <li>
                        <a href="javascript:void(0);" data-toggle="collapse" data-target="#analysis-menu">
                            <div class="pull-left"><i class="fa fa-stethoscope"></i><span
                                    class="right-nav-text">{{ trans('backend/sidebar_trans.Analysis') }}</span>
                            </div>
                            <div class="pull-right"><i class="ti-plus"></i></div>
                            <div class="clearfix"></div>
                        </a>
                        <ul id="analysis-menu" class="collapse" data-parent="#sidebarnav">

                            <li> <a href="{{ route('medicalLaboratory.analysis.index') }}">
                                    {{ trans('backend/sidebar_trans.All_Analysis') }}</a> </li>

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
                                    href="{{ Route('medicalLaboratory.fees.index') }}">{{ trans('backend/sidebar_trans.All_Fees') }}</a>
                            </li>
                        </ul>
                    </li>



                    <li>
                        <a href="{{ Route('medicalLaboratory.settings.index') }}"><i class="fa-solid fa-cogs"></i><span
                                class="right-nav-text">
                                {{ trans('backend/sidebar_trans.Settings') }}</span> </a>
                    </li>



            </div>
        </div>

        <!-- Left Sidebar End-->

        <!--=================================
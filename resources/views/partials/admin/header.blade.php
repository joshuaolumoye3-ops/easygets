<body class="bg-black">
    <div class="flex relative overflow-hidden">
        <!-- partial:partials/_sidebar.html -->
        <!-- sidebar  -->
<nav id="sidebar"
    class="w-sidebar-expanded min-h-screen bg-navbar_bg font-normal p-0 z-10 transition-all duration-300 ease-in-out lg-max:fixed lg-max:overflow-auto lg-max:bottom-0 sidebar-offcanvas">
    <div
        class="bg-navbar_bg w-61 h-[4.38rem] lg-max:hidden transition-background duration-300 ease-in-out items-center justify-center fixed z-30">
        <a href="index.html" class=" mr-0 pl-4 w-full lg-max:hidden ">
            Easygets
        </a>
        <a href="index.html" class="mr-0 pl-4 w-full max-w-full h-7 lg-max:hidden">
            <img id="logo-mini" class=" w-12 h-8 max-w-full hidden pl-[1.6rem] lg-max:inline-block"
                src="https://demo.bootstrapdash.com/corona-tailwind/themes/assets/images/logo-mini.svg" alt="">
        </a>
    </div>
    <ul class="overflow-hidden flex-nowrap flex-col mb-16 pt-navbar-height lg-max:p-0 w-61">
        <!-- sidebar profile -->
        <li class="pr-5 p-0 lg-max:hidden">
            <div class="flex flex-row items-center py-[0.6rem] px-[1.17rem] leading-5">
                <div class="flex items-center">
                <svg class="rounded-full size-9" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64" fill="currentColor">
                <!-- Circle background -->
                <circle cx="32" cy="32" r="32" fill="#e5e7eb"/>
                
                <!-- Head -->
                <circle cx="32" cy="24" r="12" fill="#9ca3af"/>
                
                <!-- Shoulders -->
                <path d="M16 54c0-9 7-16 16-16s16 7 16 16" fill="#9ca3af"/>
            </svg>
                    {{-- <img class="rounded-full size-9" src="../assets/images/faces/face15.jpg" alt=""> --}}
                    <span
                        class="bg-modern-success z-10 absolute left-[43px] w-3 h-3 text-white rounded-full text-center leading-6 top-[109px] border-2 border-border_color">
                    </span>
                </div>
                <div class="ml-4 menu-title transition-all duration-300">
                   @auth('admins')
                        <h5 class="text-white mb-1 font-normal">
                            {{ Auth::guard('admins')->user()->name }}
                        </h5>
                        <span class="text-muted text-xs">
                            {{ ucfirst(Auth::guard('admins')->user()->role) }}
                        </span>
                    @endauth
                </div>
            </div>
            <a href="#"
                class="absolute menu-title left-[12.5rem] top-[5.5rem] text-muted dropdown-toggle lg-max:top-[1.5rem]">
                <i class="mdi mdi-dots-vertical text-muted"></i>
            </a>
            <div class="dropdown-menu fixed left-[12.5rem] top-[6.56rem] mt-2 w-48 bg-navbar_bg shadow-md shadow-black border-solid  border-muted z-50 text-muted"
                id="profileDropdown">
                <a href="#" class="flex items-center px-4 py-3 border-b-[1px] border-muted/15 hover:bg-black/20">
                    <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-black">
                        <i class="mdi mdi-cog text-modern-primary"></i>
                    </div>
                    <span class="ml-3 text-sm text-white">Account settings</span>
                </a>
                <a href="#" class="flex items-center px-4 py-3 border-b-[1px] border-muted/15 hover:bg-black/20">
                    <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-black">
                        <i class="mdi mdi-onepassword  text-modern-info"></i>
                    </div>
                    <span class="ml-3 text-sm text-white">Change Password</span>
                </a>
                <a href="#" class="flex items-center px-4 py-3 hover:bg-black/20">
                    <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-black">
                        <i class="mdi mdi-calendar-today text-modern-success"></i>
                    </div>
                    <span class="ml-3 text-sm text-white">To-do list</span>
                </a>
            </div>
        </li>
        <!-- sidebar profile end  -->
        <!-- sidebar nav  -->
        <li class="pr-5 nav-category">
            <span class="text-muted font-medium text-sm h-auto">Navigation</span>
        </li>
        <li class="relative pr-5 group menu-item ">
            <div class=" mr-2 submenu-container hover:bg-black hover:rounded-r-full">
                <a class="nav-link pt-[0.8rem] pr-[10px] pb-[0.8rem] pl-[1.188rem] flex items-center h-11 ms-3"
                    href="/admin">
                    <span
                        class=" text-modern-info mr-2 text-sm bg-muted/25 min-w-8 w-8 h-8 rounded-full flex items-center justify-center">
                        <!-- Circular Dashboard Icon -->
<svg xmlns="http://www.w3.org/2000/svg" 
     viewBox="0 0 24 24" 
     width="24" height="24" 
     fill="none" stroke="currentColor" 
     stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
     role="img" aria-labelledby="dashCircleTitle dashCircleDesc">

  <title id="dashCircleTitle">Dashboard</title>
  <desc id="dashCircleDesc">A circular dashboard icon divided into four sections</desc>

  <!-- Outer circle -->
  <circle cx="12" cy="12" r="10" stroke="currentColor"/>

  <!-- Divide into 4 quadrants -->
  <line x1="12" y1="2" x2="12" y2="22" stroke="currentColor"/>
  <line x1="2" y1="12" x2="22" y2="12" stroke="currentColor"/>

  <!-- Fill one quadrant slightly -->
  <path d="M12 12 L12 2 A10 10 0 0 1 22 12 Z" fill="currentColor" opacity="0.15"/>
</svg>

                    </span>
                    <span
                        class="menu-title c-menu-title text-inherit inline-block text-sm align-middle group-hover:text-white transition-all duration-300">Dashboard
                    </span>
                </a>
            </div>
            <ul class="submenu">
                <li class="submenu-title">
                    <div class="py-3 font-medium text-white flex items-center">
                        Dashboard
                    </div>
                </li>
            </ul>
        </li>
        <li class="relative pr-5 group menu-item">
            <div class="mr-2 submenu-container hover:bg-black hover:rounded-r-full">
                <a class="nav-link pt-[0.8rem] pr-[10px] pb-[0.8rem] pl-[1.188rem] flex items-center h-11 ms-3"
                    href="pages/widgets/widgets.html">
                    <span
                        class="text-modern-warning mr-2 text-sm bg-muted/25 min-w-8  w-8 h-8 rounded-full flex items-center justify-center">
                        <i class="mdi mdi-texture"></i>
                    </span>
                    <span class="menu-title group-hover:text-white transition-all duration-300 ease-in-out">
                        Admin List
                    </span>
                </a>
            </div>
            <ul class="submenu">
                <li class="submenu-title">
                    <div class="py-3 font-medium text-white flex items-center">
                        Admin List
                    </div>
                </li>
            </ul>
        </li>

        <li class="relative pr-5 group menu-item">
            <div class="mr-2 submenu-container hover:bg-black hover:rounded-r-full text-nowrap">
                <a data-collapse-toggle="collapse-3"
                    class="nav-link pt-[0.8rem] pr-[10px] pb-[0.8rem] pl-[1.188rem] flex items-center h-11 ms-3"
                    href="#">
                    <span class="text-modern-success mr-2 text-sm bg-muted/25
                             min-w-8 w-8 h-8 rounded-full flex items-center justify-center">
                        <i class="mdi mdi-laptop"></i>
                    </span>
                    <span
                        class="menu-title group-hover:text-white transition-all duration-300 ease-in-out whitespace-nowrap">
                        Basic UI Elements
                    </span>
                    <i class="menu-arrow"></i>
                </a>
            </div>
            <ul id="collapse-3"
                class="submenu flex-col overflow-hidden transition-[max-height] duration-500 ease-in-out max-h-0 pl-[3.25rem]">
                <li class="submenu-title ">
                    <div class="py-3 font-medium text-white flex items-center">
                        Basic UI Elements
                    </div>
                </li>
                <li class="pr-5 submenu-item">
                    <a class="nav-link block py-2 transition-colors duration-200"
                        href="pages/ui-featues/accordions.html">Accordions</a>
                </li>
                <li class="pr-5 submenu-item">
                    <a class="nav-link block py-2 transition-colors duration-200"
                        href="pages/ui-featues/buttons.html">Buttons</a>
                </li>
                <li class="pr-5 submenu-item">
                    <a class="nav-link block py-2 transition-colors duration-200"
                        href="pages/ui-featues/badges.html">Badges</a>
                </li>
                <li class="pr-5 submenu-item">
                    <a class="nav-link block py-2 transition-colors duration-200"
                        href="pages/ui-featues/breadcrumbs.html">Breadcrumbs</a>
                </li>
                <li class="pr-5 submenu-item">
                    <a class="nav-link block py-2 transition-colors duration-200"
                        href="pages/ui-featues/dropdown.html">Dropdowns</a>
                </li>
                <li class="pr-5 submenu-item">
                    <a class="nav-link block py-2 transition-colors duration-200"
                        href="pages/ui-featues/modal.html">Modals</a>
                </li>
                <li class="pr-5 submenu-item">
                    <a class="nav-link block py-2 transition-colors duration-200"
                        href="pages/ui-featues/progressbar.html">Progress Bar</a>
                </li>
                <li class="pr-5 submenu-item">
                    <a class="nav-link block py-2 transition-colors duration-200"
                        href="pages/ui-featues/pagination.html">Pagination</a>
                </li>
                <li class="pr-5 submenu-item">
                    <a class="nav-link block py-2 transition-colors duration-200"
                        href="pages/ui-featues/tabs.html">Tabs</a>
                </li>
            </ul>
        </li>
        <li class="relative pr-5 group menu-item">
            <div class="mr-2 submenu-container hover:bg-black hover:rounded-r-full">
                <a data-collapse-toggle="collapse-4"
                    class="nav-link pt-[0.8rem] pr-[10px] pb-[0.8rem] pl-[1.188rem] w-full flex items-center h-11 ms-3"
                    href="#">
                    <span class="text-modern-info mr-2 text-sm bg-muted/25
                             min-w-8 w-8 h-8 rounded-full flex items-center justify-center">
                        <i class="mdi mdi-cog"></i>
                    </span>
                    <span
                        class="menu-title group-hover:text-white transition-all duration-300 ease-in-out whitespace-nowrap">
                        Advanced Elements
                    </span>
                    <i class="menu-arrow"></i>
                </a>
            </div>
            <div>
                <ul id="collapse-4"
                    class="submenu flex-col overflow-hidden transition-[max-height] duration-500 ease-in-out max-h-0 pl-[3.25rem]">
                    <li class="submenu-title ">
                        <div class="py-3 font-medium text-white flex items-center">
                            Advanced Elements
                        </div>
                    </li>
                    <li class="pr-5 submenu-item">
                        <a class="nav-link block py-2 transition-colors duration-200"
                            href="pages/advanced-ui/dragula.html">Dragula</a>
                    </li>
                    <li class="pr-5 submenu-item">
                        <a class="nav-link block py-2 transition-colors duration-200"
                            href="pages/advanced-ui/clipboard.html">Clipboard</a>
                    </li>
                    <li class="pr-5 submenu-item">
                        <a class="nav-link block py-2 transition-colors duration-200"
                            href="pages/advanced-ui/context-menu.html">Context Menu</a>
                    </li>
                    <li class="pr-5 submenu-item">
                        <a class="nav-link block py-2 transition-colors duration-200"
                            href="pages/advanced-ui/slider.html">Slider</a>
                    </li>
                    <li class="pr-5 submenu-item">
                        <a class="nav-link block py-2 transition-colors duration-200"
                            href="pages/advanced-ui/loaders.html">Loaders</a>
                    </li>
                    <li class="pr-5 submenu-item">
                        <a class="nav-link block py-2 transition-colors duration-200"
                            href="pages/advanced-ui/carousel.html">Carousel</a>
                    </li>
                    <li class="pr-5 submenu-item">
                        <a class="nav-link block py-2 transition-colors duration-200"
                            href="pages/advanced-ui/tooltips.html">Tooltips</a>
                    </li>
                </ul>
            </div>
        </li>
        {{-- <li class="relative pr-5 group menu-item">
            <div class="mr-2 submenu-container hover:bg-black hover:rounded-r-full">
                <a data-collapse-toggle="collapse-5"
                    class="nav-link pt-[0.8rem] pr-[10px] pb-[0.8rem] pl-[1.188rem] flex items-center h-11 ms-3"
                    href="#">
                    <span class="text-modern-warning mr-2 text-sm bg-muted/25
                             min-w-8 w-8 h-8 rounded-full flex items-center justify-center">
                        <i class="mdi mdi-playlist-play"></i>
                    </span>
                    <span
                        class="menu-title group-hover:text-white transition-all duration-300 ease-in-out whitespace-nowrap">
                        Form Elements
                    </span>
                    <i class="menu-arrow"></i>
                </a>
            </div>

            <ul id="collapse-5"
                class="submenu flex-col overflow-hidden transition-[max-height] duration-500 ease-in-out max-h-0 pl-[3.25rem]">
                <li class="submenu-title ">
                    <div class="py-3 font-medium text-white flex items-center">
                        Form Elements
                    </div>
                </li>
                <li class="pr-5 submenu-item">
                    <a class="nav-link block py-2 transition-colors duration-200"
                        href="pages/forms/basic-form.html">Basic
                        Forms</a>
                </li>
                <li class="pr-5 submenu-item">
                    <a class="nav-link block py-2 transition-colors duration-200"
                        href="pages/forms/advanced-form.html">Advanced
                        Forms</a>
                </li>
                <!-- <li class="pr-5 submenu-item">
                    <a class="nav-link block py-2 transition-colors duration-200" href="">Validation</a>
                </li> -->
                <li class="pr-5 submenu-item">
                    <a class="nav-link block py-2 transition-colors duration-200"
                        href="pages/forms/wizard.html">Wizard</a>
                </li>
            </ul>
        </li>
        <li class="relative pr-5 group menu-item">
            <div class="mr-2 submenu-container hover:bg-black hover:rounded-r-full">
                <a data-collapse-toggle="collapse-6"
                    class="nav-link pt-[0.8rem] pr-[10px] pb-[0.8rem] pl-[1.188rem] flex items-center h-11 ms-3"
                    href="#">
                    <span class="text-modern-danger mr-2 text-sm bg-muted/25
                             min-w-8 w-8 h-8 rounded-full flex items-center justify-center"><i
                            class="mdi mdi-table-large"></i></span>
                    <span
                        class="menu-title group-hover:text-white transition-all duration-300 ease-in-out">Tables</span>
                    <i class="menu-arrow"></i>
                </a>
            </div>
            <ul id="collapse-6"
                class="submenu flex-col overflow-hidden transition-[max-height] duration-500 ease-in-out max-h-0 pl-[3.25rem]">
                <li class="submenu-title ">
                    <div class="py-3 font-medium text-white flex items-center">
                        Tables
                    </div>
                </li>
                <li class="pr-5 submenu-item">
                    <a class="nav-link block py-2 transition-colors duration-200"
                        href="pages/tables/basic-tables.html">Basic
                        Tables</a>
                </li>
                <li class="pr-5 submenu-item">
                    <a class="nav-link block py-2 transition-colors duration-200"
                        href="pages/tables/data-tables.html">Data
                        Table</a>
                </li>
                <li class="pr-5 submenu-item">
                    <a class="nav-link block py-2 transition-colors duration-200"
                        href="pages/tables/js-grid.html">Js-grid</a>
                </li>
                <li class="pr-5 submenu-item">
                    <a class="nav-link block py-2 transition-colors duration-200"
                        href="pages/tables/sortable-tables.html">
                        Sortable Table
                    </a>
                </li>
            </ul>
        </li>
        <li class="relative pr-5 group menu-item">
            <div class="mr-2 submenu-container hover:bg-black hover:rounded-r-full">
                <a data-collapse-toggle="collapse-7"
                    class="nav-link pt-[0.8rem] pr-[10px] pb-[0.8rem] pl-[1.188rem] flex items-center h-11 ms-3"
                    href="#">
                    <span class="text-modern-primary mr-2 text-sm bg-muted/25
                             min-w-8 w-8 h-8 rounded-full flex items-center justify-center"><i
                            class="mdi mdi-format-text"></i></span>
                    <span class="menu-title group-hover:text-white transition-all duration-300 ease-in-out">
                        Editors
                    </span>
                    <i class="menu-arrow"></i>
                </a>
            </div>
            <ul id="collapse-7"
                class="submenu flex-col overflow-hidden transition-all duration-200 max-h-0 pl-[3.25rem]">
                <li class="submenu-title ">
                    <div class="py-3 font-medium text-white flex items-center">
                        Editors
                    </div>
                </li>
                <li class="pr-5 submenu-item">
                    <a class="nav-link block py-2 transition-colors duration-200"
                        href="pages/editors/text-editors.html">Text
                        Editors</a>
                </li>

            </ul>
        </li>
        <li class="relative pr-5 group menu-item">
            <div class="mr-2 submenu-container hover:bg-black hover:rounded-r-full">
                <a data-collapse-toggle="collapse-8"
                    class="nav-link pt-[0.8rem] pr-[10px] pb-[0.8rem] pl-[1.188rem] flex items-center h-11 ms-3"
                    href="#">
                    <span class="text-modern-success mr-2 text-sm bg-muted/25
                             min-w-8 w-8 h-8 rounded-full flex items-center justify-center"><i
                            class="mdi mdi-chart-bar"></i></span>
                    <span class="menu-title group-hover:text-white transition-all duration-300 ease-in-out">
                        Charts
                    </span>
                    <i class="menu-arrow"></i>
                </a>
            </div>
            <ul id="collapse-8"
                class="submenu flex-col overflow-hidden transition-[max-height] duration-500 ease-in-out max-h-0 pl-[3.25rem]">
                <li class="submenu-title ">
                    <div class="py-3 font-medium text-white flex items-center">
                        Charts
                    </div>
                </li>
                <li class="pr-5 submenu-item">
                    <a class="nav-link block py-2 transition-colors duration-200"
                        href="pages/charts/chartjs.html">ChartsJs</a>
                </li>
                <li class="pr-5 submenu-item">
                    <a class="nav-link block py-2 transition-colors duration-200"
                        href="pages/charts/apex.html">Apex</a>
                </li>
                <li class="pr-5 submenu-item">
                    <a class="nav-link block py-2 transition-colors duration-200"
                        href="pages/charts/billboard.html">Billboard</a>
                </li>
                <li class="pr-5 submenu-item">
                    <a class="nav-link block py-2 transition-colors duration-200" href="pages/charts/gauge.html">Just
                        Gauge</a>
                </li>
            </ul>
        </li>
        <li class="relative pr-5 group menu-item">
            <div class="mr-2 submenu-container hover:bg-black hover:rounded-r-full">
                <a data-collapse-toggle="collapse-9"
                    class="nav-link pt-[0.8rem] pr-[10px] pb-[0.8rem] pl-[1.188rem] flex items-center h-11 ms-3"
                    href="#">
                    <span class="text-modern-info mr-2 text-sm bg-muted/25
                             min-w-8 w-8 h-8 rounded-full flex items-center justify-center">
                        <i class="mdi mdi-map-marker-radius"></i>
                    </span>
                    <span class="menu-title group-hover:text-white transition-all duration-300 ease-in-out">
                        Maps
                    </span>
                    <i class="menu-arrow"></i>
                </a>
            </div>
            <ul id="collapse-9"
                class="submenu flex-col overflow-hidden transition-[max-height] duration-500 ease-in-out max-h-0 pl-[3.25rem]">
                <li class="submenu-title ">
                    <div class="py-3 font-medium text-white flex items-center">
                        Maps
                    </div>
                </li>
                <li class="pr-5 submenu-item">
                    <a class="nav-link block py-2 transition-colors duration-200"
                        href="pages/maps/google-maps.html">Google
                        Maps</a>
                </li>
                <li class="pr-5 submenu-item">
                    <a class="nav-link block py-2 transition-colors duration-200"
                        href="pages/maps/leaflet.html">Leaflet</a>
                </li>
                <li class="pr-5 submenu-item">
                    <a class="nav-link block py-2 transition-colors duration-200"
                        href="pages/maps/open-layer.html">OpenLayers
                        Map</a>
                </li>
            </ul>
        </li>
        <li class="relative pr-5 group menu-item">
            <div class="mr-2 submenu-container hover:bg-black hover:rounded-r-full">
                <a class="nav-link pt-[0.8rem] pr-[10px] pb-[0.8rem] pl-[1.188rem] flex items-center h-11 ms-3"
                    href="pages/notifications/notifications.html">
                    <span
                        class=" text-modern-warning mr-2 text-sm bg-muted/25 min-w-8 w-8 h-8 rounded-full flex items-center justify-center">
                        <i class="mdi mdi-bell-ring"></i>
                    </span>
                    <span
                        class="menu-title text-inherit inline-block text-sm align-middle group-hover:text-white transition-all duration-300 ease-in-out">
                        Notification
                    </span>
                </a>
            </div>
            <ul class="submenu">
                <li class="submenu-title">
                    <div class="py-3 font-medium text-white flex items-center">
                        Notification
                    </div>
                </li>
            </ul>
        </li>
        <li class="relative pr-5 group menu-item">
            <div class="mr-2 submenu-container hover:bg-black hover:rounded-r-full">
                <a class="nav-link pt-[0.8rem] pr-[10px] pb-[0.8rem] pl-[1.188rem] flex items-center h-11 ms-3"
                    href="pages/notifications/popups.html">
                    <span
                        class=" text-modern-primary mr-2 text-sm bg-muted/25 min-w-8 w-8 h-8 rounded-full flex items-center justify-center">
                        <i class="mdi mdi-forum"></i>
                    </span>
                    <span
                        class="menu-title text-inherit inline-block text-sm align-middle group-hover:text-white transition-all duration-300 ease-in-out">
                        Popups
                    </span>
                </a>
            </div>
            <ul class="submenu">
                <li class="submenu-title">
                    <div class="py-3 font-medium text-white flex items-center">
                        Popups
                    </div>
                </li>
            </ul>
        </li>
        <li class="pr-5 nav-category">
            <span class="text-muted font-medium text-sm h-auto">More</span>
        </li>
        <li class="relative pr-5 group menu-item">
            <div class="mr-2 submenu-container hover:bg-black hover:rounded-r-full">
                <a data-collapse-toggle="collapse-11"
                    class="nav-link pt-[0.8rem] pr-[10px] pb-[0.8rem] pl-[1.188rem] flex items-center h-11 ms-3"
                    href="#">
                    <span class="text-modern-success mr-2 text-sm bg-muted/25
                                            min-w-8 w-8 h-8 rounded-full flex items-center justify-center">
                        <i class="mdi mdi-security"></i>
                    </span>
                    <span
                        class="menu-title group-hover:text-white transition-all duration-300 ease-in-out whitespace-nowrap">
                        User Pages
                    </span>
                    <i class="menu-arrow"></i>
                </a>
            </div>
            <ul id="collapse-11"
                class="submenu flex-col overflow-hidden transition-[max-height] duration-500 ease-in-out max-h-0 pl-[3.25rem]">
                <li class="submenu-title ">
                    <div class="py-3 font-medium text-white flex items-center">
                        User Pages
                    </div>
                </li>
                <li class="pr-5 submenu-item">
                    <a class="nav-link block py-2 transition-colors duration-200"
                        href="pages/user-pages/login.html">Login</a>
                </li>
                <li class="pr-5 submenu-item">
                    <a class="nav-link block py-2 transition-colors duration-200"
                        href="pages/user-pages/login-2.html">Login
                        2</a>
                </li>
                <li class="pr-5 submenu-item">
                    <a class="nav-link block py-2 transition-colors duration-200"
                        href="pages/user-pages/register.html">Register</a>
                </li>
                <li class="pr-5 submenu-item">
                    <a class="nav-link block py-2 transition-colors duration-200"
                        href="pages/user-pages/register-2.html">Register
                        2</a>
                </li>
            </ul>
        </li>
        <li class="relative pr-5 group menu-item">
            <div class="mr-2 submenu-container hover:bg-black hover:rounded-r-full">
                <a data-collapse-toggle="collapse-12"
                    class="nav-link pt-[0.8rem] pr-[10px] pb-[0.8rem] pl-[1.188rem] flex items-center h-11 ms-3"
                    href="#">
                    <span class="text-modern-info mr-2 text-sm bg-muted/25
                            min-w-8 w-8 h-8 rounded-full flex items-center justify-center">
                        <i class="mdi mdi-earth"></i>
                    </span>
                    <span
                        class="menu-title group-hover:text-white transition-all duration-300 ease-in-out whitespace-nowrap">
                        General Pages
                    </span>
                    <i class="menu-arrow"></i>
                </a>
            </div>
            <div>
                <ul id="collapse-12"
                    class="submenu flex-col overflow-hidden transition-[max-height] duration-500 ease-in-out max-h-0 pl-[3.25rem]">
                    <li class="submenu-title ">
                        <div class="py-3 font-medium text-white flex items-center">
                            General Pages
                        </div>
                    </li>
                    <li class="pr-5 submenu-item">
                        <a class="nav-link block py-2 transition-colors duration-200"
                            href="pages/general-pages/blank-page.html">Blank
                            Page</a>
                    </li>
                    <li class="pr-5 submenu-item">
                        <a class="nav-link block py-2 transition-colors duration-200"
                            href="pages/general-pages/profile.html">Profile
                        </a>
                    </li>
                    <li class="pr-5 submenu-item"><a class="nav-link block py-2 transition-colors duration-200"
                            href="pages/general-pages/faq.html">FAQ
                        </a>
                    </li>
                    <li class="pr-5 submenu-item">
                        <a class="nav-link block py-2 transition-colors duration-200"
                            href="pages/general-pages/search-results.html">Search
                            Results</a>
                    </li>
                    <li class="pr-5 submenu-item">
                        <a class="nav-link block py-2 transition-colors duration-200"
                            href="pages/general-pages/news-grid.html">News
                            Grid</a>
                    </li>
                    <li class="pr-5 submenu-item">
                        <a class="nav-link block py-2 transition-colors duration-200"
                            href="pages/general-pages/timeline.html">Timeline
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        <li class="relative pr-5 group menu-item">
            <div class="mr-2 submenu-container hover:bg-black hover:rounded-r-full">
                <a data-collapse-toggle="collapse-13"
                    class="nav-link pt-[0.8rem] pr-[10px] pb-[0.8rem] pl-[1.188rem] flex items-center h-11 ms-3"
                    href="#">
                    <span class="text-modern-warning mr-2 text-sm bg-muted/25
                            min-w-8 w-8 h-8 rounded-full flex items-center justify-center">
                        <i class="mdi mdi-lock"></i>
                    </span>
                    <span
                        class="menu-title group-hover:text-white transition-all duration-300 ease-in-out whitespace-nowrap">
                        Error Pages
                    </span>
                    <i class="menu-arrow"></i>
                </a>
            </div>

            <div>
                <ul id="collapse-13"
                    class="submenu flex-col overflow-hidden transition-[max-height] duration-500 ease-in-out max-h-0 pl-[3.25rem]">
                    <li class="submenu-title ">
                        <div class="py-3 font-medium text-white flex items-center">
                            Error Pages
                        </div>
                    </li>
                    <li class="pr-5 submenu-item">
                        <a class="nav-link block py-2 transition-colors duration-200"
                            href="pages/error-pages/404.html">404</a>
                    </li>
                    <li class="pr-5 submenu-item">
                        <a class="nav-link block py-2 transition-colors duration-200"
                            href="pages/error-pages/500.html">500</a>
                    </li>
                </ul>
            </div>
        </li>
        <li class="relative pr-5 group menu-item">
            <div class="mr-2 submenu-container hover:bg-black hover:rounded-r-full">
                <a data-collapse-toggle="collapse-14"
                    class="nav-link pt-[0.8rem] pr-[10px] pb-[0.8rem] pl-[1.188rem] flex items-center h-11 ms-3"
                    href="#">
                    <span class="text-modern-danger mr-2 text-sm bg-muted/25
                            min-w-8 w-8 h-8 rounded-full flex items-center justify-center">
                        <i class="mdi mdi-medical-bag"></i>
                    </span>
                    <span
                        class="menu-title group-hover:text-white transition-all duration-300 ease-in-out whitespace-nowrap">
                        E-commerce
                    </span>
                    <i class="menu-arrow"></i>
                </a>
            </div>
            <div>
                <ul id="collapse-14"
                    class="submenu flex-col overflow-hidden transition-[max-height] duration-500 ease-in-out max-h-0 pl-[3.25rem]">
                    <li class="submenu-title ">
                        <div class="py-3 font-medium text-white flex items-center">
                            E-commerce
                        </div>
                    </li>
                    <li class="pr-5 submenu-item">
                        <a class="nav-link block py-2 transition-colors duration-200"
                            href="pages/ecommerce/invoice.html">Invoice</a>
                    </li>
                    <li class="pr-5 submenu-item">
                        <a class="nav-link block py-2 transition-colors duration-200"
                            href="pages/ecommerce/pricing.html">Pricing
                            Table</a>
                    </li>
                    <li class="pr-5 submenu-item">
                        <a class="nav-link block py-2 transition-colors duration-200"
                            href="pages/ecommerce/orders.html">Orders</a>
                    </li>
                </ul>
            </div>
        </li>
        <li class="relative pr-5 group menu-item">
            <div class="mr-2 submenu-container hover:bg-black hover:rounded-r-full">
                <a class="nav-link pt-[0.8rem] pr-[10px] pb-[0.8rem] pl-[1.188rem] flex items-center h-11 ms-3"
                    href="pages/apps/calendar.html">
                    <span
                        class=" text-modern-primary mr-2 text-sm bg-muted/25 min-w-8 w-8 h-8 rounded-full flex items-center justify-center">
                        <i class="mdi mdi-calendar-today"></i>
                    </span>
                    <span
                        class="menu-title text-inherit inline-block text-sm align-middle group-hover:text-white transition-all duration-300 ease-in-out">Calendar</span>
                </a>
            </div>
            <ul class="submenu">
                <li class="submenu-title">
                    <div class="py-3 font-medium text-white flex items-center">
                        Calendar
                    </div>
                </li>
            </ul>
        </li>
        <li class="relative pr-5 group menu-item">
            <div class="mr-2 submenu-container hover:bg-black hover:rounded-r-full">
                <a class="nav-link pt-[0.8rem] pr-[10px] pb-[0.8rem] pl-[1.188rem] flex items-center h-11 ms-3"
                    href="pages/apps/todo.html">
                    <span
                        class=" text-modern-success mr-2 text-sm bg-muted/25 min-w-8 w-8 h-8 rounded-full flex items-center justify-center">
                        <i class="mdi mdi-bulletin-board"></i>
                    </span>
                    <span
                        class="menu-title text-inherit inline-block text-sm align-middle group-hover:text-white transition-all duration-300 ease-in-out whitespace-nowrap">
                        Todo List
                    </span>
                </a>
            </div>
            <ul class="submenu">
                <li class="submenu-title">
                    <div class="py-3 font-medium text-white flex items-center">
                        Todo List
                    </div>
                </li>
            </ul>
        </li>
        <li class="relative pr-5 group menu-item ">
            <div class=" mr-2 submenu-container hover:bg-black hover:rounded-r-full">
                <a class="nav-link pt-[0.8rem] pr-[10px] pb-[0.8rem] pl-[1.188rem] flex items-center h-11 ms-3"
                    href="pages/apps/email.html">
                    <span
                        class=" text-modern-info mr-2 text-sm bg-muted/25 min-w-8 w-8 h-8 rounded-full flex items-center justify-center">
                        <i class="mdi mdi-email"></i>
                    </span>
                    <span
                        class="menu-title text-inherit inline-block text-sm align-middle group-hover:text-white transition-all duration-300 ease-in-out whitespace-nowrap">
                        E-mail
                    </span>
                </a>
            </div>
            <ul class="submenu">
                <li class="submenu-title">
                    <div class="py-3 font-medium text-white flex items-center">
                        E-mail
                    </div>
                </li>
            </ul>
        </li>
        <li class="relative pr-5 group menu-item">
            <div class="mr-2 submenu-container hover:bg-black hover:rounded-r-full">
                <a class="nav-link pt-[0.8rem] pr-[10px] pb-[0.8rem] pl-[1.188rem] flex items-center h-11 ms-3"
                    href="pages/apps/gallery.html">
                    <span
                        class=" text-modern-warning mr-2 text-sm bg-muted/25 min-w-8 w-8 h-8 rounded-full flex items-center justify-center">
                        <i class="mdi mdi-image-filter-center-focus-weak"></i>
                    </span>
                    <span
                        class="menu-title text-inherit inline-block text-sm align-middle group-hover:text-white transition-all duration-300 ease-in-out">Gallery</span>
                </a>
            </div>
            <ul class="submenu">
                <li class="submenu-title">
                    <div class=" py-3 font-medium text-white flex items-center">
                        Gallery
                    </div>
                </li>
            </ul>
        </li>
        <li class="relative pr-5 group menu-item ">
            <div class="mr-2 submenu-container hover:bg-black hover:rounded-r-full">
                <a class="nav-link pt-[0.8rem] pr-[10px] pb-[0.8rem] pl-[1.188rem] flex items-center h-11 ms-3"
                    href="pages/docs/documentation.html" target="_blank">
                    <span
                        class=" text-modern-danger mr-2 text-sm bg-muted/25 min-w-8 w-8 h-8 rounded-full flex items-center justify-center">
                        <i class="mdi mdi-file-document"></i>
                    </span>
                    <span
                        class="menu-title text-inherit inline-block text-sm align-middle group-hover:text-white transition-all duration-300 ease-in-out">Documentation</span>
                </a>
            </div>
            <ul class="submenu">
                <li class="submenu-title">
                    <div class="py-3 font-medium text-white flex items-center">
                        Documentation
                    </div>
                </li>
            </ul>
        </li> --}}
        <!-- sidebar nav end  -->
    </ul>
</nav>
        <!-- partial -->
        <div
            class="flex flex-row min-h-[calc(100vh-70px)] pl-0 pr-0 transition-all duration-200 ease-linear w-full lg-max:w-full">
            <!-- partial:partials/_navbar.html -->
            <!-- navbar  -->
<nav id="navbar"
    class="bg-navbar_bg font-normal left-[15.25rem] right-0 lg-max:left-0 p-0 fixed top-0 z-50 flex flex-row transition-all duration-300 ease-in-out">
    <div class="bg-navbar_bg w-[15.25rem] h-[4.38rem] lg-max:w-14 flex lg:hidden items-center justify-center">
        <a class="text-gray_dark/20 text-2xl/10 mr-0 p-[0rem_0rem_0rem_1.3rem] w-full" href="index.html">
            <img class="w-5 max-w-full h-7 m-auto" src="https://demo.bootstrapdash.com/corona-tailwind/themes/assets/images/logo-mini.svg" alt="">
        </a>
    </div>
    <div
        class="text-muted shadow-[20px_19px_34px_-15px_rgba(0,0,0,0.5)] pl-[0.94rem] pr-[0.94rem] w-full h-[4.4rem] flex-grow flex items-stretch">
        <button class="h-[4.4rem] border-none self-center lg-max:hidden" id="sidebarToggler">
            <span><svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg></span>
        </button>
        <ul class="flex flex-row items-center flex-grow">
            <li class="w-full">
                <form
                    class="text-inherit text-[0.9375rem] mr-4 ml-4 whitespace-nowrap md-max:mr-[0.8rem] md-max:ml-[0.8rem] mt-1 md-max:mt-0 hidden lg:flex"
                    action="#">
                    <input
                        class="bg-card_bg p-[13px_20px_11px_20px] border-[1px] border-[#2c2e33] placeholder:text-muted h-[2.575rem] font-normal text-[0.875rem] text-white rounded-sm w-3/4"
                        type="text" placeholder="Search products">
                </form>
            </li>
        </ul>
        <ul class="flex flex-row items-center gap-2 lg:w-auto  lg-max:mr-0 text-white">
            @auth('admins')
                @if(Auth::guard('admins')->user()->role === 'super_admin')
                    <li class="hidden lg:block relative">
                        <a class="p-2 rounded-md text-inherit text-[0.9375rem] ml-4 mr-4 whitespace-nowrap 
                        hover:bg-modern-success/80 hover:text-white hover:transition-colors 
                        hover:duration-500 hover:ease-in-out hover:border-modern-success hover:p-2"
                        href="#" onclick="document.getElementById('addAdminModal').classList.remove('hidden')">
                            + Add New Admin
                        </a>
                    </li>
                @endif
            @endauth
    <li class="hidden lg:block">
        <a class="text-inherit text-[0.9375rem] ml-4 mr-4 whitespace-nowrap md-max:mr-[0.8rem] md-max:[0.8rem]" href="#"">
                     <i class=" mdi mdi-view-grid"></i>
        </a>
    </li>
    <li class="relative">
        <a class="text-inherit text-[0.9375rem] ml-4 mr-4 whitespace-nowrap md-max:mr-[0.8rem] md-max:[0.8rem] relative dropdown-toggle3"
            href="#">
            <i class=" mdi mdi-email"></i>
            <span
                class="bg-modern-success z-10 absolute w-3 h-3 text-white rounded-full text-center leading-6 left-[66%]  border-2 border-border_color">
            </span>
        </a>
        <div class="dropdown-menu hidden absolute right-0 top-full mt-2 w-64 bg-navbar_bg shadow-[0px_0px_35px_-3px_rgba(0,0,0)] border-solid rounded-md border-muted z-50 text-white"
            id="mailDropdown">
            <h6 class="p-3 mb-0 text-sm font-bold">Messages</h6>
            <a href="#"
                class="flex items-center px-4 py-3 border-b-[1px] border-t-[1px] border-muted/15 hover:bg-black/20">
                <div class="text-white relative">
                    <img src="../assets/images/faces/face4.jpg" alt="image"
                        class="rounded-full flex items-center size-9">
                </div>
                <div class="pl-4 leading-[1] w-52 text-xs">
                    <p class="mb-1 text-ellipsis overflow-hidden whitespace-nowrap">Mark send you a
                        message</p>
                    <p class="text-muted mb-0 text-xs"> 1 Minutes ago </p>
                </div>
            </a>
            <a href="#" class="flex items-center px-4 py-3 border-b-[1px] border-muted/15 hover:bg-black/20">
                <div class="text-white relative">
                    <img src="../assets/images/faces/face2.jpg" alt="image"
                        class="rounded-full flex items-center size-9">
                </div>
                <div class="pl-4 leading-[1] w-52 text-xs">
                    <p class="mb-1 text-ellipsis overflow-hidden whitespace-nowrap">Cregh send you a
                        message</p>
                    <p class="text-muted mb-0 text-xs">15 Minutes ago</p>
                </div>
            </a>
            <a href="#" class="flex items-center px-4 py-3 border-b-[1px] border-muted/15 hover:bg-black/20">
                <div class="text-white relative">
                    <img src="../assets/images/faces/face3.jpg" alt="image"
                        class="rounded-full flex items-center size-9">
                </div>
                <div class="pl-4 leading-[1] w-52 text-xs">
                    <p class="mb-1 text-ellipsis overflow-hidden whitespace-nowrap">Profile picture
                        updated</p>
                    <p class="text-muted mb-0 text-xs">18 Minutes ago</p>
                </div>
            </a>
            <p class="p-3 mb-0 text-center text-sm">4 New Messages</p>
        </div>
    </li>
    <li class="relative">
        <a class="text-inherit text-[0.9375rem] ml-4 mr-4 whitespace-nowrap md-max:mr-[0.8rem] md-max:[0.8rem] relative dropdown-toggle4"
            href="#">
            <svg xmlns="http://www.w3.org/2000/svg" 
     fill="none" viewBox="0 0 24 24" stroke="currentColor" 
     class="w-6 h-6">
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11
           a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165
           6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4
           17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
</svg>
            <span
                class="bg-modern-danger z-10 absolute w-3 h-3 text-white rounded-full text-center leading-6 border-2 left-[66%] border-border_color">
            </span>
        </a>
        <div class="dropdown-menu hidden absolute right-0 top-full mt-2 w-64 bg-navbar_bg shadow-[0px_0px_35px_-3px_rgba(0,0,0)] border-solid rounded-md border-muted z-50 text-white"
            id="notificationDropdown">
            <h6 class="p-3 mb-0 text-sm font-bold">Notifications</h6>
            <a href="#"
                class="flex items-center px-4 py-3 border-b-[1px] border-t-[1px] border-muted/15 hover:bg-black/20">
                <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-dark">
                    <i class="mdi mdi-calendar text-modern-success"></i>
                </div>
                <div class="pl-4 leading-[1] w-52 text-xs">
                    <p class="text-ellipsis overflow-hidden mb-1">Event today</p>
                    <p class="text-muted mb-0 mr-2 text-sm text-ellipsis overflow-hidden whitespace-nowrap">
                        Just a reminder
                        that you
                        have an event today</p>
                </div>
            </a>
            <a href="#" class="flex items-center px-4 py-3 border-b-[1px] border-muted/15 hover:bg-black/20">
                <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-dark">
                    <i class="mdi mdi-cog text-modern-danger"></i>
                </div>
                <div class="pl-4 leading-[1] w-52 text-xs">
                    <p class="text-ellipsis overflow-hidden mb-1">Settings</p>
                    <p class="text-muted mb-0 text-ellipsis overflow-hidden text-sm whitespace-nowrap">
                        Update dashboard</p>
                </div>
            </a>
            <a href="#" class="flex items-center px-4 py-3 border-b-[1px] border-muted/15 hover:bg-black/20">
                <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-dark">
                    <i class="mdi mdi-link-variant text-modern-warning"></i>
                </div>
                <div class="pl-4 leading-[1] w-52 text-xs">
                    <p class="text-ellipsis overflow-hidden mb-1">Launch Admin</p>
                    <p class="text-muted mb-0 text-sm text-ellipsis overflow-hidden whitespace-nowrap">
                        18 Minutes ago</p>
                </div>
            </a>
            <p class="p-3 mb-0 text-center text-sm">See All Notifications</p>
        </div>
    </li>
    <li class="relative">
        <a class="text-inherit text-[0.9375rem] ml-4 mr-4 whitespace-nowrap md-max:mr-[0.8rem] md-max:[0.8rem] dropdown-toggle5"
            href="#">
            <div class="flex font-normal items-center">
            <svg class="rounded-full size-9" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64" fill="currentColor">
                <!-- Circle background -->
                <circle cx="32" cy="32" r="32" fill="#e5e7eb"/>
                
                <!-- Head -->
                <circle cx="32" cy="24" r="12" fill="#9ca3af"/>
                
                <!-- Shoulders -->
                <path d="M16 54c0-9 7-16 16-16s16 7 16 16" fill="#9ca3af"/>
            </svg>
                {{-- <img class="rounded-full size-9" src="../assets/images/faces/face15.jpg" alt=""> --}}
                 @auth('admins')
                <p class="mb-0 hidden sm:block whitespace-nowrap ml-4"> {{ Auth::guard('admins')->user()->name }}</p>
                @endauth
                <i class="text-[#a7afb7]"></i>
            </div>
        </a>
        <div class="dropdown-menu hidden absolute right-0 top-full mt-2 w-40 bg-navbar_bg shadow-[0px_0px_35px_-3px_rgba(0,0,0)] border-solid rounded-md border-muted z-50 text-white"
            id="navProfileDropdown">
            <h6 class="p-3 mb-0 text-sm font-bold">Profile</h6>
            <a href="#"
                class="flex items-center px-4 py-3 border-b-[1px] border-t-[1px] border-muted/15 hover:bg-black/20">
                <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-dark">
                    <i class="mdi mdi-cog text-modern-success"></i>
                </div>
                <span class="ml-3 text-sm text-white">Settings</span>
            </a>
            <a href="#" class="flex items-center px-4 py-3 border-b-[1px] border-muted/15 hover:bg-black/20">
                <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-dark">
                    <i class="mdi mdi-logout text-modern-danger"></i>
                </div>
                <span class="ml-3 text-sm text-white">Log out</span>
            </a>
            <p class="p-3 mb-0 text-center text-sm">See all Projects</p>
        </div>
    </li>
    <button id="offcanvas" class="lg:hidden" type="button" data-toggle="offcanvas">
        {{-- <span class="mdi mdi-format-line-spacing"></span> --}}
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
    </button>
    </ul>
    </div>
</nav>
<!-- navbar end  -->

<div id="addAdminModal" class="hidden fixed inset-0 flex items-center justify-center bg-black/50 z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Register New Admin</h3>

        <form method="POST" action="{{ route('admin.create') }}">
            @csrf
            <div class="mb-3">
                <label class="block text-sm font-medium mb-1">Name</label>
                <input type="text" name="name" required
                       class="w-full border rounded-md p-2 focus:ring focus:ring-modern-success/50">
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium mb-1">Email</label>
                <input type="email" name="email" required
                       class="w-full border rounded-md p-2 focus:ring focus:ring-modern-success/50">
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium mb-1">Phone</label>
                <input type="text" name="phone"
                       class="w-full border rounded-md p-2 focus:ring focus:ring-modern-success/50">
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium mb-1">Password</label>
                <input type="password" name="password" required
                       class="w-full border rounded-md p-2 focus:ring focus:ring-modern-success/50">
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium mb-1">Role</label>
                <select name="role" required class="w-full border rounded-md p-2 focus:ring focus:ring-modern-success/50">
                    <option value="admin">Admin</option>
                    <option value="super_admin">Super Admin</option>
                </select>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('addAdminModal').classList.add('hidden')"
                        class="px-4 py-2 bg-gray-300 rounded-md hover:bg-gray-400">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-modern-success text-white rounded-md hover:bg-modern-success/80">
                    Create
                </button>
            </div>
        </form>
    </div>
</div>

// Constants for commonly used class names
const CLASSES = {
    expanded: 'w-sidebar-expanded',
    collapsed: 'w-sidebar-collapsed',
    hidden: 'hidden',
    navbarExpanded: 'left-[15.25rem]',
    navbarCollapsed: 'left-[70px]',
    active: 'active',
    show: 'show'
};

// DOM elements as an object for better organization
const elements = {
    sidebar: document.getElementById('sidebar'),
    offcanvas: document.getElementById('offcanvas'),
    toggler: document.getElementById('sidebarToggler'),
    navbar: document.getElementById('navbar'),
    logo: document.getElementById('logo'),
    logoMini: document.getElementById('logo-mini'),
    navCategories: document.querySelectorAll('.nav-category'),
    menuTitles: document.querySelectorAll('.menu-title'),
    submenus: document.querySelectorAll('[id^="collapse-"]'),
    menuItems: document.querySelectorAll('.menu-item a')
};

class SidebarController {
    constructor() {
        this.isCollapsed = false;
        this.mediaQuery = window.matchMedia('(max-width: 992px)');
        this.init();
    }

    init() {
        // Bind event listeners
        this.handleMediaQueryChange(this.mediaQuery);
        this.mediaQuery.addEventListener('change', (e) => this.handleMediaQueryChange(e));
        elements.toggler.addEventListener('click', () => this.toggleSidebar());
        elements.offcanvas.addEventListener('click', () => this.toggleSidebar());

        // Initialize active states and collapse functionality
        this.initNavigation();
    }

    updateMenuTitles(collapse) {
        const [removeClasses, addClasses] = collapse ? [
            ['opacity-100', 'translate-x-0'],
            ['opacity-0', '-translate-x-6', 'invisible']
        ] : [
            ['opacity-0', '-translate-x-6', 'invisible'],
            ['opacity-100', 'translate-x-0']
        ];

        elements.menuTitles.forEach(title => {
            title.classList.remove(...removeClasses);
            title.classList.add(...addClasses);
        });
    }

    updateSidebarState(collapse) {
        // Update sidebar classes
        elements.sidebar.classList.toggle(CLASSES.collapsed, collapse);
        elements.sidebar.classList.toggle(CLASSES.expanded, !collapse);

        // Update navbar position
        elements.navbar.classList.toggle(CLASSES.navbarCollapsed, collapse);
        elements.navbar.classList.toggle(CLASSES.navbarExpanded, !collapse);

        // Toggle logos
        elements.logo.classList.toggle(CLASSES.hidden, collapse);
        elements.logoMini.classList.toggle(CLASSES.hidden, !collapse);

        // Toggle nav categories and submenus
        elements.navCategories.forEach(category =>
            category.classList.toggle(CLASSES.hidden, collapse));
        elements.submenus.forEach(submenu =>
            submenu.classList.toggle(CLASSES.hidden, collapse));

        // Update menu titles
        this.updateMenuTitles(collapse);
    }

    toggleSidebar() {
        this.isCollapsed = !this.isCollapsed;
        this.updateSidebarState(this.isCollapsed);
    }

    handleMediaQueryChange(e) {
        const shouldCollapse = e.matches;
        this.updateSidebarState(shouldCollapse);
    }

    initNavigation() {
        // Add active class based on current URL
        const current = location.pathname.split("/").slice(-1)[0].replace(/^\/|\/$/g, '');

        elements.menuItems.forEach(element => {
            const href = element.getAttribute('href');
            if (!href) return;

            const isRoot = current === "";
            const isCurrentPage = href.indexOf(current) !== -1 ||
                (isRoot && href.indexOf("index.html") !== -1);

            if (isCurrentPage) {
                this.handleActiveStates(element);
            }
        });

        // Initialize dropdown toggles
        this.initDropdownToggles();
        this.initCollapseToggles();
    }

    handleActiveStates(element) {
        // Add active to submenu-container if it exists
        const submenuContainer = element.closest('.submenu-container');
        if (submenuContainer) {
            submenuContainer.classList.add(CLASSES.active);
        }

        element.classList.add(CLASSES.active);

        // Handle submenu expansion for active items
        const parentMenuItem = element.closest('.menu-item');
        if (parentMenuItem) {
            const parentSubmenuContainer = parentMenuItem.querySelector('.submenu-container');
            if (parentSubmenuContainer) {
                parentSubmenuContainer.classList.add(CLASSES.active);
            }
            const collapseToggle = parentMenuItem.querySelector('[data-collapse-toggle]');
            if (collapseToggle) {
                const collapseId = collapseToggle.getAttribute('data-collapse-toggle');
                const collapseElement = document.getElementById(collapseId);

                if (collapseElement) {
                    collapseElement.style.maxHeight = `${collapseElement.scrollHeight}px`;
                    collapseElement.classList.add(CLASSES.show);
                }
            }
        }
    }

    initDropdownToggles() {
        const dropdownMap = [
            { toggleSelector: '.dropdown-toggle', dropdownId: 'profileDropdown' },
            { toggleSelector: '.dropdown-toggle2', dropdownId: 'projectDropdown' },
            { toggleSelector: '.dropdown-toggle3', dropdownId: 'mailDropdown' },
            { toggleSelector: '.dropdown-toggle4', dropdownId: 'notificationDropdown' },
            { toggleSelector: '.dropdown-toggle5', dropdownId: 'navProfileDropdown' }
        ];

        dropdownMap.forEach(({ toggleSelector, dropdownId }) => {
            const dropdownToggle = document.querySelector(toggleSelector);
            const dropdown = document.getElementById(dropdownId);

            if (dropdownToggle && dropdown) {
                dropdownToggle.addEventListener('click', (event) => {
                    event.preventDefault();
                    dropdown.classList.toggle(CLASSES.show);

                    // Close dropdown when clicking outside
                    const outsideClickListener = (e) => {
                        if (!dropdown.contains(e.target) && !dropdownToggle.contains(e.target)) {
                            dropdown.classList.remove(CLASSES.show);
                            document.removeEventListener('click', outsideClickListener);
                        }
                    };

                    document.addEventListener('click', outsideClickListener);
                });
            }
        });
    }

    initCollapseToggles() {
        const collapseButtons = document.querySelectorAll('[data-collapse-toggle]');

        collapseButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = button.getAttribute('data-collapse-toggle');
                const target = document.getElementById(targetId);

                if (target) {
                    // Close all other submenus
                    const allSubmenus = document.querySelectorAll('[id^="collapse-"]');
                    allSubmenus.forEach(submenu => {
                        if (submenu.id !== targetId) {
                            submenu.style.maxHeight = null;
                            submenu.classList.remove(CLASSES.show);
                            submenu.classList.remove('max-h-[24rem]');
                            submenu.classList.add('max-h-0');
                        }
                    });

                    // Explicitly toggle the current submenu
                    if (!target.style.maxHeight || target.style.maxHeight === '0px') {
                        target.style.maxHeight = `${target.scrollHeight}px`;
                        target.classList.add(CLASSES.show);
                        target.classList.remove('max-h-0');
                        target.classList.add('max-h-[24rem]');
                    } else {
                        target.style.maxHeight = '0px';
                        target.classList.remove(CLASSES.show);
                        target.classList.remove('max-h-[24rem]');
                        target.classList.add('max-h-0');
                    }
                }
            });
        });
    }
}

// Initialize the controller
new SidebarController();
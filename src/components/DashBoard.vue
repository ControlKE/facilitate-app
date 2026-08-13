<template>
  <v-app id="inspire">
    <v-navigation-drawer v-model="drawer">
      <v-list>
        <v-list-item :title="displayUserName" :subtitle="displayUserSubtitle">
          <template #prepend>
            <v-avatar class="profile-initials" size="38">
              {{ userInitials }}
            </v-avatar>
          </template>
          <template #append>
            <v-menu location="bottom end" offset="8">
              <template #activator="{ props }">
                <v-btn v-bind="props" size="small" variant="text" icon="mdi-menu-down"></v-btn>
              </template>
              <v-list density="comfortable" min-width="220">
                <v-list-item :title="displayUserName" :subtitle="displayUserSubtitle">
                  <template #prepend>
                    <v-avatar class="profile-initials" size="30">
                      {{ userInitials }}
                    </v-avatar>
                  </template>
                </v-list-item>
                <v-divider class="my-1"></v-divider>
                <v-list-item
                  prepend-icon="mdi-logout"
                  :title="isLoggingOut ? 'Logging out...' : 'Logout'"
                  :disabled="isLoggingOut"
                  @click="logout"
                ></v-list-item>
              </v-list>
            </v-menu>
          </template>
        </v-list-item>
      </v-list>
      <v-divider></v-divider>
      <v-list :lines="false"  nav>
        <v-list-item color="primary" :disabled="!firstAccessibleRoute" @click="dashboardClick()">
          <template v-slot:prepend>
            <v-icon >mdi-view-dashboard-outline</v-icon>
          </template>
          <v-list-item-title >Dashboard</v-list-item-title>
        </v-list-item>
        <v-list-item color="primary" @click="openWhatsAppWeb">
          <template v-slot:prepend>
            <v-icon>mdi-whatsapp</v-icon>
          </template>
          <v-list-item-title>WhatsApp Web</v-list-item-title>
          <template v-slot:append>
            <v-icon size="14" color="grey">mdi-open-in-new</v-icon>
          </template>
        </v-list-item>
        <!-- Inbox dropdown menu -->
        <v-list-group v-if="filteredInbox.length" prepend-icon="mdi-email" value="Inbox">
          <template v-slot:activator="{ props }">
            <v-list-item v-bind="props" title="Inbox"></v-list-item>
          </template>
          <v-list-item v-for="(item, i) in filteredInbox" :key="i" :value="item" color="primary" @click="inboxActionClick(item.action)">
            <template v-slot:prepend>
              <v-icon :icon="item.icon"></v-icon>
            </template>
            <v-list-item-title>{{ item.text }}</v-list-item-title>
          </v-list-item>
        </v-list-group>

        <!-- Car Allocation dropdown menu -->
        <v-list-group v-if="filteredCar.length" prepend-icon="mdi-car" value="Car allocation">
          <template v-slot:activator="{ props }">
            <v-list-item v-bind="props" title="Car allocation"></v-list-item>
          </template>
          <v-list-item v-for="(item, i) in filteredCar" :key="i" :value="item" color="primary" @click="carActionClick(item.action)">
            <template v-slot:prepend>
              <v-icon :icon="item.icon"></v-icon>
            </template>
            <v-list-item-title>{{ item.text }}</v-list-item-title>
          </v-list-item>
        </v-list-group>

        <v-list-group v-if="filteredMileage.length" prepend-icon="mdi-speedometer" value="Mileage Claims">
          <template v-slot:activator="{ props }">
            <v-list-item v-bind="props" title="Mileage Claims"></v-list-item>
          </template>
          <v-list-item v-for="(item, i) in filteredMileage" :key="i" :value="item" color="primary" @click="mileageActionClick(item.action)">
            <template v-slot:prepend>
              <v-icon :icon="item.icon"></v-icon>
            </template>
            <v-list-item-title>{{ item.text }}</v-list-item-title>
          </v-list-item>
        </v-list-group>

        <v-list-item v-for="(item, i) in filteredItems" :key="i" :value="item" color="primary" @click="navActionClick(item.action)">
          <template v-slot:prepend>
            <v-icon :icon="item.icon"></v-icon>
          </template>
          <v-list-item-title>{{ item.text }}</v-list-item-title>
        </v-list-item>

        <v-list-group v-if="filteredUsers.length" prepend-icon="mdi-account-circle" value="Users">
          <template v-slot:activator="{ props }">
            <v-list-item v-bind="props" title="Users"></v-list-item>
          </template>
          <v-list-item v-for="(item, i) in filteredUsers" :key="i" :value="item" color="primary" @click="userActionClick(item.action)">
            <template v-slot:prepend>
              <v-icon :icon="item.icon"></v-icon>
            </template>
            <v-list-item-title>{{ item.text }}</v-list-item-title>
          </v-list-item>
        </v-list-group>

      </v-list>
    </v-navigation-drawer>
    <v-app-bar elevation="2" color="white" class="dashboard-topbar" height="78">
      <v-app-bar-nav-icon @click="drawer = !drawer"></v-app-bar-nav-icon>

      <div class="topbar-title-wrap">
        <div class="topbar-context">{{ currentPageGroup }}</div>
        <v-toolbar-title class="topbar-title">{{ currentPageTitle }}</v-toolbar-title>
        <div class="topbar-subtitle">{{ currentPageSubtitle }}</div>
      </div>

      <v-spacer></v-spacer>

      <v-autocomplete
        v-model="quickJumpRoute"
        :items="quickJumpItems"
        item-title="label"
        item-value="routeName"
        density="compact"
        variant="solo-filled"
        flat
        clearable
        hide-details
        prepend-inner-icon="mdi-magnify"
        placeholder="Jump to page"
        class="topbar-jump d-none d-lg-block"
        style="width: 420px; min-width: 420px;"
        @update:modelValue="jumpToRoute"
      ></v-autocomplete>
      <v-spacer class="d-none d-lg-flex"></v-spacer>

      <v-chip
        size="small"
        variant="tonal"
        color="primary"
        prepend-icon="mdi-clock-outline"
        class="mx-2 d-none d-md-flex"
      >
        {{ liveNowLabel }}
      </v-chip>

      <v-menu location="bottom end" offset="8">
        <template #activator="{ props }">
          <v-btn v-bind="props" variant="outlined" color="primary" size="small" class="mx-2">
            Quick Actions
          </v-btn>
        </template>
        <v-list density="comfortable" min-width="240">
          <v-list-item
            v-for="action in quickActions"
            :key="action.routeName"
            :prepend-icon="action.icon"
            :title="action.label"
            @click="goToNamedRoute(action.routeName)"
          ></v-list-item>
        </v-list>
      </v-menu>

      <v-btn icon="mdi-refresh" variant="text" color="primary" @click="refreshCurrentPage"></v-btn>
    </v-app-bar>
    <v-main>
      <router-view></router-view>
    </v-main>
  </v-app>
</template>

<script>
  import {
    describeAuthApiError,
    fetchSessionUser,
    getStoredCurrentUser,
    logoutUser,
    persistCurrentUser,
  } from '../services/authApi';
  import {
    ROLE_LABELS,
    ROUTE_PERMISSION_MAP,
    canAccessUserManagement,
    hasPermission,
    firstAllowedRouteName,
    normalizeCurrentUser,
  } from '../utils/accessControl';

  const PAGE_META = {
    analyticsDashboard: {
      group: 'Analytics',
      title: 'Analytics Menu',
      subtitle: 'View overview, acquisition, pages, audience, conversions, realtime, SEO, and alerts.',
    },
    contactInbox: {
      group: 'Inbox',
      title: 'General Enquiries',
      subtitle: 'Review and respond to incoming general enquiries.',
    },
    jobapplications: {
      group: 'Inbox',
      title: 'Job Applications',
      subtitle: 'Review and manage caregiver job application submissions.',
    },
    complaints: {
      group: 'Inbox',
      title: 'Complaints',
      subtitle: 'Monitor and action complaints from carers and clients.',
    },
    carethanks: {
      group: 'Inbox',
      title: 'Carer Thanks',
      subtitle: 'View appreciation messages submitted by clients.',
    },
    messagerouting: {
      group: 'Inbox',
      title: 'Email Routing',
      subtitle: 'Choose where each website inbox category sends email notifications.',
    },
    usermanagement: {
      group: 'Users',
      title: 'User Management',
      subtitle: 'Manage user accounts, roles, and permission access matrix.',
    },
    cardashboard: {
      group: 'Cars',
      title: 'Car Dashboard',
      subtitle: 'Operational snapshot for cars, costs, and availability.',
    },
    carallocation: {
      group: 'Cars',
      title: 'Car Allocation',
      subtitle: 'Assign and return vehicles with automated invoicing.',
    },
    maintenancelog: {
      group: 'Cars',
      title: 'Maintenance Log',
      subtitle: 'Track faults, garage jobs, and off-road vehicles.',
    },
    vehicledirectory: {
      group: 'Cars',
      title: 'Vehicle Directory',
      subtitle: 'Manage fleet details, status, and rate configuration.',
    },
    routeoptimiser: {
      group: 'Care Planning',
      title: 'Route Optimiser',
      subtitle: 'Manage client addresses, create runs, and generate suggested visit order.',
    },
    googledrive: {
      group: 'Files',
      title: 'Google Drive',
      subtitle: 'Browse the company\'s shared client files and folders.',
    },
    websitecontent: {
      group: 'Website',
      title: 'Frontend Content',
      subtitle: 'Manage editable text and image fields for the website frontend.',
    },
    mileageNew: {
      group: 'Mileage Claims',
      title: 'New Mileage Entry',
      subtitle: 'Record odometer readings, lunch-home deductions, and route mileage checks.',
    },
    mileageNewSubmissions: {
      group: 'Mileage Claims',
      title: 'New Mileage Submissions',
      subtitle: 'Driver portal submissions waiting on office verification, grouped by driver and period.',
    },
    mileageReview: {
      group: 'Mileage Claims',
      title: 'Verify Mileage',
      subtitle: 'Compare driver claims against Access Care Planning and assemble the expected total.',
    },
    mileageManagerApproval: {
      group: 'Mileage Claims',
      title: 'Manager Approval',
      subtitle: 'Give final sign-off on verified mileage claims, with totals and payable amount ready.',
    },
    mileageCarerDirectory: {
      group: 'Mileage Claims',
      title: 'Carer Directory',
      subtitle: 'Maintain carer home addresses used when verifying commute mileage.',
    },
    mileageReports: {
      group: 'Mileage Claims',
      title: 'Mileage Reports',
      subtitle: 'Review payable mileage totals per carer and driver.',
    },
    mileageSettings: {
      group: 'Mileage Claims',
      title: 'Mileage Settings',
      subtitle: 'Adjust mileage rate, review threshold, and weekly submission settings.',
    },
  };

  const ACTION_PERMISSION_MAP = {
    'analytics-dashboard': 'dashboard.analytics',
    'website-content': 'website.content',
    'job-applications': 'inbox.general_enquiries',
    complaints: 'inbox.complaints',
    'carer-thanks': 'inbox.care_thanks',
    'general-enquiries': 'inbox.general_enquiries',
    'email-routing': 'inbox.email_routing',
    dash: 'cars.dashboard',
    allocate: 'cars.allocate',
    log: 'cars.maintenance',
    vehicles: 'cars.directory',
    'route-optimiser': 'routes.optimiser',
    'google-drive': 'files.google_drive',
    'mileage-new': 'mileage.claims',
    'mileage-new-submissions': 'mileage.claims',
    'mileage-review': 'mileage.claims',
    'mileage-manager-approval': 'mileage.final_approval',
    'mileage-carer-directory': 'mileage.claims',
    'mileage-reports': 'mileage.claims',
    'mileage-settings': 'mileage.claims',
  };

  export default {
    data: () => ({ 
        drawer: null,
        currentUser: null,
        isLoggingOut: false,
        now: new Date(),
        nowTimer: null,
        quickJumpRoute: null,
        items: [
            { text: 'Analytics', icon: 'mdi-chart-line', action: 'analytics-dashboard' },
            { text: 'Route Optimiser', icon: 'mdi-map-marker-path', action: 'route-optimiser' },
            { text: 'Google Drive', icon: 'mdi-google-drive', action: 'google-drive' },
            { text: 'Website Content', icon: 'mdi-web', action: 'website-content' },
            { text: 'Reviews', icon: 'mdi-comment-text', action: 'comments' },
            { text: 'Users', icon: 'mdi-account-circle', action: 'users' },
            { text: 'Gallery', icon: 'mdi-image', action: 'media' },
            { text: 'SEO', icon: 'mdi-trending-up', action: 'seo' },
            { text: 'Security', icon: 'mdi-security', action: 'security' },
            { text: 'Settings', icon: 'mdi-cog', action: 'settings' },
        ],
        
        car: [
            { text: 'Dashboard', icon: 'mdi-view-dashboard-outline', action: 'dash'},
            { text: 'Allocate', icon: 'mdi-car-arrow-right', action: 'allocate'},
            { text: 'Maintenance log', icon: 'mdi-car-wrench', action: 'log'},
            { text: 'Vehicle Directory', icon: 'mdi-car-info', action: 'vehicles'},
        ],

        mileage: [
            { text: 'New Mileage Submissions', icon: 'mdi-tray-full', action: 'mileage-new-submissions' },
            { text: 'Verify Mileage', icon: 'mdi-clipboard-check-outline', action: 'mileage-review' },
            { text: 'Manager Approval', icon: 'mdi-shield-check-outline', action: 'mileage-manager-approval' },
            { text: 'Carer Directory', icon: 'mdi-card-account-details-outline', action: 'mileage-carer-directory' },
            { text: 'Reports', icon: 'mdi-file-chart-outline', action: 'mileage-reports' },
            { text: 'Settings', icon: 'mdi-cog-outline', action: 'mileage-settings' },
        ],

        inbox: [
            { text: 'Job Applications', icon: 'mdi-briefcase-account-outline', action: 'job-applications' },
            { text: 'Complaints', icon: 'mdi-alert', action: 'complaints' },
            { text: 'Carer Thanks', icon: 'mdi-thumb-up', action: 'carer-thanks' },
            { text: 'General Enquiries', icon: 'mdi-email-outline', action: 'general-enquiries' },
            { text: 'Email Routing', icon: 'mdi-email-outline', action: 'email-routing' },
        ],

        users: [
          { text: 'User Management', icon: 'mdi-account-cog-outline', action: 'user-management' },
        ],
        // home: [
        //     { text: 'Hero Section', icon: 'mdi-account-multiple-outline', action: 'hero' },
        //     { text: 'Trending Section', icon: 'mdi-cog-outline', action: 'trending' },
        // ],
        // about: [
        //     { text: 'Hero Section', icon: 'mdi-plus-outline', action: 'hero'},
        //     { text: 'Body', icon: 'mdi-file-outline', action: 'body'},
        //     { text: 'Update', icon: 'mdi-update', action: 'hero'},
        //     { text: 'Delete', icon: 'mdi-delete', action: 'hero'},
        // ],
    }),
    computed: {
        pageMeta() {
            const routeName = String(this.$route?.name || '');
            return PAGE_META[routeName] || null;
        },
        currentPageGroup() {
            return this.pageMeta?.group || 'Dashboard';
        },
        currentPageTitle() {
            if (this.pageMeta?.title) {
                return this.pageMeta.title;
            }

            const routeName = String(this.$route?.name || '').trim();
            if (!routeName) {
                return 'Facilitate Care Services';
            }

            return routeName
                .replace(/([a-z])([A-Z])/g, '$1 $2')
                .replace(/[_-]/g, ' ')
                .replace(/\b\w/g, (letter) => letter.toUpperCase());
        },
        currentPageSubtitle() {
            return this.pageMeta?.subtitle || 'Use the left navigation to manage dashboard sections.';
        },
        liveNowLabel() {
            return this.now.toLocaleString('en-GB', {
                day: '2-digit',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit',
            });
        },
        firstAccessibleRoute() {
            return firstAllowedRouteName(this.currentUser);
        },
        filteredItems() {
            return this.items.filter((item) => this.canAccessAction(item.action));
        },
        filteredCar() {
            return this.car.filter((item) => this.canAccessAction(item.action));
        },
        filteredMileage() {
            return this.mileage.filter((item) => this.canAccessAction(item.action));
        },
        filteredInbox() {
            return this.inbox.filter((item) => this.canAccessAction(item.action));
        },
        filteredUsers() {
            return this.users.filter((item) => this.canAccessAction(item.action));
        },
        quickJumpItems() {
            return Object.entries(PAGE_META)
                .filter(([routeName]) => this.canAccessRoute(routeName))
                .map(([routeName, meta]) => ({
                    routeName,
                    label: `${meta.group} - ${meta.title}`,
                }));
        },
        quickActions() {
            return [
                { routeName: 'analyticsDashboard', icon: 'mdi-chart-line', label: 'Analytics Menu' },
                { routeName: 'cardashboard', icon: 'mdi-view-dashboard-outline', label: 'Car Dashboard' },
                { routeName: 'carallocation', icon: 'mdi-car-arrow-right', label: 'Allocate Vehicle' },
                { routeName: 'maintenancelog', icon: 'mdi-car-wrench', label: 'Open Maintenance Log' },
                { routeName: 'vehicledirectory', icon: 'mdi-car-info', label: 'Vehicle Directory' },
                { routeName: 'routeoptimiser', icon: 'mdi-map-marker-path', label: 'Route Optimiser' },
                { routeName: 'googledrive', icon: 'mdi-google-drive', label: 'Google Drive' },
                { routeName: 'mileageReview', icon: 'mdi-clipboard-check-outline', label: 'Admin Mileage Review' },
                { routeName: 'mileageSettings', icon: 'mdi-cog-outline', label: 'Mileage Settings' },
                { routeName: 'websitecontent', icon: 'mdi-web', label: 'Website Content' },
                { routeName: 'complaints', icon: 'mdi-alert', label: 'View Complaints' },
                { routeName: 'messagerouting', icon: 'mdi-email-outline', label: 'Inbox Email Routing' },
                { routeName: 'usermanagement', icon: 'mdi-account-cog-outline', label: 'User Management' },
            ].filter((item) => this.canAccessRoute(item.routeName));
        },
        displayUserName() {
            return this.currentUser?.name || this.currentUser?.username || 'Admin User';
        },
        displayUserEmail() {
            return this.currentUser?.email || 'No email';
        },
        displayUserSubtitle() {
            const role = this.currentUser?.role ? (ROLE_LABELS[this.currentUser.role] || this.currentUser.role) : 'Guest';
            return `${this.displayUserEmail} | ${role}`;
        },
        userInitials() {
            const source = `${this.displayUserName}`.trim();
            if (!source) {
                return 'AU';
            }

            const parts = source.split(/\s+/).filter(Boolean);
            const first = parts[0]?.charAt(0) || '';
            const second = parts[1]?.charAt(0) || '';
            const initials = `${first}${second}`.toUpperCase();
            return initials || first.toUpperCase() || 'AU';
        },
    },
    created() {
        this.initializeCurrentUser();
        this.nowTimer = setInterval(() => {
            this.now = new Date();
        }, 30000);
    },
    watch: {
        '$route.name': {
            immediate: true,
            handler() {
                this.enforceRouteAccess();
            },
        },
    },
    beforeUnmount() {
        if (this.nowTimer) {
            clearInterval(this.nowTimer);
            this.nowTimer = null;
        }
    },
    methods: {
        redirectToPublicHome() {
            if (typeof window !== 'undefined') {
                window.location.assign('/');
                return;
            }
            this.$router.replace({ name: 'home' });
        },
        async initializeCurrentUser() {
            const storedUser = normalizeCurrentUser(getStoredCurrentUser());
            if (storedUser) {
                this.currentUser = storedUser;
            }

            const shouldFetchSession = !storedUser || !storedUser.permissions;
            if (!shouldFetchSession) {
                this.enforceRouteAccess();
                return;
            }

            try {
                const result = await fetchSessionUser();
                this.currentUser = normalizeCurrentUser(result.user);
                if (this.currentUser) {
                    persistCurrentUser(this.currentUser);
                }
            } catch (error) {
                if (this.isDashboardRoute(this.$route?.name) || String(this.$route?.name || '') === 'dashboard') {
                    this.redirectToPublicHome();
                }
            } finally {
                this.enforceRouteAccess();
            }
        },
        isDashboardRoute(routeName) {
            const normalized = String(routeName || '');
            return Object.prototype.hasOwnProperty.call(PAGE_META, normalized);
        },
        permissionForAction(action) {
            return ACTION_PERMISSION_MAP[String(action || '')] || '';
        },
        hasAccessPermission(permissionKey) {
            return hasPermission(this.currentUser, permissionKey);
        },
        canAccessAction(action) {
            if (String(action || '') === 'user-management') {
                return canAccessUserManagement(this.currentUser);
            }
            const permissionKey = this.permissionForAction(action);
            if (!permissionKey) {
                return false;
            }
            return this.hasAccessPermission(permissionKey);
        },
        canAccessRoute(routeName) {
            const normalizedRoute = String(routeName || '');
            if (normalizedRoute === 'usermanagement') {
                return canAccessUserManagement(this.currentUser);
            }

            const permissionKey = ROUTE_PERMISSION_MAP[normalizedRoute];
            if (!permissionKey) {
                return true;
            }
            return this.hasAccessPermission(permissionKey);
        },
        enforceRouteAccess() {
            const routeName = String(this.$route?.name || '');
            if (!routeName) {
                return;
            }

            if (routeName === 'dashboard') {
                if (this.currentUser && this.firstAccessibleRoute) {
                    this.$router.replace({ name: this.firstAccessibleRoute });
                }
                return;
            }

            if (!this.isDashboardRoute(routeName) || !this.currentUser) {
                return;
            }

            if (!this.canAccessRoute(routeName)) {
                if (this.firstAccessibleRoute && this.firstAccessibleRoute !== routeName) {
                    this.$router.replace({ name: this.firstAccessibleRoute });
                    return;
                }
                this.redirectToPublicHome();
            }
        },
        async logout() {
            if (this.isLoggingOut) {
                return;
            }

            this.isLoggingOut = true;
            try {
                await logoutUser();
            } catch (error) {
                console.warn(describeAuthApiError(error, 'Logout request failed.'));
            } finally {
                this.currentUser = null;
                this.isLoggingOut = false;
                this.redirectToPublicHome();
            }
        },
        jumpToRoute(routeName) {
            const target = String(routeName || '').trim();
            if (!target) {
                return;
            }

            this.goToNamedRoute(target);
            this.quickJumpRoute = null;
        },
        goToNamedRoute(routeName) {
            const target = String(routeName || '').trim();
            if (!target || target === String(this.$route?.name || '')) {
                return;
            }
            if (!this.canAccessRoute(target)) {
                return;
            }
            this.$router.replace({ name: target });
        },
        refreshCurrentPage() {
            this.$router.go(0);
        },
        navActionClick(action) {
            if (action === 'analytics-dashboard') {
                this.goToNamedRoute('analyticsDashboard');
            } else if (action === 'route-optimiser') {
                this.goToNamedRoute('routeoptimiser');
            } else if (action === 'google-drive') {
                this.goToNamedRoute('googledrive');
            } else if (action === 'website-content'){
                this.goToNamedRoute('websitecontent');
            }
        },
        inboxActionClick(action) {
          if (action === 'job-applications') {
                this.goToNamedRoute('jobapplications');
            } else if (action === 'complaints') {
                this.goToNamedRoute('complaints');
            } else if (action === 'carer-thanks') {
                this.goToNamedRoute('carethanks');
            } else if (action === 'general-enquiries') {
                this.goToNamedRoute('contactInbox');
            } else if (action === 'email-routing') {
                this.goToNamedRoute('messagerouting');
            }
            
        },
        carActionClick(action) {
          if (action === 'allocate') {
                this.goToNamedRoute('carallocation');
            } else if (action === 'log') {
                this.goToNamedRoute('maintenancelog');
            } else if (action === 'dash') {
                this.goToNamedRoute('cardashboard');
            } else if (action === 'vehicles') {
                this.goToNamedRoute('vehicledirectory');
            }
        },
        mileageActionClick(action) {
          const routes = {
            'mileage-new': 'mileageNew',
            'mileage-new-submissions': 'mileageNewSubmissions',
            'mileage-review': 'mileageReview',
            'mileage-manager-approval': 'mileageManagerApproval',
            'mileage-carer-directory': 'mileageCarerDirectory',
            'mileage-reports': 'mileageReports',
            'mileage-settings': 'mileageSettings',
          };
          this.goToNamedRoute(routes[action]);
        },
        dashboardClick() {
            if (this.firstAccessibleRoute) {
                this.goToNamedRoute(this.firstAccessibleRoute);
            }
        },
        userActionClick(action) {
            if (action === 'user-management') {
                this.goToNamedRoute('usermanagement');
            }
        },
        openWhatsAppWeb() {
            const width = 420;
            const height = 650;
            const left = Math.max(0, Math.round((window.screen.width - width) / 2));
            const top = Math.max(0, Math.round((window.screen.height - height) / 2));
            const features = [
                `width=${width}`,
                `height=${height}`,
                `left=${left}`,
                `top=${top}`,
                'resizable=yes',
                'scrollbars=yes',
                'noopener',
            ].join(',');

            const popup = window.open('https://web.whatsapp.com/', 'facilitate_whatsapp_web', features);
            if (popup) {
                popup.focus();
            } else {
                // Popup blocked by the browser: fall back to a normal tab.
                window.open('https://web.whatsapp.com/', '_blank', 'noopener,noreferrer');
            }
        },
    }
  }
  </script>
<style>
.v-list-group__items .v-list-item {
    padding-inline-start: 36px !important;
}

.profile-initials {
    background: linear-gradient(135deg, #ab207d 0%, #c44da0 100%);
    color: #fff;
    font-weight: 700;
    letter-spacing: 0.01em;
}

.dashboard-topbar {
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
}

.topbar-title-wrap {
    display: flex;
    flex-direction: column;
    min-width: 220px;
}

.topbar-context {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: rgba(0, 0, 0, 0.5);
    line-height: 1.1;
}

.topbar-title {
    font-size: 1.05rem !important;
    line-height: 1.2;
    font-weight: 700;
}

.topbar-subtitle {
    font-size: 0.78rem;
    color: rgba(0, 0, 0, 0.58);
    line-height: 1.1;
}

.topbar-jump {
    width: 420px;
    min-width: 420px;
    max-width: 420px;
    flex: 0 0 420px;
}

@media (max-width: 959px) {
    .topbar-subtitle {
        display: none;
    }
}
</style>

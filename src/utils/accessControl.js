export const AUTH_STORAGE_KEY = 'facilitateCurrentUser';

export const ROLE_KEYS = ['director', 'manager', 'care_coordinator', 'carer'];

export const ROLE_LABELS = {
  director: 'Director',
  manager: 'Manager',
  care_coordinator: 'Care Coordinator',
  carer: 'Carer',
};

export const USER_MANAGEMENT_PERMISSIONS = ['users.manage_accounts', 'users.manage_permissions'];

export const PERMISSION_CATALOG = [
  {
    key: 'dashboard.analytics',
    group: 'Overview',
    label: 'Analytics Dashboard',
    description: 'View analytics dashboard widgets.',
  },
  {
    key: 'inbox.general_enquiries',
    group: 'Inbox',
    label: 'General Enquiries',
    description: 'View and manage general enquiry inbox.',
  },
  {
    key: 'inbox.complaints',
    group: 'Inbox',
    label: 'Complaints',
    description: 'View and manage complaints inbox.',
  },
  {
    key: 'inbox.care_thanks',
    group: 'Inbox',
    label: 'Carer Thanks',
    description: 'View and manage carer thanks messages.',
  },
  {
    key: 'inbox.email_routing',
    group: 'Inbox',
    label: 'Inbox Email Routing',
    description: 'Choose which email addresses receive website inbox notifications.',
  },
  {
    key: 'cars.dashboard',
    group: 'Cars',
    label: 'Car Dashboard',
    description: 'View car operations dashboard.',
  },
  {
    key: 'cars.allocate',
    group: 'Cars',
    label: 'Car Allocation',
    description: 'Assign and return cars.',
  },
  {
    key: 'cars.maintenance',
    group: 'Cars',
    label: 'Maintenance Log',
    description: 'Manage maintenance records and off-road vehicles.',
  },
  {
    key: 'cars.directory',
    group: 'Cars',
    label: 'Vehicle Directory',
    description: 'View and update company vehicle directory.',
  },
  {
    key: 'routes.optimiser',
    group: 'Care Planning',
    label: 'Route Optimiser',
    description: 'Manage client route runs and optimise visit order.',
  },
  {
    key: 'files.google_drive',
    group: 'Files',
    label: 'Google Drive',
    description: 'Browse shared client files and folders on Google Drive.',
  },
  {
    key: 'mileage.final_approval',
    group: 'Mileage',
    label: 'Final Mileage Approval',
    description: 'Give final sign-off on mileage claims after office verification against Access Care Planning.',
  },
  {
    key: 'website.content',
    group: 'Website',
    label: 'Website Content',
    description: 'Edit website content registry and media values.',
  },
  {
    key: 'mileage.claims',
    group: 'Mileage',
    label: 'Mileage Claims',
    description: 'Create, submit, review, and report weekly mileage claims.',
  },
  {
    key: 'users.manage_accounts',
    group: 'Users',
    label: 'Manage Accounts',
    description: 'Create users and view account list.',
  },
  {
    key: 'users.manage_permissions',
    group: 'Users',
    label: 'Manage Role Access',
    description: 'Edit role permissions matrix.',
  },
];

const PERMISSION_KEYS = PERMISSION_CATALOG.map((item) => item.key);

const withAllPermissions = (value) =>
  PERMISSION_KEYS.reduce((acc, key) => {
    acc[key] = Boolean(value);
    return acc;
  }, {});

const DEFAULT_ROLE_PERMISSION_MATRIX = {
  director: withAllPermissions(true),
  manager: {
    ...withAllPermissions(true),
    'users.manage_permissions': false,
  },
  care_coordinator: {
    ...withAllPermissions(false),
    'dashboard.analytics': true,
    'inbox.general_enquiries': true,
    'inbox.complaints': true,
    'inbox.care_thanks': true,
    'cars.dashboard': true,
    'cars.allocate': true,
    'cars.maintenance': true,
    'cars.directory': true,
    'routes.optimiser': true,
    'files.google_drive': true,
    'mileage.claims': true,
  },
  carer: {
    ...withAllPermissions(false),
    'cars.dashboard': true,
    'mileage.claims': true,
  },
};

export const ROUTE_PERMISSION_MAP = {
  analyticsDashboard: 'dashboard.analytics',
  contactInbox: 'inbox.general_enquiries',
  jobapplications: 'inbox.general_enquiries',
  complaints: 'inbox.complaints',
  carethanks: 'inbox.care_thanks',
  messagerouting: 'inbox.email_routing',
  cardashboard: 'cars.dashboard',
  carallocation: 'cars.allocate',
  maintenancelog: 'cars.maintenance',
  vehicledirectory: 'cars.directory',
  routeoptimiser: 'routes.optimiser',
  googledrive: 'files.google_drive',
  mileageManagerApproval: 'mileage.final_approval',
  mileageCarerDirectory: 'mileage.claims',
  mileageRunDirectory: 'mileage.claims',
  websitecontent: 'website.content',
  mileageDashboard: 'mileage.claims',
  mileageNew: 'mileage.claims',
  mileageMine: 'mileage.claims',
  mileageWeekly: 'mileage.claims',
  mileageNewSubmissions: 'mileage.claims',
  mileageReview: 'mileage.claims',
  mileageReports: 'mileage.claims',
  mileageBreakdown: 'mileage.claims',
  mileageSettings: 'mileage.claims',
  usermanagement: 'users.manage_accounts',
};

export const ROUTE_PRIORITY = [
  'analyticsDashboard',
  'cardashboard',
  'carallocation',
  'maintenancelog',
  'vehicledirectory',
  'routeoptimiser',
  'googledrive',
  'mileageDashboard',
  'mileageManagerApproval',
  'mileageCarerDirectory',
  'mileageRunDirectory',
  'mileageNew',
  'mileageMine',
  'mileageWeekly',
  'mileageNewSubmissions',
  'mileageReview',
  'mileageReports',
  'mileageBreakdown',
  'mileageSettings',
  'contactInbox',
  'jobapplications',
  'complaints',
  'carethanks',
  'messagerouting',
  'usermanagement',
  'websitecontent',
];

export const normalizeRoleKey = (value) => {
  const raw = String(value || '')
    .trim()
    .toLowerCase()
    .replace(/[\s-]+/g, '_');

  if (ROLE_KEYS.includes(raw)) return raw;
  if (raw === 'carecoordinator' || raw === 'care_coordinator') return 'care_coordinator';
  if (raw === 'director') return 'director';
  if (raw === 'manager') return 'manager';
  if (raw === 'carer') return 'carer';
  return 'care_coordinator';
};

export const getDefaultPermissionsForRole = (roleKey) => {
  const normalizedRole = normalizeRoleKey(roleKey);
  const defaults = DEFAULT_ROLE_PERMISSION_MATRIX[normalizedRole] || DEFAULT_ROLE_PERMISSION_MATRIX.care_coordinator;
  return { ...defaults };
};

export const normalizePermissions = (permissionsInput, roleKey) => {
  const base = getDefaultPermissionsForRole(roleKey);

  if (Array.isArray(permissionsInput)) {
    const list = permissionsInput.map((item) => String(item || '').trim());
    PERMISSION_KEYS.forEach((key) => {
      base[key] = list.includes(key);
    });
    return base;
  }

  if (!permissionsInput || typeof permissionsInput !== 'object') {
    return base;
  }

  PERMISSION_KEYS.forEach((key) => {
    if (Object.prototype.hasOwnProperty.call(permissionsInput, key)) {
      base[key] = Boolean(permissionsInput[key]);
    }
  });

  return base;
};

export const normalizeCurrentUser = (user) => {
  if (!user || typeof user !== 'object') {
    return null;
  }

  const role = normalizeRoleKey(user.role);
  const permissions = normalizePermissions(user.permissions, role);
  const name = String(user.name || user.fullName || user.username || user.email || 'Dashboard User').trim();
  const email = String(user.email || '').trim();
  const username = String(user.username || '').trim();
  const id = Number(user.id || 0);

  return {
    id: Number.isFinite(id) ? id : 0,
    name,
    email,
    username,
    role,
    roleLabel: ROLE_LABELS[role] || 'Care Coordinator',
    permissions,
    loggedInAt: String(user.loggedInAt || new Date().toISOString()),
  };
};

export const hasPermission = (user, permissionKey) => {
  if (!permissionKey) {
    return true;
  }

  const normalized = normalizeCurrentUser(user);
  if (!normalized) {
    return false;
  }

  if (normalized.role === 'director') {
    return true;
  }

  return Boolean(normalized.permissions?.[permissionKey]);
};

export const hasAnyPermission = (user, permissionKeys) => {
  const keys = Array.isArray(permissionKeys) ? permissionKeys : [];
  if (!keys.length) {
    return true;
  }

  return keys.some((permissionKey) => hasPermission(user, permissionKey));
};

export const canAccessUserManagement = (user) =>
  hasAnyPermission(user, USER_MANAGEMENT_PERMISSIONS);

export const firstAllowedRouteName = (user) => {
  for (const routeName of ROUTE_PRIORITY) {
    if (routeName === 'usermanagement' && canAccessUserManagement(user)) {
      return routeName;
    }
    const permissionKey = ROUTE_PERMISSION_MAP[routeName];
    if (!permissionKey || hasPermission(user, permissionKey)) {
      return routeName;
    }
  }
  return null;
};

export const roleCanEditTargetRole = (actorRoleKey, targetRoleKey) => {
  const actorRole = normalizeRoleKey(actorRoleKey);
  const targetRole = normalizeRoleKey(targetRoleKey);

  if (actorRole === 'director') return true;
  if (actorRole === 'manager') return targetRole === 'care_coordinator' || targetRole === 'carer';
  return false;
};

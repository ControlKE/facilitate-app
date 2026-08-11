const DEFAULT_LOCAL_PHP_API_BASE = '/phpapi';
const DEFAULT_LIVE_PHP_API_BASE = 'https://facilitatecareservices.co.uk/php';
const LOCALHOST_PHP_API_MODE_KEY = 'facilitate.php_api_mode';

const trimString = (value, fallback = '') => {
  const normalized = String(value || '').trim();
  return normalized || fallback;
};

const normalizeMode = (value, fallback = 'auto') => {
  const normalized = String(value || '').trim().toLowerCase();
  if (normalized === 'live' || normalized === 'local') {
    return normalized;
  }
  return fallback;
};

const isBrowser = typeof window !== 'undefined';

export const isLocalHost =
  isBrowser &&
  (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1');

export const LOCAL_PHP_API_BASE = trimString(
  import.meta.env.VITE_LOCAL_PHP_API_BASE,
  DEFAULT_LOCAL_PHP_API_BASE,
);

export const LIVE_PHP_API_BASE = trimString(
  import.meta.env.VITE_LIVE_PHP_API_BASE,
  DEFAULT_LIVE_PHP_API_BASE,
);

const readQueryMode = () => {
  if (!isBrowser) {
    return 'auto';
  }

  try {
    const queryMode = new URLSearchParams(window.location.search).get('phpApi');
    return normalizeMode(queryMode, 'auto');
  } catch {
    return 'auto';
  }
};

const readStoredMode = () => {
  if (!isBrowser) {
    return 'auto';
  }

  try {
    return normalizeMode(window.localStorage.getItem(LOCALHOST_PHP_API_MODE_KEY), 'auto');
  } catch {
    return 'auto';
  }
};

const persistMode = (mode) => {
  if (!isBrowser) {
    return;
  }

  try {
    window.localStorage.setItem(LOCALHOST_PHP_API_MODE_KEY, mode);
  } catch {
    // Ignore storage failures so API selection still works in restricted browsers.
  }
};

const defaultLocalMode = normalizeMode(import.meta.env.VITE_LOCALHOST_PHP_API_MODE, 'local');

const resolvePhpApiMode = () => {
  if (!isLocalHost) {
    return 'live';
  }

  const queryMode = readQueryMode();
  if (queryMode !== 'auto') {
    persistMode(queryMode);
    return queryMode;
  }

  const storedMode = readStoredMode();
  if (storedMode !== 'auto') {
    return storedMode;
  }

  return defaultLocalMode;
};

export const phpApiMode = resolvePhpApiMode();
export const isUsingLivePhpApi = phpApiMode === 'live';
export const PHP_API_BASE = isUsingLivePhpApi ? LIVE_PHP_API_BASE : LOCAL_PHP_API_BASE;

const buildBaseUrl = (base, script, action, queryParams = {}) => {
  const params = new URLSearchParams({ action });
  Object.entries(queryParams).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      params.set(key, String(value));
    }
  });
  return `${base}/${script}.php?${params.toString()}`;
};

export const buildPhpApiUrl = (script, action, queryParams = {}) =>
  buildBaseUrl(PHP_API_BASE, script, action, queryParams);

export const buildLivePhpApiUrl = (script, action, queryParams = {}) =>
  buildBaseUrl(LIVE_PHP_API_BASE, script, action, queryParams);

export const buildLocalPhpApiUrl = (script, action, queryParams = {}) =>
  buildBaseUrl(LOCAL_PHP_API_BASE, script, action, queryParams);

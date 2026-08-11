import axios from 'axios';

const isLocalHost =
  typeof window !== 'undefined' &&
  (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1');

const LOCAL_API_BASE = '/phpapi';
const LIVE_API_BASE = 'https://facilitatecareservices.co.uk/php';
const API_BASE = isLocalHost ? LOCAL_API_BASE : LIVE_API_BASE;

const http = axios.create({
  withCredentials: true,
});

const endpoint = (action) =>
  `${API_BASE}/routeOptimiser.php?action=${encodeURIComponent(String(action || '').trim())}`;

const serverMessage = (payload) => {
  if (!payload || typeof payload !== 'object') {
    return '';
  }

  const message = String(payload.message || '').trim();
  const detail = String(payload.detail || payload.error || '').trim();
  if (message && detail) {
    return `${message} ${detail}`.trim();
  }
  return message || detail;
};

const unwrapResponse = (data, fallbackMessage) => {
  if (!data || typeof data !== 'object' || !data.success) {
    throw new Error(serverMessage(data) || fallbackMessage);
  }
  return data;
};

export const describeRouteOptimiserError = (error, fallback = 'Request failed.') => {
  if (axios.isAxiosError(error)) {
    const detail = serverMessage(error.response?.data);
    if (detail) return detail;
    const status = Number(error.response?.status || 0);
    if (status > 0) return `${fallback} (HTTP ${status})`;
    if (error.code === 'ERR_NETWORK') {
      return 'Unable to reach route optimiser service. Check PHP/Apache connection.';
    }
  }

  if (error instanceof Error && error.message.trim()) {
    return error.message.trim();
  }

  return fallback;
};

export const fetchRouteOptimiserBootstrap = async () => {
  const response = await http.get(endpoint('getBootstrap'));
  return unwrapResponse(response?.data, 'Failed to load route optimiser.');
};

export const fetchRouteRun = async (id) => {
  const response = await http.get(endpoint('getRun'), {
    params: { id: Number(id || 0) },
  });
  return unwrapResponse(response?.data, 'Failed to load run details.');
};

export const saveRouteClient = async (payload) => {
  const response = await http.post(endpoint('saveClient'), payload || {});
  return unwrapResponse(response?.data, 'Failed to save client.');
};

export const lookupRouteClientAddress = async (payload) => {
  const response = await http.post(endpoint('lookupAddress'), payload || {});
  return unwrapResponse(response?.data, 'Failed to look up address.');
};

export const deleteRouteClient = async (id) => {
  const response = await http.post(endpoint('deleteClient'), {
    id: Number(id || 0),
  });
  return unwrapResponse(response?.data, 'Failed to delete client.');
};

export const generateOptimisedRoute = async (payload) => {
  const response = await http.post(endpoint('generateRoute'), payload || {});
  return unwrapResponse(response?.data, 'Failed to generate route.');
};

export const saveRouteRun = async (payload) => {
  const response = await http.post(endpoint('saveRun'), payload || {});
  return unwrapResponse(response?.data, 'Failed to save run.');
};

export const deleteRouteRun = async (id) => {
  const response = await http.post(endpoint('deleteRun'), {
    id: Number(id || 0),
  });
  return unwrapResponse(response?.data, 'Failed to delete run.');
};

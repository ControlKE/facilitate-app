import axios from 'axios';

const isLocalHost =
  typeof window !== 'undefined' &&
  (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1');

const LOCAL_API_BASE = '/phpapi';
const LIVE_API_BASE = 'https://facilitatecareservices.co.uk/php';
const API_BASE = isLocalHost ? LOCAL_API_BASE : LIVE_API_BASE;

const endpoint = (action = 'overview', days) => {
  const query = new URLSearchParams();
  query.set('action', String(action || 'overview').trim());
  if (Number.isFinite(Number(days)) && Number(days) > 0) {
    query.set('days', String(Math.round(Number(days))));
  }
  return `${API_BASE}/analyticsDashboard.php?${query.toString()}`;
};

const http = axios.create({
  withCredentials: true,
});

const serverMessage = (payload) => {
  if (!payload || typeof payload !== 'object') {
    return '';
  }
  const message = String(payload.message || payload.error || '').trim();
  const detail = String(payload.detail || '').trim();
  if (message && detail) {
    return `${message} ${detail}`.trim();
  }
  return message || detail;
};

export const describeAnalyticsError = (error, fallback = 'Failed to load analytics dashboard.') => {
  if (axios.isAxiosError(error)) {
    const detail = serverMessage(error.response?.data);
    if (detail) return detail;
    const status = Number(error.response?.status || 0);
    if (status > 0) return `${fallback} (HTTP ${status})`;
    if (error.code === 'ERR_NETWORK') {
      return 'Unable to reach analytics service. Check PHP/Apache connection.';
    }
  }

  if (error instanceof Error && error.message.trim()) {
    return error.message.trim();
  }
  return fallback;
};

export const fetchAnalyticsSection = async (action = 'overview', days = 30) => {
  try {
    const response = await http.get(endpoint(action, days));
    const data = response?.data || {};
    if (!data.success) {
      throw new Error(serverMessage(data) || 'Failed to load analytics section.');
    }
    return data;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      const payload = error.response?.data;
      if (payload && typeof payload === 'object') {
        // Surface backend diagnostics in browser console for faster troubleshooting.
        console.error('Analytics API error payload:', payload);
      }
    }
    throw error;
  }
};

export const fetchAnalyticsOverview = async (days = 30) =>
  fetchAnalyticsSection('overview', days);

import axios from 'axios';
import {
  AUTH_STORAGE_KEY,
  normalizeCurrentUser,
} from '../utils/accessControl';
import { buildPhpApiUrl } from '../utils/phpApi';

const endpoint = (action) =>
  buildPhpApiUrl('login', String(action || '').trim());

const authHttp = axios.create({
  withCredentials: true,
});

const serverMessage = (payload) => {
  if (!payload || typeof payload !== 'object') {
    return '';
  }
  return String(payload.message || payload.error || '').trim();
};

export const describeAuthApiError = (error, fallback = 'Request failed.') => {
  if (axios.isAxiosError(error)) {
    const detail = serverMessage(error.response?.data);
    if (detail) return detail;
    const status = Number(error.response?.status || 0);
    if (status > 0) return `${fallback} (HTTP ${status})`;
    if (error.code === 'ERR_NETWORK') {
      return 'Unable to reach login service. Check PHP/Apache connection.';
    }
  }
  if (error instanceof Error && error.message.trim()) {
    return error.message.trim();
  }
  return fallback;
};

export const persistCurrentUser = (user) => {
  const normalized = normalizeCurrentUser(user);
  if (!normalized) {
    localStorage.removeItem(AUTH_STORAGE_KEY);
    return null;
  }
  localStorage.setItem(AUTH_STORAGE_KEY, JSON.stringify(normalized));
  return normalized;
};

export const getStoredCurrentUser = () => {
  try {
    const raw = localStorage.getItem(AUTH_STORAGE_KEY);
    if (!raw) return null;
    const parsed = JSON.parse(raw);
    return normalizeCurrentUser(parsed);
  } catch {
    return null;
  }
};

export const clearStoredCurrentUser = () => {
  localStorage.removeItem(AUTH_STORAGE_KEY);
  sessionStorage.removeItem(AUTH_STORAGE_KEY);
};

export const loginUser = async ({ identifier, password }) => {
  const response = await authHttp.post(endpoint('login'), {
    username: identifier,
    email: identifier,
    password,
  });
  const payload = response?.data || {};
  if (!payload.success || !payload.user) {
    throw new Error(serverMessage(payload) || 'Login failed.');
  }

  const user = persistCurrentUser(payload.user);
  if (!user) {
    throw new Error('Login response did not include a valid user profile.');
  }
  return {
    user,
    message: serverMessage(payload) || 'Login successful.',
  };
};

export const fetchSessionUser = async () => {
  const response = await authHttp.get(endpoint('session'));
  const payload = response?.data || {};
  if (!payload.success || !payload.user) {
    throw new Error(serverMessage(payload) || 'Session not available.');
  }

  const user = persistCurrentUser(payload.user);
  if (!user) {
    throw new Error('Invalid session user payload.');
  }

  return {
    user,
    message: serverMessage(payload) || 'Session loaded.',
  };
};

export const logoutUser = async () => {
  try {
    await authHttp.post(endpoint('logout'), {});
  } finally {
    clearStoredCurrentUser();
  }
};

export const requestPasswordReset = async (identifier) => {
  const response = await authHttp.post(endpoint('requestPasswordReset'), {
    identifier: String(identifier || '').trim(),
  });
  const data = response?.data || {};
  if (!data.success) {
    throw new Error(serverMessage(data) || 'Failed to start password reset.');
  }
  return data;
};

export const resetPasswordWithToken = async ({ token, newPassword }) => {
  const response = await authHttp.post(endpoint('resetPassword'), {
    token: String(token || '').trim(),
    newPassword: String(newPassword || ''),
  });
  const data = response?.data || {};
  if (!data.success) {
    throw new Error(serverMessage(data) || 'Failed to reset password.');
  }
  return data;
};

export const changeMyPassword = async ({ currentPassword, newPassword }) => {
  const response = await authHttp.post(endpoint('changePassword'), {
    currentPassword: String(currentPassword || ''),
    newPassword: String(newPassword || ''),
  });
  const data = response?.data || {};
  if (!data.success) {
    throw new Error(serverMessage(data) || 'Failed to change password.');
  }
  return data;
};

export const setUserPassword = async ({ userId, newPassword }) => {
  const response = await authHttp.post(endpoint('setUserPassword'), {
    userId: Number(userId || 0),
    newPassword: String(newPassword || ''),
  });
  const data = response?.data || {};
  if (!data.success) {
    throw new Error(serverMessage(data) || 'Failed to update user password.');
  }
  return data;
};

export const createUserAccount = async (payload) => {
  const response = await authHttp.post(endpoint('createUser'), payload || {});
  const data = response?.data || {};
  if (!data.success) {
    throw new Error(serverMessage(data) || 'Failed to create account.');
  }
  return data;
};

export const updateUserAccount = async (payload) => {
  const response = await authHttp.post(endpoint('updateUser'), payload || {});
  const data = response?.data || {};
  if (!data.success) {
    throw new Error(serverMessage(data) || 'Failed to update account.');
  }
  return data;
};

export const listUserAccounts = async () => {
  const response = await authHttp.get(endpoint('listUsers'));
  const data = response?.data || {};
  if (!data.success) {
    throw new Error(serverMessage(data) || 'Failed to load users.');
  }
  return Array.isArray(data.users) ? data.users : [];
};

export const getRoleAccessMatrix = async () => {
  const response = await authHttp.get(endpoint('getRoleAccess'));
  const data = response?.data || {};
  if (!data.success) {
    throw new Error(serverMessage(data) || 'Failed to load role access.');
  }
  return {
    roles: Array.isArray(data.roles) ? data.roles : [],
    permissionCatalog: Array.isArray(data.permissionCatalog) ? data.permissionCatalog : [],
  };
};

export const saveRoleAccessMatrix = async (roleKey, permissions) => {
  const response = await authHttp.post(endpoint('saveRoleAccess'), {
    roleKey,
    permissions,
  });
  const data = response?.data || {};
  if (!data.success) {
    throw new Error(serverMessage(data) || 'Failed to save role access.');
  }
  return data;
};

export const getMessageRoutingSettings = async () => {
  try {
    const response = await authHttp.get(endpoint('getMessageRoutingSettings'));
    const data = response?.data || {};
    if (!data.success) {
      const error = new Error(serverMessage(data) || 'Failed to load inbox email routing.');
      error.responseData = data;
      throw error;
    }

    return {
      settings: data.settings && typeof data.settings === 'object' ? data.settings : {},
      categories: data.categories && typeof data.categories === 'object' ? data.categories : {},
    };
  } catch (error) {
    if (axios.isAxiosError(error) && error.response?.data) {
      error.responseData = error.response.data;
    }
    throw error;
  }
};

export const saveMessageRoutingSettings = async (settings) => {
  try {
    const response = await authHttp.post(endpoint('saveMessageRoutingSettings'), {
      settings: settings || {},
    });
    const data = response?.data || {};
    if (!data.success) {
      const error = new Error(serverMessage(data) || 'Failed to save inbox email routing.');
      error.responseData = data;
      throw error;
    }

    return {
      message: serverMessage(data) || 'Inbox email routing updated successfully.',
      settings: data.settings && typeof data.settings === 'object' ? data.settings : {},
    };
  } catch (error) {
    if (axios.isAxiosError(error) && error.response?.data) {
      error.responseData = error.response.data;
    }
    throw error;
  }
};

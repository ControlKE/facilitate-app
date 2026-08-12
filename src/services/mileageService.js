import axios from 'axios';
import { getStoredCurrentUser } from './authApi';
import { buildPhpApiUrl } from '../utils/phpApi';

const http = axios.create({ withCredentials: true });

const endpoint = (action, params = {}) => buildPhpApiUrl('mileage', action, params);

const unwrap = (response, fallback) => {
  const data = response?.data || {};
  if (data.success === false) throw new Error(data.message || fallback);
  return data;
};

export const defaultMileageRate = 0.3;
export const defaultMileageThreshold = 10;

export const gbp = (value) =>
  new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP' }).format(Number(value || 0));

export const miles = (value) => Math.round((Number(value || 0) + Number.EPSILON) * 100) / 100;

export const currentMileageUser = () => {
  const user = getStoredCurrentUser();
  return {
    userId: Number(user?.id || 0) || 1,
    driverName: user?.name || user?.username || user?.email || 'Current User',
    role: user?.role || 'carer',
  };
};

export const calculateMileagePreview = (entry = {}, settings = {}) => {
  const mileageRate = Number(settings.mileageRate || defaultMileageRate);
  const thresholdMiles = Number(settings.thresholdMiles || defaultMileageThreshold);
  const claimedMileage = miles(Math.max(0, Number(entry.odometerEnd || 0) - Number(entry.odometerStart || 0)));
  const lunchHomeMileageDeduction = Math.min(miles(entry.lunchHomeMileageDeduction), claimedMileage);
  const middayPayableMileage = miles(Math.max(0, Number(entry.middayPayableMileage || 0)));
  const adjustedClaimedMileage = miles(Math.max(0, claimedMileage - lunchHomeMileageDeduction + middayPayableMileage));
  const expectedSystemMileage = miles(Math.max(0, Number(entry.expectedSystemMileage || 0)));
  const passengerPickupMileage = miles(Math.max(0, Number(entry.passengerPickupMileage || 0)));
  const expectedTotalMileage = miles(expectedSystemMileage + passengerPickupMileage);
  const differenceFromSystem = miles(adjustedClaimedMileage - expectedTotalMileage);
  const thresholdFlag = adjustedClaimedMileage > expectedTotalMileage + thresholdMiles;
  const finalPayableMileage = thresholdFlag ? null : Math.max(adjustedClaimedMileage, expectedTotalMileage);
  const finalPayableAmount = finalPayableMileage === null ? null : miles(finalPayableMileage * mileageRate);
  return {
    claimedMileage,
    lunchHomeMileageDeduction,
    middayPayableMileage,
    adjustedClaimedMileage,
    expectedSystemMileage,
    passengerPickupMileage,
    expectedTotalMileage,
    differenceFromSystem,
    thresholdFlag,
    explanationRequired: thresholdFlag,
    finalPayableMileage,
    finalPayableAmount,
  };
};

export const statusColor = (status) => ({
  draft: 'grey',
  submitted: 'info',
  pending_review: 'warning',
  approved: 'success',
  rejected: 'error',
  adjusted: 'secondary',
}[status] || 'grey');

export const statusLabel = (status) =>
  String(status || 'draft').replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());

export const fetchMileageEntries = async (params = {}) => unwrap(await http.get(endpoint('list', params)), 'Failed to load mileage entries.');
export const fetchMileageEntry = async (id) => unwrap(await http.get(endpoint('get', { id })), 'Failed to load mileage entry.');
export const saveMileageEntry = async (payload) => {
  return unwrap(await http.post(endpoint('save'), payload), payload?.id ? 'Failed to update mileage entry.' : 'Failed to create mileage entry.');
};
export const deleteMileageEntry = async (id) => unwrap(await http.post(endpoint('delete'), { id }), 'Failed to delete mileage entry.');
export const submitMileageEntry = async (id) => unwrap(await http.post(endpoint('submit'), { id }), 'Failed to submit mileage entry.');
export const reviewMileageEntry = async (id, payload) => unwrap(await http.post(endpoint('review'), { ...payload, id }), 'Failed to review mileage entry.');
export const verifyMileageEntry = async (id, payload) => unwrap(await http.post(endpoint('verify'), { ...payload, id }), 'Failed to save mileage verification.');
export const fetchCarers = async () => unwrap(await http.get(endpoint('listCarers')), 'Failed to load carer directory.');
export const saveCarer = async (payload) => unwrap(await http.post(endpoint('saveCarer'), payload), 'Failed to save carer.');
export const deleteCarer = async (id) => unwrap(await http.post(endpoint('deleteCarer'), { id }), 'Failed to delete carer.');
export const fetchCurrentWeekMileage = async (params = {}) => unwrap(await http.get(endpoint('currentPayrollWeek', params)), 'Failed to load weekly mileage.');
export const submitMileageWeek = async (payload) => unwrap(await http.post(endpoint('submitWeek'), payload), 'Failed to submit weekly mileage.');
export const reviewMileageWeek = async (payload) => unwrap(await http.post(endpoint('reviewWeek'), payload), 'Failed to review weekly mileage.');
export const fetchPendingMileageReviews = async (params = {}) => unwrap(await http.get(endpoint('pending', params)), 'Failed to load pending reviews.');
export const fetchWeeklyMileageReport = async (params = {}) => unwrap(await http.get(endpoint('weeklyReport', params)), 'Failed to load mileage report.');
export const fetchCurrentPayrollWeek = async (params = {}) => unwrap(await http.get(endpoint('currentPayrollWeek', params)), 'Failed to load current payroll week.');
export const fetchWeeklyMileageBreakdown = async (params = {}) => unwrap(await http.get(endpoint('weeklyBreakdown', params)), 'Failed to load weekly mileage breakdown.');
export const fetchMileageSettings = async () => unwrap(await http.get(endpoint('settings')), 'Failed to load mileage settings.');
export const updateMileageSettings = async (payload) => unwrap(await http.post(endpoint('updateSettings'), payload), 'Failed to update mileage settings.');

import type { MileageEntryInput, MileageFilters, MileageReviewInput } from '../types/mileage.js';
import {
  createMileageSubmission,
  getMileageEntryById,
  getMileageSettings,
  getWeeklyBreakdown,
  getWeeklyReport,
  insertMileageEntry,
  listMileageEntries,
  reviewMileageEntry,
  softDeleteMileageEntry,
  submitMileageEntry,
  updateMileageEntry,
  updateMileageSettings,
} from '../db/mileageQueries.js';
import { getExpectedRouteMileage } from './mileageExpectedRouteService.js';
import {
  MILEAGE_RATE,
  calculateMileage,
  getSubmissionWeek,
  numericMileage,
} from '../utils/mileageCalculations.js';

const requireText = (value: unknown, field: string): string => {
  const text = String(value || '').trim();
  if (!text) throw new Error(`${field} is required.`);
  return text;
};

const requireDate = (value: unknown): string => {
  const text = String(value || '').trim();
  if (!/^\d{4}-\d{2}-\d{2}$/.test(text)) throw new Error('workDate must be YYYY-MM-DD.');
  return text;
};

export const prepareMileagePayload = async (input: MileageEntryInput, existingStatus?: string) => {
  const settings = await getMileageSettings();
  const workDate = requireDate(input.workDate);
  const odometerStart = numericMileage(input.odometerStart);
  const odometerEnd = numericMileage(input.odometerEnd);
  if (odometerEnd < odometerStart) throw new Error('Odometer end must be greater than or equal to odometer start.');

  const expectedFromRoute = await getExpectedRouteMileage({
    driverUserId: Number(input.userId || 0),
    workDate,
    startingLocation: String(input.startingLocation || ''),
    endingLocation: String(input.endingLocation || ''),
    carerIds: input.carerIds || [],
  });

  const expectedSystemMileage = Math.max(0, numericMileage(input.expectedSystemMileage ?? expectedFromRoute ?? 0));
  const passengerPickupMileage = Math.max(0, numericMileage(input.passengerPickupMileage));
  const middayPayableMileage = Math.max(0, numericMileage(input.middayPayableMileage));
  const lunchHomeMileageDeduction = Math.max(0, numericMileage(input.lunchHomeMileageDeduction));
  const calculated = calculateMileage({
    odometerStart,
    odometerEnd,
    expectedSystemMileage,
    passengerPickupMileage,
    middayPayableMileage,
    lunchHomeMileageDeduction,
    mileageRate: settings.mileageRate || MILEAGE_RATE,
    thresholdMiles: settings.thresholdMiles,
    adminStatus: existingStatus === 'submitted' || existingStatus === 'approved' ? (existingStatus as any) : undefined,
  });

  if (lunchHomeMileageDeduction > calculated.claimedMileage) {
    throw new Error('Lunch-home mileage deduction cannot exceed claimed mileage.');
  }
  if (calculated.explanationRequired && !String(input.driverExplanation || '').trim()) {
    throw new Error('Driver explanation is required when claimed mileage is more than 10 miles above system mileage.');
  }

  const week = getSubmissionWeek(workDate);
  return {
    userId: Number(input.userId || 0) || 1,
    driverName: String(input.driverName || '').trim() || 'Current User',
    workDate,
    submissionWeekStart: week.weekStart,
    submissionWeekEnd: week.weekEnd,
    startingLocation: requireText(input.startingLocation, 'Starting location'),
    endingLocation: requireText(input.endingLocation, 'Ending location'),
    odometerStart,
    odometerEnd,
    expectedSystemMileage,
    passengerPickupMileage,
    middayPayableMileage,
    middayMileageReason: String(input.middayMileageReason || '').trim(),
    lunchHomeMileageDeduction,
    wentHomeForLunch: Boolean(input.wentHomeForLunch || lunchHomeMileageDeduction > 0),
    driverExplanation: String(input.driverExplanation || '').trim(),
    notes: String(input.notes || '').trim(),
    mileageRate: settings.mileageRate || MILEAGE_RATE,
    ...calculated,
  };
};

export const mileageService = {
  list: (filters: MileageFilters) => listMileageEntries(filters),
  get: (id: number) => getMileageEntryById(id),
  async create(input: MileageEntryInput) {
    const payload = await prepareMileagePayload(input);
    return insertMileageEntry(payload, input.carerIds || []);
  },
  async update(id: number, input: MileageEntryInput) {
    const existing = await getMileageEntryById(id);
    if (!existing) return null;
    if (!['draft', 'pending_review'].includes(existing.adminStatus)) {
      throw new Error('Only draft or pending-review entries can be edited.');
    }
    const payload = await prepareMileagePayload(input, existing.adminStatus);
    return updateMileageEntry(id, payload, input.carerIds);
  },
  delete: (id: number) => softDeleteMileageEntry(id),
  submit: (id: number) => submitMileageEntry(id),
  async review(id: number, review: MileageReviewInput) {
    const existing = await getMileageEntryById(id);
    if (!existing) return null;
    const status = review.status;
    if (!['approved', 'rejected', 'adjusted'].includes(status)) {
      throw new Error('Review status must be approved, rejected, or adjusted.');
    }
    const adminAdjustedPayableMileage = status === 'adjusted'
      ? Math.max(0, numericMileage(review.adminAdjustedPayableMileage))
      : null;
    const calculated = calculateMileage({
      odometerStart: existing.odometerStart,
      odometerEnd: existing.odometerEnd,
      expectedSystemMileage: existing.expectedSystemMileage,
      passengerPickupMileage: existing.passengerPickupMileage,
      middayPayableMileage: existing.middayPayableMileage,
      lunchHomeMileageDeduction: existing.lunchHomeMileageDeduction,
      adminStatus: status,
      adminAdjustedPayableMileage,
      mileageRate: existing.mileageRate,
      thresholdMiles: (await getMileageSettings()).thresholdMiles,
    });
    return reviewMileageEntry(id, { ...calculated, adminAdjustedPayableMileage }, review);
  },
  async submitWeek(input: { userId: number; driverName?: string; weekStart: string; weekEnd: string }) {
    return createMileageSubmission(input);
  },
  report: (weekStart?: string, weekEnd?: string) => getWeeklyReport(weekStart, weekEnd),
  currentPayrollWeek(date?: string) {
    return getSubmissionWeek(date || new Date().toISOString().slice(0, 10));
  },
  weeklyBreakdown(input: any) {
    const week = getSubmissionWeek(String(input.weekStart || input.date || new Date().toISOString().slice(0, 10)));
    return getWeeklyBreakdown({
      weekStart: week.weekStart,
      weekEnd: week.weekEnd,
      userId: input.userId ? Number(input.userId) : undefined,
      driver: input.driver ? String(input.driver) : undefined,
      status: input.status ? String(input.status) : undefined,
      flaggedOnly: Boolean(input.flaggedOnly),
      pendingOnly: Boolean(input.pendingOnly),
    });
  },
  settings: () => getMileageSettings(),
  updateSettings(input: any) {
    const mileageRate = Math.max(0, numericMileage(input.mileageRate, MILEAGE_RATE));
    const thresholdMiles = Math.max(0, numericMileage(input.thresholdMiles, 10));
    return updateMileageSettings({
      mileageRate,
      thresholdMiles,
      weekStartsOn: String(input.weekStartsOn || 'wednesday').trim().toLowerCase(),
      submissionDueDay: String(input.submissionDueDay || 'tuesday').trim().toLowerCase(),
      paymentWindow: String(input.paymentWindow || 'thursday-friday').trim().toLowerCase(),
      updatedBy: Number(input.updatedBy || 0) || null,
    });
  },
};

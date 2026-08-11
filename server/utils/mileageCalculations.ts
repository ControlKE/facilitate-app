import type { MileageCalculatedValues, MileageStatus } from '../types/mileage.js';

export const MILEAGE_RATE = 0.3;
export const MILEAGE_THRESHOLD_MILES = 10;

const money = (value: number): number => Math.round((value + Number.EPSILON) * 100) / 100;
const miles = (value: number): number => Math.round((value + Number.EPSILON) * 100) / 100;

export const numericMileage = (value: unknown, fallback = 0): number => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : fallback;
};

export const computeClaimedMileage = (odometerStart: number, odometerEnd: number): number =>
  miles(Math.max(0, numericMileage(odometerEnd) - numericMileage(odometerStart)));

export const computeAdjustedClaimedMileage = (
  claimedMileage: number,
  lunchHomeMileageDeduction = 0,
  middayPayableMileage = 0,
): number => miles(Math.max(0, numericMileage(claimedMileage) - numericMileage(lunchHomeMileageDeduction) + numericMileage(middayPayableMileage)));

export const computeDifferenceFromSystem = (
  adjustedClaimedMileage: number,
  expectedSystemMileage = 0,
): number => miles(numericMileage(adjustedClaimedMileage) - numericMileage(expectedSystemMileage));

export const computeFinalPayableMileage = (
  adjustedClaimedMileage: number,
  expectedSystemMileage = 0,
  thresholdFlag = false,
): number | null => {
  if (thresholdFlag) return null;
  return miles(Math.max(numericMileage(adjustedClaimedMileage), numericMileage(expectedSystemMileage)));
};

export const computeFinalPayableAmount = (
  finalPayableMileage: number | null | undefined,
  mileageRate = MILEAGE_RATE,
): number | null => {
  if (finalPayableMileage === null || finalPayableMileage === undefined) return null;
  return money(numericMileage(finalPayableMileage) * numericMileage(mileageRate, MILEAGE_RATE));
};

export const determineMileageStatus = (thresholdFlag: boolean, requestedStatus?: MileageStatus): MileageStatus => {
  if (requestedStatus) return requestedStatus;
  return thresholdFlag ? 'pending_review' : 'draft';
};

export const calculateMileage = (input: {
  odometerStart: number;
  odometerEnd: number;
  expectedSystemMileage?: number;
  passengerPickupMileage?: number;
  middayPayableMileage?: number;
  lunchHomeMileageDeduction?: number;
  adminStatus?: MileageStatus;
  mileageRate?: number;
  thresholdMiles?: number;
  adminAdjustedPayableMileage?: number | null;
}): MileageCalculatedValues => {
  const claimedMileage = computeClaimedMileage(input.odometerStart, input.odometerEnd);
  const deduction = Math.min(numericMileage(input.lunchHomeMileageDeduction), claimedMileage);
  const middayPayableMileage = Math.max(0, numericMileage(input.middayPayableMileage));
  const expectedSystemMileage = Math.max(0, numericMileage(input.expectedSystemMileage));
  const passengerPickupMileage = Math.max(0, numericMileage(input.passengerPickupMileage));
  const expectedTotalMileage = expectedSystemMileage + passengerPickupMileage;
  const adjustedClaimedMileage = computeAdjustedClaimedMileage(claimedMileage, deduction, middayPayableMileage);
  const differenceFromSystem = computeDifferenceFromSystem(adjustedClaimedMileage, expectedTotalMileage);
  const thresholdMiles = Math.max(0, numericMileage(input.thresholdMiles, MILEAGE_THRESHOLD_MILES));
  const thresholdFlag = adjustedClaimedMileage > expectedTotalMileage + thresholdMiles;
  const explanationRequired = thresholdFlag;
  const adminStatus = determineMileageStatus(thresholdFlag, input.adminStatus);

  let finalPayableMileage = computeFinalPayableMileage(adjustedClaimedMileage, expectedTotalMileage, thresholdFlag);
  if (adminStatus === 'adjusted' && input.adminAdjustedPayableMileage !== null && input.adminAdjustedPayableMileage !== undefined) {
    finalPayableMileage = Math.max(0, numericMileage(input.adminAdjustedPayableMileage));
  }
  if (adminStatus === 'rejected') {
    finalPayableMileage = 0;
  }
  if (adminStatus === 'approved' && thresholdFlag) {
    finalPayableMileage = adjustedClaimedMileage;
  }

  return {
    claimedMileage,
    adjustedClaimedMileage,
    differenceFromSystem,
    thresholdFlag,
    explanationRequired,
    finalPayableMileage,
    finalPayableAmount: computeFinalPayableAmount(finalPayableMileage, input.mileageRate),
    adminStatus,
  };
};

export const getSubmissionWeek = (workDate: string): { weekStart: string; weekEnd: string } => {
  const date = new Date(`${workDate}T12:00:00`);
  if (Number.isNaN(date.getTime())) {
    throw new Error('Invalid work date.');
  }
  // Business cycle is Wednesday through Tuesday because mileage forms are submitted every Tuesday.
  const day = date.getDay();
  const daysSinceWednesday = (day + 4) % 7;
  const start = new Date(date);
  start.setDate(date.getDate() - daysSinceWednesday);
  const end = new Date(start);
  end.setDate(start.getDate() + 6);
  const iso = (value: Date) => value.toISOString().slice(0, 10);
  return { weekStart: iso(start), weekEnd: iso(end) };
};

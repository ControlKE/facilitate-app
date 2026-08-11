export type MileageStatus = 'draft' | 'submitted' | 'pending_review' | 'approved' | 'rejected' | 'adjusted';

export interface MileageEntryInput {
  userId: number;
  driverName?: string;
  workDate: string;
  startingLocation: string;
  endingLocation: string;
  odometerStart: number;
  odometerEnd: number;
  expectedSystemMileage?: number;
  passengerPickupMileage?: number;
  middayPayableMileage?: number;
  middayMileageReason?: string;
  lunchHomeMileageDeduction?: number;
  wentHomeForLunch?: boolean;
  driverExplanation?: string;
  notes?: string;
  carerIds?: number[];
}

export interface MileageReviewInput {
  reviewerId?: number;
  reviewerName?: string;
  status: 'approved' | 'rejected' | 'adjusted';
  adminAdjustedPayableMileage?: number | null;
  adminNotes?: string;
}

export interface MileageCalculatedValues {
  claimedMileage: number;
  adjustedClaimedMileage: number;
  differenceFromSystem: number;
  thresholdFlag: boolean;
  explanationRequired: boolean;
  finalPayableMileage: number | null;
  finalPayableAmount: number | null;
  adminStatus: MileageStatus;
}

export interface MileageFilters {
  userId?: number;
  weekStart?: string;
  weekEnd?: string;
  status?: string;
  flaggedOnly?: boolean;
  driver?: string;
}

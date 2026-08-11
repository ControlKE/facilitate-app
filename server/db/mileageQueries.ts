import type { ResultSetHeader, RowDataPacket } from 'mysql2';
import { pool } from './mysql.js';
import type { MileageFilters, MileageReviewInput } from '../types/mileage.js';

const entryColumns = `
  id AS id,
  user_id AS userId,
  driver_name AS driverName,
  work_date AS workDate,
  submission_week_start AS submissionWeekStart,
  submission_week_end AS submissionWeekEnd,
  starting_location AS startingLocation,
  ending_location AS endingLocation,
  odometer_start AS odometerStart,
  odometer_end AS odometerEnd,
  claimed_mileage AS claimedMileage,
  expected_system_mileage AS expectedSystemMileage,
  passenger_pickup_mileage AS passengerPickupMileage,
  midday_payable_mileage AS middayPayableMileage,
  midday_mileage_reason AS middayMileageReason,
  lunch_home_mileage_deduction AS lunchHomeMileageDeduction,
  went_home_for_lunch AS wentHomeForLunch,
  adjusted_claimed_mileage AS adjustedClaimedMileage,
  difference_from_system AS differenceFromSystem,
  threshold_flag AS thresholdFlag,
  explanation_required AS explanationRequired,
  driver_explanation AS driverExplanation,
  admin_status AS adminStatus,
  admin_adjusted_payable_mileage AS adminAdjustedPayableMileage,
  final_payable_mileage AS finalPayableMileage,
  mileage_rate AS mileageRate,
  final_payable_amount AS finalPayableAmount,
  notes AS notes,
  admin_notes AS adminNotes,
  submitted_at AS submittedAt,
  reviewed_at AS reviewedAt,
  created_at AS createdAt,
  updated_at AS updatedAt
`;

export const mapMileageRow = (row: RowDataPacket) => ({
  ...row,
  id: Number(row.id),
  userId: Number(row.userId || 0),
  odometerStart: Number(row.odometerStart || 0),
  odometerEnd: Number(row.odometerEnd || 0),
  claimedMileage: Number(row.claimedMileage || 0),
  expectedSystemMileage: Number(row.expectedSystemMileage || 0),
  passengerPickupMileage: Number(row.passengerPickupMileage || 0),
  middayPayableMileage: Number(row.middayPayableMileage || 0),
  middayMileageReason: String(row.middayMileageReason || ''),
  lunchHomeMileageDeduction: Number(row.lunchHomeMileageDeduction || 0),
  wentHomeForLunch: Boolean(row.wentHomeForLunch),
  adjustedClaimedMileage: Number(row.adjustedClaimedMileage || 0),
  differenceFromSystem: Number(row.differenceFromSystem || 0),
  thresholdFlag: Boolean(row.thresholdFlag),
  explanationRequired: Boolean(row.explanationRequired),
  adminAdjustedPayableMileage: row.adminAdjustedPayableMileage === null ? null : Number(row.adminAdjustedPayableMileage || 0),
  finalPayableMileage: row.finalPayableMileage === null ? null : Number(row.finalPayableMileage || 0),
  mileageRate: Number(row.mileageRate || 0.3),
  finalPayableAmount: row.finalPayableAmount === null ? null : Number(row.finalPayableAmount || 0),
});

export const listMileageEntries = async (filters: MileageFilters = {}) => {
  const where = ['deleted_at IS NULL'];
  const params: unknown[] = [];

  if (filters.userId) {
    where.push('user_id = ?');
    params.push(filters.userId);
  }
  if (filters.weekStart) {
    where.push('submission_week_start >= ?');
    params.push(filters.weekStart);
  }
  if (filters.weekEnd) {
    where.push('submission_week_end <= ?');
    params.push(filters.weekEnd);
  }
  if (filters.status) {
    where.push('admin_status = ?');
    params.push(filters.status);
  }
  if (filters.flaggedOnly) {
    where.push('threshold_flag = 1');
  }
  if (filters.driver) {
    where.push('driver_name LIKE ?');
    params.push(`%${filters.driver}%`);
  }

  const [rows] = await pool.query<RowDataPacket[]>(
    `SELECT ${entryColumns} FROM mileage_entries WHERE ${where.join(' AND ')} ORDER BY work_date DESC, id DESC LIMIT 500`,
    params,
  );
  return rows.map(mapMileageRow);
};

export const getMileageEntryById = async (id: number) => {
  const [rows] = await pool.query<RowDataPacket[]>(
    `SELECT ${entryColumns} FROM mileage_entries WHERE id = ? AND deleted_at IS NULL LIMIT 1`,
    [id],
  );
  return rows[0] ? mapMileageRow(rows[0]) : null;
};

export const insertMileageEntry = async (payload: any, carerIds: number[] = []) => {
  const conn = await pool.getConnection();
  try {
    await conn.beginTransaction();
    const [result] = await conn.query<ResultSetHeader>(
      `INSERT INTO mileage_entries (
        user_id, driver_name, work_date, submission_week_start, submission_week_end,
        starting_location, ending_location, odometer_start, odometer_end, claimed_mileage,
        expected_system_mileage, passenger_pickup_mileage, midday_payable_mileage, midday_mileage_reason, lunch_home_mileage_deduction, went_home_for_lunch,
        adjusted_claimed_mileage, difference_from_system, threshold_flag, explanation_required,
        driver_explanation, admin_status, final_payable_mileage, mileage_rate, final_payable_amount, notes
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [
        payload.userId,
        payload.driverName,
        payload.workDate,
        payload.submissionWeekStart,
        payload.submissionWeekEnd,
        payload.startingLocation,
        payload.endingLocation,
        payload.odometerStart,
        payload.odometerEnd,
        payload.claimedMileage,
        payload.expectedSystemMileage,
        payload.passengerPickupMileage,
        payload.middayPayableMileage,
        payload.middayMileageReason,
        payload.lunchHomeMileageDeduction,
        payload.wentHomeForLunch ? 1 : 0,
        payload.adjustedClaimedMileage,
        payload.differenceFromSystem,
        payload.thresholdFlag ? 1 : 0,
        payload.explanationRequired ? 1 : 0,
        payload.driverExplanation,
        payload.adminStatus,
        payload.finalPayableMileage,
        payload.mileageRate,
        payload.finalPayableAmount,
        payload.notes,
      ],
    );
    const id = result.insertId;
    for (const carerId of carerIds) {
      await conn.query('INSERT INTO mileage_entry_care_staff (mileage_entry_id, user_id) VALUES (?, ?)', [id, carerId]);
    }
    await conn.commit();
    return getMileageEntryById(id);
  } catch (error) {
    await conn.rollback();
    throw error;
  } finally {
    conn.release();
  }
};

export const updateMileageEntry = async (id: number, payload: any, carerIds?: number[]) => {
  const conn = await pool.getConnection();
  try {
    await conn.beginTransaction();
    await conn.query(
      `UPDATE mileage_entries SET
        user_id = ?, driver_name = ?, work_date = ?, submission_week_start = ?, submission_week_end = ?,
        starting_location = ?, ending_location = ?, odometer_start = ?, odometer_end = ?, claimed_mileage = ?,
        expected_system_mileage = ?, passenger_pickup_mileage = ?, midday_payable_mileage = ?, midday_mileage_reason = ?, lunch_home_mileage_deduction = ?, went_home_for_lunch = ?,
        adjusted_claimed_mileage = ?, difference_from_system = ?, threshold_flag = ?, explanation_required = ?,
        driver_explanation = ?, admin_status = ?, final_payable_mileage = ?, mileage_rate = ?, final_payable_amount = ?, notes = ?
      WHERE id = ? AND deleted_at IS NULL`,
      [
        payload.userId,
        payload.driverName,
        payload.workDate,
        payload.submissionWeekStart,
        payload.submissionWeekEnd,
        payload.startingLocation,
        payload.endingLocation,
        payload.odometerStart,
        payload.odometerEnd,
        payload.claimedMileage,
        payload.expectedSystemMileage,
        payload.passengerPickupMileage,
        payload.middayPayableMileage,
        payload.middayMileageReason,
        payload.lunchHomeMileageDeduction,
        payload.wentHomeForLunch ? 1 : 0,
        payload.adjustedClaimedMileage,
        payload.differenceFromSystem,
        payload.thresholdFlag ? 1 : 0,
        payload.explanationRequired ? 1 : 0,
        payload.driverExplanation,
        payload.adminStatus,
        payload.finalPayableMileage,
        payload.mileageRate,
        payload.finalPayableAmount,
        payload.notes,
        id,
      ],
    );
    if (Array.isArray(carerIds)) {
      await conn.query('DELETE FROM mileage_entry_care_staff WHERE mileage_entry_id = ?', [id]);
      for (const carerId of carerIds) {
        await conn.query('INSERT INTO mileage_entry_care_staff (mileage_entry_id, user_id) VALUES (?, ?)', [id, carerId]);
      }
    }
    await conn.commit();
    return getMileageEntryById(id);
  } catch (error) {
    await conn.rollback();
    throw error;
  } finally {
    conn.release();
  }
};

export const softDeleteMileageEntry = async (id: number) => {
  await pool.query('UPDATE mileage_entries SET deleted_at = NOW() WHERE id = ?', [id]);
};

export const submitMileageEntry = async (id: number) => {
  await pool.query(
    `UPDATE mileage_entries
     SET admin_status = IF(threshold_flag = 1, 'pending_review', 'submitted'), submitted_at = COALESCE(submitted_at, NOW())
     WHERE id = ? AND deleted_at IS NULL`,
    [id],
  );
  return getMileageEntryById(id);
};

export const reviewMileageEntry = async (id: number, values: any, review: MileageReviewInput) => {
  const conn = await pool.getConnection();
  try {
    await conn.beginTransaction();
    await conn.query(
      `UPDATE mileage_entries SET
        admin_status = ?, admin_adjusted_payable_mileage = ?, final_payable_mileage = ?,
        final_payable_amount = ?, admin_notes = ?, reviewed_at = NOW()
       WHERE id = ? AND deleted_at IS NULL`,
      [
        values.adminStatus,
        values.adminAdjustedPayableMileage,
        values.finalPayableMileage,
        values.finalPayableAmount,
        review.adminNotes || '',
        id,
      ],
    );
    await conn.query(
      `INSERT INTO mileage_reviews (
        mileage_entry_id, reviewer_id, reviewer_name, review_status,
        adjusted_payable_mileage, final_payable_mileage, admin_notes
      ) VALUES (?, ?, ?, ?, ?, ?, ?)`,
      [
        id,
        review.reviewerId || null,
        review.reviewerName || '',
        review.status,
        values.adminAdjustedPayableMileage,
        values.finalPayableMileage,
        review.adminNotes || '',
      ],
    );
    await conn.commit();
    return getMileageEntryById(id);
  } catch (error) {
    await conn.rollback();
    throw error;
  } finally {
    conn.release();
  }
};

export const createMileageSubmission = async (payload: any) => {
  const [result] = await pool.query<ResultSetHeader>(
    `INSERT INTO mileage_submissions (
      user_id, driver_name, week_start, week_end, status,
      total_claimed_mileage, total_adjusted_claimed_mileage, total_expected_system_mileage,
      total_final_payable_mileage, total_payable_amount, flagged_count, submitted_at
    )
    SELECT ?, ?, ?, ?, 'submitted',
      COALESCE(SUM(claimed_mileage), 0), COALESCE(SUM(adjusted_claimed_mileage), 0),
      COALESCE(SUM(expected_system_mileage + passenger_pickup_mileage), 0), COALESCE(SUM(final_payable_mileage), 0),
      COALESCE(SUM(final_payable_amount), 0), COALESCE(SUM(threshold_flag), 0), NOW()
    FROM mileage_entries
    WHERE user_id = ? AND submission_week_start = ? AND submission_week_end = ? AND deleted_at IS NULL`,
    [payload.userId, payload.driverName || '', payload.weekStart, payload.weekEnd, payload.userId, payload.weekStart, payload.weekEnd],
  );
  await pool.query(
    `UPDATE mileage_entries SET admin_status = IF(threshold_flag = 1, 'pending_review', 'submitted'), submitted_at = COALESCE(submitted_at, NOW())
     WHERE user_id = ? AND submission_week_start = ? AND submission_week_end = ? AND deleted_at IS NULL`,
    [payload.userId, payload.weekStart, payload.weekEnd],
  );
  return result.insertId;
};

export const getWeeklyReport = async (weekStart?: string, weekEnd?: string) => {
  const where = ['deleted_at IS NULL'];
  const params: unknown[] = [];
  if (weekStart) {
    where.push('submission_week_start >= ?');
    params.push(weekStart);
  }
  if (weekEnd) {
    where.push('submission_week_end <= ?');
    params.push(weekEnd);
  }
  const [rows] = await pool.query<RowDataPacket[]>(
    `SELECT
      user_id AS userId,
      driver_name AS driverName,
      submission_week_start AS weekStart,
      submission_week_end AS weekEnd,
      COUNT(*) AS entryCount,
      COALESCE(SUM(claimed_mileage), 0) AS totalClaimedMileage,
      COALESCE(SUM(adjusted_claimed_mileage), 0) AS totalAdjustedClaimedMileage,
      COALESCE(SUM(expected_system_mileage), 0) AS totalExpectedSystemMileage,
      COALESCE(SUM(passenger_pickup_mileage), 0) AS totalPassengerPickupMileage,
      COALESCE(SUM(final_payable_mileage), 0) AS totalFinalPayableMileage,
      COALESCE(SUM(final_payable_amount), 0) AS totalPayableAmount,
      COALESCE(SUM(threshold_flag), 0) AS flaggedCount
    FROM mileage_entries
    WHERE ${where.join(' AND ')}
    GROUP BY user_id, driver_name, submission_week_start, submission_week_end
    ORDER BY submission_week_start DESC, driver_name ASC`,
    params,
  );
  return rows.map((row) => ({
    ...row,
    entryCount: Number(row.entryCount || 0),
    totalClaimedMileage: Number(row.totalClaimedMileage || 0),
    totalAdjustedClaimedMileage: Number(row.totalAdjustedClaimedMileage || 0),
    totalExpectedSystemMileage: Number(row.totalExpectedSystemMileage || 0),
    totalPassengerPickupMileage: Number(row.totalPassengerPickupMileage || 0),
    totalFinalPayableMileage: Number(row.totalFinalPayableMileage || 0),
    totalPayableAmount: Number(row.totalPayableAmount || 0),
    flaggedCount: Number(row.flaggedCount || 0),
  }));
};

export const getMileageSettings = async () => {
  const [rows] = await pool.query<RowDataPacket[]>(
    'SELECT setting_key AS settingKey, setting_value AS settingValue, description FROM mileage_settings ORDER BY setting_key ASC',
  );
  const settings = rows.reduce<Record<string, string>>((acc, row) => {
    acc[String(row.settingKey)] = String(row.settingValue);
    return acc;
  }, {});
  return {
    mileageRate: Number(settings.mileage_rate || 0.3),
    thresholdMiles: Number(settings.threshold_miles || 10),
    weekStartsOn: settings.week_starts_on || 'wednesday',
    submissionDueDay: settings.submission_due_day || 'tuesday',
    paymentWindow: settings.payment_window || 'thursday-friday',
    rows,
  };
};

const roundNumber = (value: unknown): number => Math.round((Number(value || 0) + Number.EPSILON) * 100) / 100;

const deriveWeeklyStatus = (row: RowDataPacket): string => {
  const entryCount = Number(row.entryCount || 0);
  const pendingCount = Number(row.pendingReviewCount || 0);
  const explanationCount = Number(row.explanationRequiredCount || 0);
  const paidCount = Number(row.paidCount || 0);
  const readyCount = Number(row.readyCount || 0);
  const rejectedCount = Number(row.rejectedCount || 0);

  if (pendingCount > 0 || explanationCount > 0) return 'needs_review';
  if (entryCount > 0 && paidCount === entryCount) return 'paid';
  if (entryCount > 0 && readyCount + rejectedCount === entryCount) return 'ready';
  if (entryCount > 0 && readyCount > 0) return 'mixed';
  return 'mixed';
};

export const getWeeklyBreakdown = async (filters: MileageFilters & {
  pendingOnly?: boolean;
  weekStart: string;
  weekEnd: string;
}) => {
  const where = ['deleted_at IS NULL', 'submission_week_start = ?', 'submission_week_end = ?'];
  const params: unknown[] = [filters.weekStart, filters.weekEnd];

  if (filters.userId) {
    where.push('user_id = ?');
    params.push(filters.userId);
  }
  if (filters.driver) {
    where.push('driver_name LIKE ?');
    params.push(`%${filters.driver}%`);
  }
  if (filters.status) {
    where.push('admin_status = ?');
    params.push(filters.status);
  }
  if (filters.flaggedOnly) {
    where.push('threshold_flag = 1');
  }
  if (filters.pendingOnly) {
    where.push("(admin_status = 'pending_review' OR explanation_required = 1)");
  }

  const whereSql = where.join(' AND ');
  const [summaryRows] = await pool.query<RowDataPacket[]>(
    `SELECT
      user_id AS userId,
      driver_name AS driverName,
      COUNT(*) AS entryCount,
      ROUND(COALESCE(SUM(claimed_mileage), 0), 2) AS claimedMileageTotal,
      ROUND(COALESCE(SUM(expected_system_mileage), 0), 2) AS accessMileageTotal,
      ROUND(COALESCE(SUM(passenger_pickup_mileage), 0), 2) AS passengerPickupMileageTotal,
      ROUND(COALESCE(SUM(midday_payable_mileage), 0), 2) AS middayPayableMileageTotal,
      ROUND(COALESCE(SUM(expected_system_mileage + passenger_pickup_mileage), 0), 2) AS expectedTotalMileage,
      ROUND(COALESCE(SUM(expected_system_mileage + passenger_pickup_mileage - claimed_mileage), 0), 2) AS mileageDifference,
      ROUND(COALESCE(SUM(final_payable_mileage), 0), 2) AS finalPayableMileageTotal,
      ROUND(COALESCE(SUM(final_payable_amount), 0), 2) AS finalPayableAmountTotal,
      ROUND(COALESCE(AVG(mileage_rate), 0.30), 2) AS rate,
      COALESCE(SUM(threshold_flag), 0) AS flaggedCount,
      COALESCE(SUM(explanation_required), 0) AS explanationRequiredCount,
      COALESCE(SUM(admin_status = 'pending_review'), 0) AS pendingReviewCount,
      COALESCE(SUM(admin_status IN ('approved','adjusted')), 0) AS readyCount,
      COALESCE(SUM(admin_status = 'paid'), 0) AS paidCount,
      COALESCE(SUM(admin_status = 'rejected'), 0) AS rejectedCount
    FROM mileage_entries
    WHERE ${whereSql}
    GROUP BY user_id, driver_name
    ORDER BY driver_name ASC`,
    params,
  );

  const [detailRows] = await pool.query<RowDataPacket[]>(
    `SELECT ${entryColumns},
      passenger_pickup_mileage AS passengerPickupMileage,
      midday_payable_mileage AS middayPayableMileage,
      midday_mileage_reason AS middayMileageReason,
      ROUND(expected_system_mileage + passenger_pickup_mileage, 2) AS expectedTotalMileage,
      ROUND(expected_system_mileage + passenger_pickup_mileage - claimed_mileage, 2) AS managerMileageDifference
    FROM mileage_entries
    WHERE ${whereSql}
    ORDER BY driver_name ASC, work_date ASC, id ASC`,
    params,
  );

  const breakdownKey = (userId: unknown, driverName: unknown): string =>
    `${Number(userId || 0)}|${String(driverName || '').trim().toLowerCase()}`;

  const detailsByUser = detailRows.reduce<Record<string, any[]>>((acc, row) => {
    const entry = {
      ...mapMileageRow(row),
      passengerPickupMileage: Number(row.passengerPickupMileage || 0),
      middayPayableMileage: Number(row.middayPayableMileage || 0),
      middayMileageReason: String(row.middayMileageReason || ''),
      expectedTotalMileage: Number(row.expectedTotalMileage || 0),
      managerMileageDifference: Number(row.managerMileageDifference || 0),
    };
    const key = breakdownKey(entry.userId, entry.driverName);
    acc[key] = acc[key] || [];
    acc[key].push(entry);
    return acc;
  }, {});

  const rows = summaryRows.map((row) => ({
    userId: Number(row.userId || 0),
    driverName: String(row.driverName || ''),
    entryCount: Number(row.entryCount || 0),
    claimedMileageTotal: roundNumber(row.claimedMileageTotal),
    accessMileageTotal: roundNumber(row.accessMileageTotal),
    passengerPickupMileageTotal: roundNumber(row.passengerPickupMileageTotal),
    middayPayableMileageTotal: roundNumber(row.middayPayableMileageTotal),
    expectedTotalMileage: roundNumber(row.expectedTotalMileage),
    mileageDifference: roundNumber(row.mileageDifference),
    finalPayableMileageTotal: roundNumber(row.finalPayableMileageTotal),
    finalPayableAmountTotal: roundNumber(row.finalPayableAmountTotal),
    rate: Number(row.rate || 0.3),
    flaggedCount: Number(row.flaggedCount || 0),
    pendingReviewCount: Number(row.pendingReviewCount || 0),
    explanationRequiredCount: Number(row.explanationRequiredCount || 0),
    weeklyStatus: deriveWeeklyStatus(row),
    entries: detailsByUser[breakdownKey(row.userId, row.driverName)] || [],
  }));

  const totals = rows.reduce((acc, row) => {
    acc.totalCarers += 1;
    acc.entryCount += row.entryCount;
    acc.claimedMileageTotal += row.claimedMileageTotal;
    acc.accessMileageTotal += row.accessMileageTotal;
    acc.passengerPickupMileageTotal += row.passengerPickupMileageTotal;
    acc.middayPayableMileageTotal += row.middayPayableMileageTotal;
    acc.expectedTotalMileage += row.expectedTotalMileage;
    acc.mileageDifference += row.mileageDifference;
    acc.finalPayableMileageTotal += row.finalPayableMileageTotal;
    acc.finalPayableAmountTotal += row.finalPayableAmountTotal;
    acc.flaggedCount += row.flaggedCount;
    acc.pendingReviewCount += row.pendingReviewCount;
    return acc;
  }, {
    totalCarers: 0,
    entryCount: 0,
    claimedMileageTotal: 0,
    accessMileageTotal: 0,
    passengerPickupMileageTotal: 0,
    middayPayableMileageTotal: 0,
    expectedTotalMileage: 0,
    mileageDifference: 0,
    finalPayableMileageTotal: 0,
    finalPayableAmountTotal: 0,
    flaggedCount: 0,
    pendingReviewCount: 0,
  });

  Object.keys(totals).forEach((key) => {
    if (key !== 'totalCarers' && key !== 'entryCount' && key !== 'flaggedCount' && key !== 'pendingReviewCount') {
      (totals as Record<string, number>)[key] = roundNumber((totals as Record<string, number>)[key]);
    }
  });

  return {
    week: {
      weekStart: filters.weekStart,
      weekEnd: filters.weekEnd,
    },
    rows,
    totals,
    statusCounts: rows.reduce<Record<string, number>>((acc, row) => {
      acc[row.weeklyStatus] = (acc[row.weeklyStatus] || 0) + 1;
      return acc;
    }, {}),
  };
};

export const updateMileageSettings = async (settings: {
  mileageRate?: number;
  thresholdMiles?: number;
  weekStartsOn?: string;
  submissionDueDay?: string;
  paymentWindow?: string;
  updatedBy?: number | null;
}) => {
  const entries = [
    ['mileage_rate', settings.mileageRate, 'Default payable mileage rate in GBP per mile.'],
    ['threshold_miles', settings.thresholdMiles, 'Miles above expected route mileage before explanation and review are required.'],
    ['week_starts_on', settings.weekStartsOn, 'Mileage submission week starts on this day.'],
    ['submission_due_day', settings.submissionDueDay, 'Weekly mileage forms are submitted on this day.'],
    ['payment_window', settings.paymentWindow, 'Mileage payment window.'],
  ].filter((entry) => entry[1] !== undefined && entry[1] !== null && String(entry[1]).trim() !== '');

  for (const [key, value, description] of entries) {
    await pool.query(
      `INSERT INTO mileage_settings (setting_key, setting_value, description, updated_by)
       VALUES (?, ?, ?, ?)
       ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), description = VALUES(description), updated_by = VALUES(updated_by), updated_at = CURRENT_TIMESTAMP`,
      [key, String(value), description, settings.updatedBy || null],
    );
  }

  return getMileageSettings();
};

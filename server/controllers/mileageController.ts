import type { Request, Response } from 'express';
import { mileageService } from '../services/mileageService.js';
import { getSubmissionWeek } from '../utils/mileageCalculations.js';

const idParam = (req: Request): number => Number.parseInt(req.params.id, 10);

const filtersFromRequest = (req: Request) => ({
  userId: req.query.userId ? Number(req.query.userId) : undefined,
  weekStart: req.query.weekStart ? String(req.query.weekStart) : undefined,
  weekEnd: req.query.weekEnd ? String(req.query.weekEnd) : undefined,
  status: req.query.status ? String(req.query.status) : undefined,
  flaggedOnly: String(req.query.flaggedOnly || '') === 'true',
  driver: req.query.driver ? String(req.query.driver) : undefined,
});

const handleError = (res: Response, error: unknown, fallback: string) => {
  res.status(400).json({
    success: false,
    message: error instanceof Error ? error.message : fallback,
  });
};

export const mileageController = {
  async list(req: Request, res: Response) {
    try {
      res.json({ success: true, entries: await mileageService.list(filtersFromRequest(req)) });
    } catch (error) {
      handleError(res, error, 'Failed to load mileage entries.');
    }
  },
  async get(req: Request, res: Response) {
    try {
      const entry = await mileageService.get(idParam(req));
      if (!entry) {
        res.status(404).json({ success: false, message: 'Mileage entry not found.' });
        return;
      }
      res.json({ success: true, entry });
    } catch (error) {
      handleError(res, error, 'Failed to load mileage entry.');
    }
  },
  async create(req: Request, res: Response) {
    try {
      res.status(201).json({ success: true, entry: await mileageService.create(req.body || {}) });
    } catch (error) {
      handleError(res, error, 'Failed to create mileage entry.');
    }
  },
  async update(req: Request, res: Response) {
    try {
      const entry = await mileageService.update(idParam(req), req.body || {});
      if (!entry) {
        res.status(404).json({ success: false, message: 'Mileage entry not found.' });
        return;
      }
      res.json({ success: true, entry });
    } catch (error) {
      handleError(res, error, 'Failed to update mileage entry.');
    }
  },
  async remove(req: Request, res: Response) {
    try {
      await mileageService.delete(idParam(req));
      res.json({ success: true });
    } catch (error) {
      handleError(res, error, 'Failed to delete mileage entry.');
    }
  },
  async submit(req: Request, res: Response) {
    try {
      res.json({ success: true, entry: await mileageService.submit(idParam(req)) });
    } catch (error) {
      handleError(res, error, 'Failed to submit mileage entry.');
    }
  },
  async review(req: Request, res: Response) {
    try {
      const entry = await mileageService.review(idParam(req), req.body || {});
      if (!entry) {
        res.status(404).json({ success: false, message: 'Mileage entry not found.' });
        return;
      }
      res.json({ success: true, entry });
    } catch (error) {
      handleError(res, error, 'Failed to review mileage entry.');
    }
  },
  async currentWeek(req: Request, res: Response) {
    try {
      const baseDate = String(req.query.date || new Date().toISOString().slice(0, 10));
      const week = getSubmissionWeek(baseDate);
      const entries = await mileageService.list({
        ...filtersFromRequest(req),
        weekStart: week.weekStart,
        weekEnd: week.weekEnd,
      });
      res.json({ success: true, week, entries });
    } catch (error) {
      handleError(res, error, 'Failed to load current week mileage.');
    }
  },
  async submitWeek(req: Request, res: Response) {
    try {
      const id = await mileageService.submitWeek(req.body || {});
      res.status(201).json({ success: true, submissionId: id });
    } catch (error) {
      handleError(res, error, 'Failed to submit weekly mileage.');
    }
  },
  async pending(req: Request, res: Response) {
    try {
      res.json({ success: true, entries: await mileageService.list({ ...filtersFromRequest(req), status: 'pending_review' }) });
    } catch (error) {
      handleError(res, error, 'Failed to load pending mileage reviews.');
    }
  },
  async weeklyReport(req: Request, res: Response) {
    try {
      res.json({
        success: true,
        report: await mileageService.report(
          req.query.weekStart ? String(req.query.weekStart) : undefined,
          req.query.weekEnd ? String(req.query.weekEnd) : undefined,
        ),
      });
    } catch (error) {
      handleError(res, error, 'Failed to load mileage report.');
    }
  },
  async currentPayrollWeek(req: Request, res: Response) {
    try {
      res.json({
        success: true,
        week: mileageService.currentPayrollWeek(req.query.date ? String(req.query.date) : undefined),
      });
    } catch (error) {
      handleError(res, error, 'Failed to load current payroll week.');
    }
  },
  async weeklyBreakdown(req: Request, res: Response) {
    try {
      res.json({
        success: true,
        breakdown: await mileageService.weeklyBreakdown({
          weekStart: req.query.weekStart ? String(req.query.weekStart) : undefined,
          date: req.query.date ? String(req.query.date) : undefined,
          userId: req.query.userId ? Number(req.query.userId) : undefined,
          driver: req.query.driver ? String(req.query.driver) : undefined,
          status: req.query.status ? String(req.query.status) : undefined,
          flaggedOnly: String(req.query.flaggedOnly || '') === 'true',
          pendingOnly: String(req.query.pendingOnly || '') === 'true',
        }),
      });
    } catch (error) {
      handleError(res, error, 'Failed to load weekly mileage breakdown.');
    }
  },
  async settings(_req: Request, res: Response) {
    try {
      res.json({ success: true, settings: await mileageService.settings() });
    } catch (error) {
      handleError(res, error, 'Failed to load mileage settings.');
    }
  },
  async updateSettings(req: Request, res: Response) {
    try {
      res.json({ success: true, settings: await mileageService.updateSettings(req.body || {}) });
    } catch (error) {
      handleError(res, error, 'Failed to update mileage settings.');
    }
  },
};

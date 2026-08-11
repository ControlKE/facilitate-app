import { Router } from 'express';
import { mileageController } from '../controllers/mileageController.js';

const router = Router();

router.get('/reports/weekly', mileageController.weeklyReport);
router.get('/current-payroll-week', mileageController.currentPayrollWeek);
router.get('/weekly-breakdown', mileageController.weeklyBreakdown);
router.get('/settings', mileageController.settings);
router.put('/settings', mileageController.updateSettings);
router.get('/submissions/current-week', mileageController.currentWeek);
router.post('/submissions', mileageController.submitWeek);
router.get('/admin/pending', mileageController.pending);
router.get('/', mileageController.list);
router.post('/', mileageController.create);
router.get('/:id', mileageController.get);
router.put('/:id', mileageController.update);
router.delete('/:id', mileageController.remove);
router.post('/:id/submit', mileageController.submit);
router.post('/:id/review', mileageController.review);

export default router;

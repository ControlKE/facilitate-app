import path from 'node:path';
import { fileURLToPath } from 'node:url';
import dotenv from 'dotenv';
import express, { type Request, type Response } from 'express';
import cors from 'cors';
import {
  verifyDbConnection,
} from './db/mysql.js';
import mileageRoutes from './routes/mileage.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
dotenv.config({ path: path.resolve(__dirname, '.env') });
dotenv.config();

const app = express();
const port = Number.parseInt(process.env.PORT || '3001', 10);

app.use(cors());
app.use(express.json({ limit: '2mb' }));
app.use('/api/mileage', mileageRoutes);

app.get('/api/health', async (_req: Request, res: Response) => {
  try {
    await verifyDbConnection();
    res.json({
      ok: true,
      message: 'API is healthy',
      now: new Date().toISOString(),
    });
  } catch (error) {
    res.status(500).json({
      ok: false,
      message: 'Database connection failed',
      error: error instanceof Error ? error.message : 'unknown',
    });
  }
});

app.listen(port, async () => {
  try {
    await verifyDbConnection();
    // eslint-disable-next-line no-console
    console.log(`Server listening on http://localhost:${port}`);
  } catch (error) {
    // eslint-disable-next-line no-console
    console.error(
      `Server started but DB ping failed: ${
        error instanceof Error ? error.message : 'unknown'
      }`
    );
  }
});

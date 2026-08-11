import mysql from 'mysql2/promise';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import dotenv from 'dotenv';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

dotenv.config({ path: path.resolve(__dirname, '../.env') });
dotenv.config();

const toInt = (value: string | undefined, fallback: number): number => {
  const parsed = Number.parseInt(String(value ?? ''), 10);
  return Number.isFinite(parsed) ? parsed : fallback;
};

export const pool = mysql.createPool({
  host: process.env.MYSQL_HOST || '127.0.0.1',
  user: process.env.MYSQL_USER || 'root',
  password: process.env.MYSQL_PASSWORD || '',
  database: process.env.MYSQL_DATABASE || 'facilitate',
  port: toInt(process.env.MYSQL_PORT, 3306),
  connectionLimit: toInt(process.env.MYSQL_POOL_SIZE, 10),
  waitForConnections: true,
  namedPlaceholders: false,
});

export const verifyDbConnection = async (): Promise<void> => {
  const conn = await pool.getConnection();
  try {
    await conn.ping();
  } finally {
    conn.release();
  }
};

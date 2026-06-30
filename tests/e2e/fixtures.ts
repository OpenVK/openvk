import { test as base, expect } from '@playwright/test';
import { execSync } from 'child_process';

const dbHost = process.env.DB_HOST || 'mariadb-primary';
const dbUser = process.env.DB_USER || 'openvk';
const dbPass = process.env.DB_PASSWORD || 'openvk';
const dbName = process.env.DB_NAME || 'db';
const mysql = `mysql -h ${dbHost} -u ${dbUser} -p${dbPass} ${dbName} --ssl-mode=DISABLED --default-character-set=utf8mb4`;
const snapshotFile = '/tmp/openvk-test-db-snapshot.sql';

export const test = base.extend({
  page: async ({ page }, use) => {
    execSync(`${mysql} < ${snapshotFile}`, { stdio: 'inherit' });
    await use(page);
  },
});

export { expect };

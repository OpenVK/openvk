import { execSync } from 'child_process';

async function globalSetup(): Promise<void> {
  const dbHost = process.env.DB_HOST || 'mariadb-primary';
  const dbUser = process.env.DB_USER || 'openvk';
  const dbPass = process.env.DB_PASSWORD || 'openvk';
  const dbName = process.env.DB_NAME || 'db';
  const snapshotFile = '/tmp/openvk-test-db-snapshot.sql';

  // Safety check: refuse to run against non-Docker database hosts
  if (dbHost !== 'mariadb-primary') {
    console.error(
      `\n  SAFETY ABORT: DB_HOST="${dbHost}" doesn't look like a test container.\n` +
      `  These tests are designed for the Docker test stack only.\n` +
      `  To override, set OPENVK_TESTS_FORCE=1 in your environment.\n`
    );
    if (!process.env.OPENVK_TESTS_FORCE) {
      process.exit(1);
    }
    console.warn('  OPENVK_TESTS_FORCE=1 set, proceeding anyway.\n');
  }

  const mysqldump = `mysqldump -h ${dbHost} -u ${dbUser} ${dbName} --ssl-mode=DISABLED --default-character-set=utf8mb4`;

  // Take snapshot on first run (post-seed state).
  // Per-test restoration is handled by fixtures.ts.
  if (execSync(`test -f ${snapshotFile} && echo exists || echo notexists`).toString().trim() === 'notexists') {
    console.log('Taking DB snapshot...');
    execSync(`${mysqldump} > ${snapshotFile}`, { stdio: 'inherit', env: { ...process.env, MYSQL_PWD: dbPass } });
    console.log('Snapshot saved.');
  }
}

export default globalSetup;

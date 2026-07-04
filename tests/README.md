# OpenVK tests

## Screenshot testing

Run from this folder (`tests/`):

```bash
docker compose -f docker-compose.test.yml up --build --abort-on-container-exit --exit-code-from playwright
```

### Updating screenshots

When the UI changes, regenerate baselines:

```bash
docker compose -f docker-compose.test.yml run --rm playwright npx playwright test --update-snapshots
```

Baselines live in `tests/e2e/screenshots/*.spec.ts-snapshots/*.png`. Commit them.

---
paths:
  - config/website-text.php
---

# Config

## Config defaults must equal the live wording; register keys via WebsiteContent::sync()
The `default` of every key in config/website-text.php (and each list's `defaults` in config/website-lists.php) is what "Restore Original Text" restores and what a fresh install shows, so keep it equal to the wording currently live on production — never leave superseded/non-compliant copy there. Wording changes are made in the admin (DB `value` wins), not in migrations. To add a key: add it to config, then a two-line migration that calls `App\Support\WebsiteContent::sync()` (idempotent; never overwrites admin-edited values). Seed data in database/data/*.php mirrors production likewise.

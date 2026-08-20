# 🧪 Automated Tests

The plugin ships with unit/integration (PHPUnit) and browser acceptance (Behat) tests, executed
on every CI push against the full Moodle 4.5 → 5.2 matrix (PostgreSQL & MariaDB).

### PHPUnit — Unit & Integration Tests

| Test file | Cases |
|-----------|------:|
| `backup/restore_test.php` | 2 |
| `condition_test.php` | 24 |
| `frontend_test.php` | 5 |
| `privacy/provider_test.php` | 1 |
| **Total** | **32** |

```bash
vendor/bin/phpunit --testsuite availability_playerhud
```

**Overall line coverage** (PHPUnit + Xdebug): **94%**.

### Behat — Acceptance Tests

| Feature file | Scenarios |
|--------------|----------:|
| `allow_add.feature` | 2 |
| `restriction_level.feature` | 2 |
| `restriction_gamification.feature` | 2 |
| **Total** | **6** |

```bash
php admin/tool/behat/cli/init.php
vendor/bin/behat --tags=@availability_playerhud --profile=chrome
```

[Full test-by-test breakdown and coverage table →]({{ '/testing.html' | relative_url }})

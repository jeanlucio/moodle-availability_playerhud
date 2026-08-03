# 🧪 Automated Tests

The plugin ships with unit/integration (PHPUnit) and browser acceptance (Behat) tests, executed
on every CI push against the full matrix (Moodle 4.5 → 5.2, PostgreSQL & MariaDB).

### PHPUnit — Unit & Integration Tests

| Test file | Cases | What is covered |
|-----------|------:|----------------|
| `condition_test.php` | 18 | No-block fallback; level-based access at the exact, above, and below thresholds; item quantity checks across all four operators (≥, >, <, =); RPG class assignment (present, a different class, `classid = 0`, no RPG progress row); gamification enabled/disabled/no-player-record; unknown subtype falls through to `false`; corrupted/empty block `configdata` degrades gracefully; description strings for every subtype and operator; `save()` serialisation, including the int casts; `get_debug_string()`; a forged `levelval`/`itemqty` payload is cast to `int` by both `save()` and `get_description()` instead of being rendered as raw HTML (stored-XSS regression); a tampered block `configdata` containing a serialized object of an arbitrary class is never instantiated, proving `unserialize_object()` blocks it instead of a bare `unserialize()`; `get_description()` and `is_available()` never resolve item/class names or count inventory from a different course's PlayerHUD block instance (instance-isolation regression, three dedicated tests) |
| `frontend_test.php` | 5 | `allow_add()` with the block present and absent; `get_javascript_init_params()` with items/classes populated and with no block; `get_javascript_strings()` returns every expected string key |
| `privacy/provider_test.php` | 1 | `get_reason()` returns the `privacy:metadata` string key |
| **Total** | **24** | |

```bash
vendor/bin/phpunit --testsuite availability_playerhud
```

**Line coverage by class** (PHPUnit + Xdebug):

| Class | Line coverage |
|-------|:-------------:|
| `frontend` | 100% |
| `privacy\provider` | 100% |
| `condition` | 99% |
| **Overall** | **99%** |

The single uncovered line sits in `condition::is_available()`'s `level` branch: the `return
false;` fallback taken only if the `\block_playerhud\game` class does not exist. PlayerHUD Block
is a hard declared dependency (`$plugin->dependencies` in `version.php`), so that class is always
present in any real installation — the branch is defensive code for an already-enforced
precondition, not untested logic reachable in normal use.

### Behat — Acceptance Tests

| Feature file | Scenarios | What is covered |
|--------------|----------:|----------------|
| `allow_add.feature` | 2 | The PlayerHUD restriction option is offered when adding a restriction only if the block exists in the course, and is hidden otherwise |
| `restriction_level.feature` | 2 | A student below the required level sees the restriction notice ("You must reach..."); a student at or above the level accesses the activity without it |
| `restriction_gamification.feature` | 2 | A student with gamification disabled sees the restriction notice; a student with it enabled accesses the activity without it |
| **Total** | **6** | |

```bash
php admin/tool/behat/cli/init.php
vendor/bin/behat --tags=@availability_playerhud --profile=chrome
```

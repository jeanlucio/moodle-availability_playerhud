# 🧪 Testes Automatizados

O plugin inclui testes unitários/integração (PHPUnit) e de aceitação em navegador (Behat),
executados a cada push de CI contra a matriz completa (Moodle 4.5 → 5.2, PostgreSQL & MariaDB).

### PHPUnit — Testes Unitários e de Integração

| Arquivo de teste | Casos |
|-------------------|------:|
| `backup/restore_test.php` | 2 |
| `condition_test.php` | 24 |
| `frontend_test.php` | 5 |
| `privacy/provider_test.php` | 1 |
| **Total** | **32** |

```bash
vendor/bin/phpunit --testsuite availability_playerhud
```

**Cobertura de linhas total** (PHPUnit + Xdebug): **94%**.

### Behat — Testes de Aceitação

| Arquivo de feature | Cenários |
|--------------------|--------:|
| `allow_add.feature` | 2 |
| `restriction_level.feature` | 2 |
| `restriction_gamification.feature` | 2 |
| **Total** | **6** |

```bash
php admin/tool/behat/cli/init.php
vendor/bin/behat --tags=@availability_playerhud --profile=chrome
```

[Detalhamento completo dos testes e tabela de cobertura →]({{ '/testing-pt.html' | relative_url }})

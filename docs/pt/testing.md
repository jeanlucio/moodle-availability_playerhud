# 🧪 Testes Automatizados

O plugin inclui testes unitários/integração (PHPUnit) e de aceitação em navegador (Behat),
executados a cada push de CI contra a matriz completa (Moodle 4.5 → 5.2, PostgreSQL & MariaDB).

### PHPUnit — Testes Unitários e de Integração

| Arquivo de teste | Casos | O que é coberto |
|-------------------|------:|----------------|
| `condition_test.php` | 18 | Fallback sem bloco; acesso por nível no limite exato, acima e abaixo; checagem de quantidade de item nos quatro operadores (≥, >, <, =); atribuição de classe RPG (presente, uma classe diferente, `classid = 0`, sem linha de progresso RPG); gamificação ativa/desativada/sem registro de jogador; subtype desconhecido cai em `false`; `configdata` do bloco corrompido ou vazio degrada com segurança; strings de descrição para todos os subtypes e operadores; serialização de `save()`, incluindo os casts para int; `get_debug_string()`; um payload forjado de `levelval`/`itemqty` é convertido para `int` tanto por `save()` quanto por `get_description()` em vez de ser renderizado como HTML cru (regressão de XSS armazenado); um `configdata` de bloco adulterado contendo um objeto serializado de uma classe arbitrária nunca é instanciado, provando que `unserialize_object()` bloqueia isso em vez de um `unserialize()` puro; `get_description()` e `is_available()` nunca resolvem nomes de item/classe nem contam inventário de uma instância de bloco PlayerHUD de outro curso (regressão de isolamento de instância, três testes dedicados) |
| `frontend_test.php` | 5 | `allow_add()` com o bloco presente e ausente; `get_javascript_init_params()` com itens/classes populados e sem bloco; `get_javascript_strings()` retorna todas as chaves esperadas |
| `privacy/provider_test.php` | 1 | `get_reason()` retorna a chave de string `privacy:metadata` |
| **Total** | **24** | |

```bash
vendor/bin/phpunit --testsuite availability_playerhud
```

**Cobertura de linhas por classe** (PHPUnit + Xdebug):

| Classe | Cobertura de linhas |
|--------|:-------------------:|
| `frontend` | 100% |
| `privacy\provider` | 100% |
| `condition` | 99% |
| **Total** | **99%** |

A única linha não coberta está no ramo `level` de `condition::is_available()`: o fallback
`return false;` só é executado se a classe `\block_playerhud\game` não existir. O Bloco
PlayerHUD é uma dependência obrigatória declarada (`$plugin->dependencies` em `version.php`),
então essa classe sempre existe em qualquer instalação real — o ramo é código defensivo para
uma precondição já garantida, não lógica sem teste alcançável em uso normal.

### Behat — Testes de Aceitação

| Arquivo de feature | Cenários | O que é coberto |
|--------------------|--------:|----------------|
| `allow_add.feature` | 2 | A opção de restrição PlayerHUD é oferecida ao adicionar uma restrição somente se o bloco existe no curso, e fica oculta caso contrário |
| `restriction_level.feature` | 2 | Um estudante abaixo do nível necessário vê o aviso de restrição ("Você deve alcançar..."); um estudante no nível ou acima acessa a atividade sem restrição |
| `restriction_gamification.feature` | 2 | Um estudante com gamificação desativada vê o aviso de restrição; um estudante com ela ativada acessa a atividade sem restrição |
| **Total** | **6** | |

```bash
php admin/tool/behat/cli/init.php
vendor/bin/behat --tags=@availability_playerhud --profile=chrome
```

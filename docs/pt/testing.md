# 🧪 Testes Automatizados

O plugin inclui testes unitários/integração (PHPUnit) e de aceitação em navegador (Behat),
executados a cada push de CI contra a matriz completa (Moodle 4.5 → 5.2, PostgreSQL & MariaDB).

### PHPUnit — Testes Unitários e de Integração

| Arquivo de teste | Casos | O que é coberto |
|-------------------|------:|----------------|
| `backup/restore_test.php` | 2 | Um round-trip real de backup/restore de curso remapeia o `itemid` de uma restrição de posse de item, e o `classid` de uma restrição de classe RPG, para apontar pro item/classe do próprio curso restaurado — não o id do curso de origem (um id obsoleto faria o operador `<` sempre resolver a contagem de inventário como 0, desbloqueando todo mundo silenciosamente) |
| `condition_test.php` | 24 | Fallback sem bloco; acesso por nível no limite exato, acima e abaixo; checagem de quantidade de item nos quatro operadores (≥, >, <, =); atribuição de classe RPG (presente, uma classe diferente, `classid = 0`, sem linha de progresso RPG); gamificação ativa/desativada/sem registro de jogador; subtype desconhecido cai em `false`; `configdata` do bloco corrompido ou vazio degrada com segurança; strings de descrição para todos os subtypes e operadores; serialização de `save()`, incluindo os casts para int; `get_debug_string()`; um payload forjado de `levelval`/`itemqty` é convertido para `int` tanto por `save()` quanto por `get_description()` em vez de ser renderizado como HTML cru (regressão de XSS armazenado); um `configdata` de bloco adulterado contendo um objeto serializado de uma classe arbitrária nunca é instanciado, provando que `unserialize_object()` bloqueia isso em vez de um `unserialize()` puro; `get_description()` e `is_available()` nunca resolvem nomes de item/classe nem contam inventário de uma instância de bloco PlayerHUD de outro curso (regressão de isolamento de instância, três testes dedicados); uma checagem de quantidade de item reconhece saldo mantido só no motor novo de quantidade (baseado em `stack`) do bloco, não apenas na tabela legada de inventário |
| `frontend_test.php` | 5 | `allow_add()` com o bloco presente e ausente; `get_javascript_init_params()` com itens/classes populados e sem bloco; `get_javascript_strings()` retorna todas as chaves esperadas |
| `privacy/provider_test.php` | 1 | `get_reason()` retorna a chave de string `privacy:metadata` |
| **Total** | **32** | |

```bash
vendor/bin/phpunit --testsuite availability_playerhud
```

**Cobertura de linhas por classe** (PHPUnit + Xdebug):

| Classe | Cobertura de linhas |
|--------|:-------------------:|
| `frontend` | 100% |
| `privacy\provider` | 100% |
| `condition` | 91% |
| **Total** | **94%** |

As linhas não cobertas de `condition` estão no ramo `level` de `is_available()` (o fallback
`return false;` só executado se a classe `\block_playerhud\game` não existir — o Bloco
PlayerHUD é uma dependência obrigatória declarada, então essa classe sempre existe em qualquer
instalação real) e alguns ramos de borda na delegação de quantidade de item adicionada pro
motor novo baseado em `stack`. O arquivo de round-trip de backup/restore não é atribuído a uma
única classe de `classes/` — ele exercita o pipeline real de restauração de ponta a ponta em
vez de uma classe isolada, então não aparece na tabela por classe acima apesar de cobrir o
remapeamento em tempo de restauração de `condition`.

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

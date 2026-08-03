# 🔐 Segurança e Conformidade

* **Isolamento de instância:** as buscas de nome de item/classe em `get_description()` e a
  contagem de inventário em `is_available()` são sempre escopadas à instância do bloco PlayerHUD
  do próprio curso (`blockinstanceid`) — um payload `availabilityconditionsjson` forjado
  apontando para um item ou classe de outro curso não consegue vazar o nome, nem pode ser
  satisfeito por inventário concedido através do bloco de outro curso.
* **Renderização protegida contra XSS:** os valores `levelval` e `itemqty` vindos da árvore de
  disponibilidade são convertidos para `int` antes de serem persistidos (`save()`) e antes de
  serem renderizados (`get_description()`) — um payload forjado não consegue injetar HTML/JS na
  mensagem "Não disponível a menos que..." exibida a todo estudante, professor e admin que
  visualize o curso.
* **Desserialização segura:** o `configdata` armazenado pelo bloco é lido com
  `unserialize_object()`, restringindo o payload a `stdClass` — uma configuração adulterada
  nunca pode disparar instanciação arbitrária de objeto nem uma cadeia POP-gadget como um
  `unserialize()` puro permitiria.
* **Reavaliação no servidor:** `is_available()` é sempre recalculado a partir dos dados atuais
  do estudante no PlayerHUD a cada checagem; nada sobre o estado "liberado" é confiado a partir
  do cliente.
* **Sem chamadas a APIs externas:** a condição apenas lê o próprio banco de dados do Moodle.
* **Consciente de privacidade:** implementa o `null_provider` do Moodle — este plugin não
  armazena nenhum dado pessoal próprio; ele apenas avalia regras de acesso contra dados
  pertencentes ao Bloco PlayerHUD.

## Provedor de Privacidade

A Restrição de Acesso do PlayerHUD implementa o `null_provider` do Moodle — ele apenas **lê**
dados pertencentes e armazenados pelo Bloco PlayerHUD (nível, inventário, classe RPG, status de
gamificação) para calcular uma decisão de acesso; nunca persiste nenhum dado pessoal próprio.
Veja a documentação do próprio bloco para a cobertura completa de exportação/exclusão GDPR.

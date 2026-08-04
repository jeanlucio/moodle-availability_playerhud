# 🧩 Integração Opcional: Report Unlocker

As restrições do PlayerHUD normalmente são configuradas e editadas uma atividade por vez, pelo
próprio painel **Restringir acesso** de cada atividade (veja [Como Usar](#usage)). O plugin de
relatório separado **Report Unlocker** (`report_unlocker`, do mesmo autor, mas que **não** faz
parte da família PlayerGames) adiciona um painel de todo o curso que lista todas as restrições de
acesso — incluindo as condições de Nível, Item, Personagem e Gamificação do PlayerHUD — com
edição inline, remoção em lote e um assistente de IA para mudanças em linguagem natural. O Report
Unlocker lê e grava o mesmo JSON de `{course_modules}.availability` que este plugin produz,
sempre escopado à instância do bloco PlayerHUD do próprio curso, então nada muda na forma como a
restrição é armazenada. Sem o Report Unlocker instalado, as restrições continuam funcionando
exatamente como descrito em [Como Usar](#usage) — só o painel de todo o curso fica indisponível.

👉 <https://github.com/jeanlucio/moodle-report_unlocker>

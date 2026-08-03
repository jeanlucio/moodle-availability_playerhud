# ✨ Funcionalidades

* 🎯 **Restrição por Nível Mínimo:** libera conteúdo apenas quando o nível do estudante no
  PlayerHUD atinge um limite configurado.
* 🎒 **Restrição por Item Possuído:** libera conteúdo com base em quantas unidades de um item do
  PlayerHUD o estudante possui atualmente, com um operador de comparação (`>=`, `>`, `<`, `=`).
* 🧙 **Restrição por Classe de Personagem:** libera conteúdo apenas para estudantes que têm uma
  classe RPG específica atribuída no PlayerHUD.
* 🎮 **Restrição por Status de Gamificação:** libera conteúdo apenas para estudantes com a
  gamificação ativa (não pausada) no bloco PlayerHUD do curso.
* 🧠 **Integração Nativa com Restringir Acesso:** aparece como uma opção comum dentro do próprio
  painel "Restringir acesso" do Moodle — sem tela de configuração separada, sem capability extra
  para gerenciar.
* 🧩 **Disponibilidade Sensível ao Contexto:** a opção de restrição só é oferecida quando existe
  uma instância do bloco PlayerHUD no curso; caso contrário, permanece oculta.
* ⚡ **Avaliação em Tempo Real:** a condição é recalculada a cada carregamento de página
  diretamente a partir dos dados atuais do estudante no PlayerHUD — sem cache, sem estado
  "liberado" desatualizado.
* 📝 **Texto de Descrição Dinâmico:** a mensagem "Não disponível a menos que..." exibida ao
  estudante é construída a partir do requisito real (nível, nome e quantidade do item, nome da
  classe ou status de gamificação), não de um texto estático.

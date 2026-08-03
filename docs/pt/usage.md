# 📖 Como Usar

1. Adicione o **Bloco PlayerHUD** ao curso (veja [Instalação](#installation)) — a opção de
   restrição só aparece em cursos onde existe uma instância do bloco PlayerHUD.
2. Ative o **Modo de edição** no curso.
3. Edite uma atividade, recurso ou seção.
4. Abra a seção **Restringir acesso** e clique em **Adicionar restrição…**
5. Selecione **PlayerHUD**.
6. Escolha o tipo de restrição e seus parâmetros (veja a tabela de referência abaixo).

O estudante só ganha acesso quando a condição definida é atendida; a descrição exibida a ele é
gerada dinamicamente a partir da mesma condição.

## Referência dos Tipos de Restrição

| Tipo | Parâmetros | Acesso liberado quando |
|------|-----------|--------------------------|
| **Nível Mínimo** | Nível necessário | O nível atual do estudante no PlayerHUD é **maior ou igual** ao nível configurado. |
| **Possuir Item** | Item, operador (`ao menos`, `mais que`, `menos que`, `exatamente`), quantidade | A quantidade do item selecionado no inventário do estudante satisfaz o operador escolhido em relação à quantidade configurada. |
| **Personagem** | Classe RPG | O estudante tem a classe RPG selecionada atribuída no seu progresso do PlayerHUD. |
| **Gamificação Ativa** | — | O estudante não pausou/optou por sair da gamificação no bloco PlayerHUD do curso. |

## Observações

* A restrição é sempre reavaliada no servidor a cada carregamento de página — não há cache no
  cliente do estado "liberado" que possa ficar desatualizado.
* Se o bloco PlayerHUD for removido do curso após uma restrição ter sido configurada, a condição
  passa a avaliar com segurança como indisponível, em vez de gerar erro.
* Editar restrições uma atividade por vez é o fluxo padrão. Para um painel de todo o curso com
  edição inline e remoção em lote, veja a integração opcional com o
  [Report Unlocker](#unlocker).

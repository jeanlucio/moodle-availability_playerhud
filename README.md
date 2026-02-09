# Moodle PlayerHUD Availability Condition

![Moodle](https://img.shields.io/badge/Moodle-4.5%2B-orange?style=flat-square&logo=moodle&logoColor=white)
![License](https://img.shields.io/badge/License-GPLv3-blue?style=flat-square)
![Status](https://img.shields.io/badge/Status-Stable-green?style=flat-square)

[English](#english) | [Português](#português)

---

## English

The **PlayerHUD Availability Condition** is a plugin for Moodle that allows teachers to restrict access to activities, resources, or sections based on the student's progress in the **PlayerHUD Block**. It introduces gamification mechanics by unlocking content only when students reach a certain **Level** or collect specific **Items**.

### ✨ Features

* **Restrict by Level:** Unlock content only when a student reaches a specific level (e.g., "Level 5 or higher").
* **Restrict by Item:** Unlock content based on items in the student's inventory.
* **Advanced Logic:** Use operators to create complex conditions:
    * *More than (>)*
    * *Less than (<)*
    * *Exactly (=)*
    * *Greater or equal (>=)* (Default)

### 📦 Requirements

* **Moodle:** 4.5 or higher.
* **Dependency:** [Block PlayerHUD](https://github.com/jeanlucio/moodle-block_playerhud) (must be installed and enabled in the course).

### 🛠️ Installation

1.  Download the `.zip` file or clone this repository.
2.  Extract the content into your Moodle's `availability/condition/` directory.
3.  Rename the folder to `playerhud` (if it isn't already).
    * Path should be: `your-moodle/availability/condition/playerhud/`
4.  Go to **Site administration > Notifications** to complete the installation.

### 📖 Usage

1.  In a course, turn **Edit mode on**.
2.  Edit an activity or resource and go to the **Restrict access** section.
3.  Click **Add restriction...** and select **PlayerHUD**.
4.  Choose the restriction type:
    * **Minimum Level:** Set the required level number.
    * **Own Item:** Select an item from the dropdown, choose an operator (e.g., "more than"), and set the quantity.

---

## Português

A **Restrição de Acesso do PlayerHUD** é um plugin para Moodle que permite aos professores restringirem o acesso a atividades, recursos ou tópicos com base no progresso do aluno no **Bloco PlayerHUD**. Ele introduz mecânicas de gamificação ao liberar conteúdo apenas quando os alunos atingem um determinado **Nível** ou coletam **Itens** específicos.

### ✨ Funcionalidades

* **Restrição por Nível:** Libere conteúdo apenas quando o aluno atingir um nível específico (ex: "Nível 5 ou superior").
* **Restrição por Item:** Libere conteúdo com base nos itens do inventário do aluno.
* **Lógica Avançada:** Use operadores para criar condições complexas:
    * *Mais que (>)*
    * *Menos que (<)*
    * *Exatamente (=)*
    * *Maior ou igual (>=)* (Padrão)

### 📦 Requisitos

* **Moodle:** 4.5 ou superior.
* **Dependência:** [Block PlayerHUD](https://github.com/jeanlucio/moodle-block_playerhud) (deve estar instalado e adicionado ao curso).

### 🛠️ Instalação

1.  Baixe o arquivo `.zip` ou clone este repositório.
2.  Extraia o conteúdo no diretório `availability/condition/` do seu Moodle.
3.  Renomeie a pasta para `playerhud` (se ainda não estiver).
    * O caminho deve ficar: `seu-moodle/availability/condition/playerhud/`
4.  Acesse **Administração do site > Notificações** para concluir a instalação.

### 📖 Como Usar

1.  No curso, ative o **Modo de edição**.
2.  Edite uma atividade ou recurso e vá até a seção **Restringir acesso**.
3.  Clique em **Adicionar restrição...** e selecione **PlayerHUD**.
4.  Escolha o tipo de restrição:
    * **Nível Mínimo:** Defina o número do nível necessário.
    * **Possuir Item:** Selecione um item da lista, escolha um operador (ex: "mais que") e defina a quantidade.

---

## 📄 License / Licença

This project is licensed under the **GNU General Public License v3 (GPLv3)**.

**Copyright:** 2026 Jean Lúcio
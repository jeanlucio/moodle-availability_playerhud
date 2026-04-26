# Moodle PlayerHUD Restrição de Acesso

[![Moodle Plugin CI](https://github.com/jeanlucio/moodle-availability_playerhud/actions/workflows/ci.yml/badge.svg)](https://github.com/jeanlucio/moodle-availability_playerhud/actions/workflows/ci.yml)
![Moodle](https://img.shields.io/badge/Moodle-4.5%2B-orange?style=flat-square&logo=moodle&logoColor=white)
![License](https://img.shields.io/badge/License-GPLv3-blue?style=flat-square)
![Status](https://img.shields.io/badge/Status-Stable-green?style=flat-square)
[![PlayerHUD Ecosystem](https://img.shields.io/badge/PlayerHUD-Ecosystem-6f42c1?style=flat-square&logo=gamepad&logoColor=white)](https://github.com/jeanlucio/moodle-block_playerhud)
![Role](https://img.shields.io/badge/Role-Access_Control-d63384?style=flat-square)
![GitHub release](https://img.shields.io/github/v/release/jeanlucio/moodle-availability_playerhud?style=flat-square)


[English](#english) | [Português](#português)


## English

The **PlayerHUD Availability Condition** is an extension plugin for Moodle that allows teachers to restrict access to activities, resources, or course sections based on a student's progress in the PlayerHUD Block.

It enables gamified progression rules by unlocking content only when students reach specific **Levels** or possess certain **Items**.

---

### ✨ Features

* 🎯 Restrict access by minimum Level
* 🎒 Restrict access based on collected Items
* 🔢 Flexible comparison operators:
  * Greater than (>)
  * Less than (<)
  * Exactly (=)
  * Greater or equal (>=)
* 🧠 Fully integrated with Moodle’s native Restrict Access system
* ⚡ Real-time validation based on PlayerHUD data

---

### 🔗 Part of the PlayerHUD Ecosystem

This plugin works together with:

* **PlayerHUD Block (Required)**  
  👉 https://github.com/jeanlucio/moodle-block_playerhud

Optional extension:

* **PlayerHUD Filter**  
  👉 https://github.com/jeanlucio/moodle-filter_playerhud

---

### 📦 Requirements

* **Moodle:** 4.5 or higher
* **Required Dependency:** PlayerHUD Block  
  https://github.com/jeanlucio/moodle-block_playerhud
* **PHP:** Compatible with your Moodle version

---

### 🛠️ Installation

1. Ensure the **PlayerHUD Block** is installed first.  
   👉 https://github.com/jeanlucio/moodle-block_playerhud  
   This availability condition depends on the block and will not function without it.

2. Download the `.zip` file or clone this repository.
3. Extract the folder into your Moodle `availability/condition/` directory.
4. Rename the folder to `playerhud` (if necessary).  
   Final path:  
   `your-moodle/availability/condition/playerhud/`
5. Visit **Site administration > Notifications** to complete installation.

---

### 📖 Usage

1. Turn **Edit mode on** in a course.
2. Edit an activity, resource, or section.
3. Open the **Restrict access** section.
4. Click **Add restriction…**
5. Select **PlayerHUD**.
6. Choose the restriction type:
   * **Minimum Level** – Define the required level.
   * **Own Item** – Select an item, choose an operator, and define the required quantity.

Students will only gain access when the defined conditions are met.

---

### 🔐 Security & Compliance

* Capability-based validation
* Server-side condition evaluation
* Fully integrated with Moodle core access control
* No external API calls
* Compatible with Moodle privacy API standards

---

## 📄 License

This project is licensed under the **GNU General Public License v3 (GPLv3)**.

**Copyright:** 2026 Jean Lúcio

---

## Português

A **Restrição de Acesso do PlayerHUD** é um plugin de extensão para Moodle que permite ao professor restringir o acesso a atividades, recursos ou seções do curso com base no progresso do aluno no Bloco PlayerHUD.

Ele possibilita regras de progressão gamificada, liberando conteúdos apenas quando o estudante atinge determinados **Níveis** ou possui **Itens** específicos.

---

### ✨ Funcionalidades

* 🎯 Restrição por Nível mínimo
* 🎒 Restrição baseada em Itens coletados
* 🔢 Operadores de comparação flexíveis:
  * Maior que (>)
  * Menor que (<)
  * Exatamente (=)
  * Maior ou igual (>=)
* 🧠 Integração total com o sistema nativo de “Restringir acesso” do Moodle
* ⚡ Validação em tempo real com base nos dados do PlayerHUD

---

### 🔗 Parte do Ecossistema PlayerHUD

Este plugin funciona em conjunto com:

* **Bloco PlayerHUD (Obrigatório)**  
  👉 https://github.com/jeanlucio/moodle-block_playerhud

Extensão opcional:

* **Filtro PlayerHUD**  
  👉 https://github.com/jeanlucio/moodle-filter_playerhud

---

### 📦 Requisitos

* **Moodle:** 4.5 ou superior
* **Dependência Obrigatória:** Bloco PlayerHUD  
  https://github.com/jeanlucio/moodle-block_playerhud
* **PHP:** Compatível com a versão do Moodle

---

### 🛠️ Instalação

1. Certifique-se de que o **Bloco PlayerHUD** esteja instalado primeiro.  
   👉 https://github.com/jeanlucio/moodle-block_playerhud  
   Esta restrição depende do bloco e não funcionará sem ele.

2. Baixe o arquivo `.zip` ou clone o repositório.
3. Extraia a pasta para o diretório `availability/condition/` do seu Moodle.
4. Renomeie para `playerhud` (se necessário).  
   Caminho final:  
   `seu-moodle/availability/condition/playerhud/`
5. Acesse **Administração do site > Notificações** para concluir a instalação.

---

### 📖 Como Usar

1. Ative o **Modo de edição** no curso.
2. Edite uma atividade, recurso ou seção.
3. Vá até a seção **Restringir acesso**.
4. Clique em **Adicionar restrição…**
5. Selecione **PlayerHUD**.
6. Escolha o tipo de restrição:
   * **Nível Mínimo** – Defina o nível necessário.
   * **Possuir Item** – Selecione o item, escolha o operador e defina a quantidade.

O acesso será liberado automaticamente quando as condições forem atendidas.

---

### 🔐 Segurança e Conformidade

* Validação baseada em capabilities
* Avaliação das condições no servidor
* Integração total com o controle de acesso do Moodle
* Não utiliza APIs externas
* Compatível com os padrões de privacidade do Moodle

---

## 📄 Licença

Este projeto é licenciado sob a **GNU General Public License v3 (GPLv3)**.

**Copyright:** 2026 Jean Lúcio

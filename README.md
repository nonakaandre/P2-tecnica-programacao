# Refatoração de Legado: Sistema de Relatórios de Vendas

Este repositório contém o processo de refatoração de um sistema legado de relatórios de vendas em PHP. O objetivo é eliminar acoplamentos, melhorar a manutenibilidade e introduzir novos requisitos de negócio guiados por três padrões de projeto (**GoF**): **Template Method**, **Builder** e **Command**.

---

## 📌 O Cenário

O sistema opera de forma crítica há anos com três grandes gargalos de sustentabilidade técnica:

### 1. Duplicação de Fluxo (`Template Method`)

Cada formato de relatório (CSV, HTML, JSON) implementa manualmente a mesma sequência: **filtrar $\rightarrow$ ordenar $\rightarrow$ formatar $\rightarrow$ salvar**. A nova demanda de negócio exige suporte a **XML** e **Markdown**.

* **Perguntas norteadoras:**
* O que é fixo e o que varia nesse fluxo?
* Quais passos compõem o *esqueleto* do algoritmo e quais funcionam como *ganchos* (hooks) de extensão?



### 2. Parâmetros Posicionais Excessivos (`Builder`)

A função principal recebe **12 parâmetros posicionais**. A presença constante de argumentos `null` esconde inconsistências e dificulta a manutenção da chamada.

* **Perguntas norteadoras:**
* Qual objeto modela com precisão "um pedido de relatório"?
* Como estruturar a construção passo a passo desse objeto, assegurando validação de consistência antes da instanciação final?



### 3. Ações sem Histórico ou Rastreabilidade (`Command`)

O processamento é centralizado em um bloco `switch` monolítico. O negócio demanda:

* Registro e rastreabilidade de todas as ações executadas.
* Capacidade de desfazimento (*undo*), como restaurar um relatório após `arquivar_csv`.
* Execução em fila e em lote (*batch processing*).
* **Perguntas norteadoras:**
* Quais dados e métodos uma ação precisa encapsular para permitir agendamento, execução sob demanda e rollback?
* Quem orquestra o ciclo de vida e a decisão de despacho das ações?



---

## 🛠️ Ordem Estratégica de Refatoração

Para garantir refatoração sem regressão funcional, seguimos a ordem abaixo:

1. **Rede de Segurança (Characterization Testing / Golden Master)**
* Executar o comportamento base antes de qualquer alteração de código:
```bash
php legado.php gerar_csv
php legado.php gerar_html_e_enviar
php legado.php arquivar_csv

```


* Armazenar os arquivos de saída gerados em `out/` e os registros de terminal (`stdout`) como baseline comparativo de paridade estrita.


2. **Template Method (Menor Risco)**
* Isolar a estrutura algorítmica comum internamente na geração de arquivos, mantendo a interface pública de chamada inalterada.


3. **Builder (Risco Médio)**
* Substituir a lista posicional de parâmetros por um objeto de transferência/configuração validado via construtor fluido.


4. **Command (Integração Arquitetural)**
* Desacoplar os invocadores dos receptores. O comando de geração recebe o payload validado pelo Builder e aciona o motor padronizado pelo Template Method.



---

## ✅ Critérios de Aceitação

* [ ] **Paridade Funcional:** As rotinas originais (`gerar_csv`, `gerar_html_e_enviar`, `arquivar_csv`) produzem saídas idênticas às capturadas no Golden Master.
* [ ] **Open/Closed Principle:** A adição do formato Markdown é feita estritamente criando uma nova classe derivada, sem modificar estruturas condicionais existentes.
* [ ] **Assinaturas Limpas:** Nenhuma chamada de método ou função ultrapassa 2 a 3 argumentos posicionais.
* [ ] **Auditoria e Rollback:** Histórico de ações registrado em memória/log, com suporte funcional ao desfazimento de `arquivar_csv`.

---

## ⚖️ Trade-offs Arquiteturais

* **Template Method vs. Strategy:**
* O **Template Method** baseia-se em herança e impõe um esqueleto invariável. Adequado para este cenário, onde os passos lógicos são fixos.
* O **Strategy** dependeria de composição, sendo mais indicado se cada etapa do pipeline (como filtro ou ordenação) precisasse variar de forma ortogonal e independente.


* **Builder vs. Recursos do PHP 8+:**
* *Named Arguments* e propriedades *readonly* eliminam a fragilidade posicional com menos código boilerplate.
* O padrão **Builder** justifica-se aqui pela necessidade de aplicar validações de compatibilidade entre múltiplos parâmetros durante o ciclo de montagem antes de entregar a instância final.


* **Custo Operacional do Command:**
* Introduzir classes dedicadas para cada ação adiciona camadas ao projeto. O ganho dessa complexidade acidental se justifica pelos requisitos de histórico, reversão e enfileiramento.



---

## 📚 Referências de Estudo

* **Design Patterns: Elements of Reusable Object-Oriented Software** (*Gamma, Helm, Johnson, Vlissides, 1994*):
* Consultar a seção *Applicability* de cada padrão para validar aderência de caso de uso.


* **Documentação Oficial do PHP (`php.net`):**
* [Class Abstraction](https://www.php.net/manual/pt_BR/language.oop5.abstract.php) & [Object Interfaces](https://www.php.net/manual/pt_BR/language.oop5.interfaces.php) — Fundamentos para Template Method e Command.
* [Named Arguments](https://www.google.com/search?q=https://www.php.net/manual/pt_BR/functions.arguments.php%23functions.named-arguments) — Avaliação de alternativas nativas ao Builder.

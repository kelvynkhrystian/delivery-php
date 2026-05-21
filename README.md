
## 🚀 O Projeto
https://joaodedeus.acaicity.com.br/
---


O aplicativo é uma interface simples em PHP que consome a API do Google Maps para delivery.
### 🛠️ Tecnologias Utilizadas

* **PHP** (Estruturado / Monolítico)
* **HTML5 & CSS3**
* **JavaScript**
* **Google Maps JavaScript API & Geocoding API**

# 🗺️ Google Maps Integration App

> **Nota de Autenticidade e Transparência:** Este foi o meu primeiro aplicativo prático utilizando PHP integrado com a API do Google Maps. O objetivo principal deste projeto foi **fazer funcionar o mais rápido possível**. Por conta disso, ele possui uma arquitetura centralizada (muito código em arquivos únicos) e pontos de segurança que deixam a desejar (como chaves de API expostas no front-end). Estou ciente de cada um desses débitos técnicos e o projeto será totalmente refatorado em breve.

Este repositório serve como um registro do meu ponto de partida e da minha evolução na programação backend.

---


## ⚠️ Débitos Técnicos Conscientes (O que precisa melhorar?)

Como este foi um projeto de validação rápida e aprendizado inicial, existem falhas estruturais severas que listei para guiar minha próxima etapa de desenvolvimento:

1.  **Segurança (API Keys Expostas):** A chave de API do Google Maps está injetada diretamente no código client-side sem restrições de HTTP referrers robustas ou proxy reverso no backend.
2.  **Arquitetura Monolítica/Acoplada:** Regras de negócio, renderização HTML e chamadas de script estão misturadas no mesmo arquivo, violando os princípios de separação de conceitos (SoC) e padrões como MVC ou Service/Controller.
3.  **Falta de Sanitização de Dados:** Os inputs de endereço/coordenadas não passam por uma camada rigorosa de validação e tratamento antes de serem enviados para as APIs externas.
4.  **Ausência de Variáveis de Ambiente:** Configurações críticas e credenciais estão hardcoded em vez de utilizarem um arquivo `.env`.

---

## 🔄 Plano de Refatoração (Próximos Passos)

Para transformar este protótipo em uma aplicação segura e escalável, os seguintes passos serão implementados na versão v2:

* [ ] **Isolamento de Credenciais:** Migrar todas as chaves e credenciais para variáveis de ambiente utilizando `vlucas/phpdotenv`.
* [ ] **Camada de Proxy no Backend:** Criar endpoints PHP que façam as requisições de Geocoding para que a API Key nunca fique visível no navegador do usuário.
* [ ] **Modularização do Código:** Separar as responsabilidades aplicando a arquitetura correta (Views separadas da lógica de rotas e consumo de APIs).
* [ ] **Restrição na Google Cloud Console:** Configurar restrições estritas de IP e domínio para a chave de API ativa.

---

## 💻 Como Rodar o Projeto (Status Atual)

Se mesmo conhecendo os pontos acima você quiser rodar o projeto localmente para testar ou veja acesse o link:
https://joaodedeus.acaicity.com.br/

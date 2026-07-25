# Notre Guard

Sistema de gestão para a equipe de segurança patrimonial do Colégio Notre Dame Campinas:
ronda eletrônica, RDO/ocorrências, checklists e indicadores. Desktop (supervisão) + mobile
(campo).

**Estado:** Fases 1 e 2 implementadas. O painel administrativo roda com os cadastros base e as
telas operacionais, e a PWA do vigilante executa o ciclo completo de turno — inclusive sem
rede. O RDO e os dashboards (Fase 3) ainda não existem.

## Stack

PHP 8.4 · Laravel 13 · Filament 4 · MySQL 8 · Sanctum · dompdf · simple-qrcode.
PWA de campo: Blade + Alpine.js + Dexie (IndexedDB) + service worker.

## Subir o ambiente

```bash
composer install
```

Depois copie `.env.example` para `.env`, rode `php artisan key:generate`, garanta que o MySQL
está no ar (ver [docs/02](docs/02-ambiente-desenvolvimento.md)) e então:

```bash
php artisan migrate --seed
```

```bash
npm install && npm run build
```

```bash
php artisan serve --port=8010
```

O painel fica em <http://127.0.0.1:8010/admin> e a PWA do vigilante em
<http://127.0.0.1:8010/campo>.

> Para testar a PWA em celular é preciso **HTTPS** — câmera, geolocalização e service worker
> não funcionam em HTTP. Use `herd secure` ou um túnel.

### Acessos de desenvolvimento

| Perfil | E-mail | Senha |
|---|---|---|
| Administrador | `admin@notreguard.local` | `admin1234` |
| Supervisão | `supervisao@notreguard.local` | `super1234` |
| Vigilante (sem acesso ao painel) | `vigilante@notreguard.local` | `vigilante1234` |

O seed cria a "Unidade Sede" com 2 postos, 6 pontos de controle, 1 roteiro com duas janelas
noturnas, um checklist de perímetro e a taxonomia inicial de ocorrências.

## Testes

```bash
php artisan test
```

## O que já funciona

**Painel (supervisão)**

- Cadastro de unidades, com postos e pontos de controle gerenciados na própria tela da unidade
- Roteiros de ronda com ordem dos pontos e janelas de execução por dia da semana
- Checklists com itens reordenáveis, vinculáveis a um ponto de controle
- Vigilantes, com registro profissional e validade da reciclagem
- Taxonomia de tipos de ocorrência, hierárquica
- Folhas de QR Code prontas para impressão, por ponto, por posto ou para a unidade inteira
  (`/qr/unidade/{unidade}/pontos`)
- Consulta de turnos, rondas (com aderência e desvios de cada leitura) e ocorrências

**PWA de campo (vigilante)**

- Login por matrícula no aparelho corporativo
- Assunção de posto, ronda com leitura de QR, checklist por ponto, ponto pulado com
  justificativa, ocorrência com foto, encerramento de ronda e de turno
- **Funciona offline**: os registros ficam no aparelho e sobem sozinhos quando há rede
- Desvios (fora do raio, sem GPS, fora da janela, fora de ordem, relógio divergente) são
  marcados pelo servidor — o app nunca recusa um registro

Turnos, rondas e leituras são imutáveis: nascem em campo e o painel só consulta.

## Documentação

| Documento | Conteúdo |
|---|---|
| [01 — Plano de implantação](docs/01-plano-de-implantacao.md) | Estudo de mercado, escopo por fases, arquitetura, roadmap, riscos |
| [02 — Ambiente de desenvolvimento](docs/02-ambiente-desenvolvimento.md) | Herd, MySQL, `.env`, teste em dispositivo real |
| [03 — Modelo de dados](docs/03-modelo-de-dados.md) | Tabelas, convenções de sincronização, tokens de QR |
| [04 — Decisões técnicas](docs/04-decisoes-tecnicas.md) | Decisões tomadas na implementação, com o motivo |
| [05 — API de sincronização](docs/05-api-de-sincronizacao.md) | Endpoints, tipos de evento, idempotência, desvios |
| [06 — PWA de campo](docs/06-pwa-de-campo.md) | Telas, offline, decisões de interface, limitações |

## Relação com o Portal de Segurança Digital

Aplicação **independente** (código e banco próprios). Compartilha com o Portal a autenticação
Google Workspace (ainda a implementar) e a linguagem visual — mas não componentes de código,
porque o Portal está em Filament 3 e este projeto em Filament 4.

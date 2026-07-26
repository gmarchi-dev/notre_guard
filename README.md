# Notre Guard

Sistema de gestão para a equipe de segurança patrimonial:
ronda eletrônica, RDO/ocorrências, checklists e indicadores. Desktop (supervisão) + mobile
(campo).

**Estado:** Fases 1, 2 e 3 completas. O painel roda com os cadastros base, as telas
operacionais, o RDO com PDF selado, o painel de indicadores e as notificações de ocorrência
grave; a PWA do vigilante executa o ciclo completo de turno, inclusive sem rede. O próximo
passo é o rollout (Fase 4), que depende do levantamento com a equipe.

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

As notificações são enfileiradas, então é preciso um worker rodando em paralelo:

```bash
php artisan queue:work
```

Em produção, o expurgo de dados vencidos depende do agendador. Para conferir a política sem
apagar nada:

```bash
php artisan notre-guard:purge-data --dry-run
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

- **Painel operacional** com aderência de ronda, ocorrências, taxa de desvio e turnos abertos,
  filtrável por período e unidade, com gráficos de aderência diária, recorrência por hora e
  pontos com mais não conformidade
- Cadastro de unidades, com postos e pontos de controle gerenciados na própria tela da unidade
- Roteiros de ronda com ordem dos pontos e janelas de execução por dia da semana
- Checklists com itens reordenáveis, vinculáveis a um ponto de controle
- Vigilantes, com registro profissional e validade da reciclagem
- Taxonomia de tipos de ocorrência, hierárquica
- Folhas de QR Code prontas para impressão, por ponto, por posto ou para a unidade inteira
  (`/qr/unidade/{unidade}/pontos`)
- Consulta de turnos, rondas (com aderência e desvios de cada leitura) e ocorrências
- **RDO** por unidade e data, com fechamento selado por SHA-256, PDF para envio e detecção de
  registros que chegaram depois do fechamento
- **Alertas de segurança do vigilante**: botão de pânico (entrega imediata, fora da fila) e
  alerta de inatividade em ronda, com tela de plantão e ciclo de atendimento
- **Notificações** de ocorrência grave por e-mail e sino no painel, respeitando a unidade do
  destinatário
- **Retenção de dados (LGPD)**: expurgo diário automatizado com prazos configuráveis e
  histórico das execuções para prestação de contas
- **Login Google Workspace** implementado e **desligado** (`GOOGLE_AUTH_ENABLED=false`);
  ativação é só credencial e flag — ver [docs/11](docs/11-autenticacao-google.md)
- Gestão de usuários e perfis (só administrador), com criação do login direto no cadastro
  de vigilante
- **Escopo por unidade**: o gestor de unidade enxerga apenas a própria unidade

**PWA de campo (vigilante)**

- Login por matrícula no aparelho corporativo
- Assunção de posto, ronda com leitura de QR, checklist por ponto, ponto pulado com
  justificativa, ocorrência com foto, encerramento de ronda e de turno
- **Funciona offline**: os registros ficam no aparelho e sobem sozinhos quando há rede
- Tela de fila mostrando o que está pendente e o que o servidor recusou, com o motivo
- **Botão de emergência** sempre visível, com confirmação em duas etapas e contingência offline
- Desvios (fora do raio, sem GPS, fora da janela, fora de ordem, relógio divergente) são
  marcados pelo servidor — o app nunca recusa um registro

Turnos, rondas e leituras são imutáveis: nascem em campo e o painel só consulta.

**Portaria** (`/portaria`, login por matrícula)

- Quadro de chaves com entrega e devolução, cadastro de solicitante na hora da retirada
- Livro de retiradas com prazo por retirada e aviso diário de chaves não devolvidas
- Painel separado do administrativo: o vigilante entra aqui, e não na operação das unidades

## Documentação

| Documento | Conteúdo |
|---|---|
| [01 — Plano de implantação](docs/01-plano-de-implantacao.md) | Estudo de mercado, escopo por fases, arquitetura, roadmap, riscos |
| [02 — Ambiente de desenvolvimento](docs/02-ambiente-desenvolvimento.md) | Herd, MySQL, `.env`, teste em dispositivo real |
| [03 — Modelo de dados](docs/03-modelo-de-dados.md) | Tabelas, convenções de sincronização, tokens de QR |
| [04 — Decisões técnicas](docs/04-decisoes-tecnicas.md) | Decisões tomadas na implementação, com o motivo |
| [05 — API de sincronização](docs/05-api-de-sincronizacao.md) | Endpoints, tipos de evento, idempotência, desvios |
| [06 — PWA de campo](docs/06-pwa-de-campo.md) | Telas, offline, decisões de interface, limitações |
| [07 — RDO](docs/07-rdo.md) | Ciclo do relatório diário, selo de integridade, PDF |
| [08 — Painel operacional](docs/08-dashboard.md) | Indicadores, gráficos e como ler os números |
| [09 — Notificações](docs/09-notificacoes.md) | Quando dispara, quem recebe, canais e fila |
| [10 — LGPD e retenção](docs/10-lgpd-e-retencao.md) | Prazos, regras do expurgo e prestação de contas |
| [11 — Autenticação Google](docs/11-autenticacao-google.md) | Regras de aceite e roteiro de ativação |
| [12 — Segurança do vigilante](docs/12-seguranca-do-vigilante.md) | Botão de pânico, inatividade e atendimento |
| [13 — Controle de chaves](docs/13-controle-de-chaves.md) | Livro da portaria, prazos e painel próprio |

## Relação com o Portal de Segurança Digital

Aplicação **independente** (código e banco próprios). Compartilha com o Portal o modelo de
autenticação Google Workspace faseada e a linguagem visual — mas não componentes de código,
porque o Portal está em Filament 3 e este projeto em Filament 4.

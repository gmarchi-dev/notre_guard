# Notre Guard — Sistema de Gestão de Segurança Patrimonial
## Estudo de mercado e plano de implantação

**Data:** 25/07/2026
**Status:** proposta para aprovação
**Projeto:** `notre_guard` (aplicação independente, irmã do Portal de Segurança Digital)
**Escopo:** aplicação web responsiva (desktop + mobile) para gestão da equipe de segurança patrimonial, com foco em ronda eletrônica, RDO/ocorrências e checklists.

---

## 1. Sumário executivo

O mercado brasileiro de software para segurança patrimonial está maduro e comoditizado na camada operacional (ronda com QR Code/NFC, registro de ocorrência com foto, dashboard de conformidade). Os produtos disponíveis — Performance Lab, Mobitraxx, Ronda Fácil, SSRonda/Veolink, MobRonda, Marcozero, entre outros — convergem para praticamente o mesmo conjunto de funcionalidades, com preços a partir de ~R$ 71/licença/mês. Isso significa duas coisas:

1. **O escopo funcional é conhecido e de baixo risco.** Não há incerteza sobre "o que construir": o padrão de mercado está estabelecido e documentado neste estudo.
2. **A diferenciação não está na ronda em si**, e sim na integração com o ecossistema interno (colaboradores, unidades, aceites, indicadores institucionais) e no controle total sobre dados e evolução — exatamente o que uma solução SaaS de nicho não entrega.

**Recomendação:** construir internamente, em Laravel 13 + Filament (mesmo stack do Portal de Segurança Digital já em operação), com **PWA offline-first** para o uso em campo — sem app nativo na primeira versão. O MVP operacional (assunção de posto + ronda + ocorrência/RDO + relatórios) é entregável em **10 a 12 semanas** com um desenvolvedor dedicado.

---

## 2. Estudo de mercado

### 2.1 Referência principal — Performance Lab (segurança patrimonial)

A solução organiza o ciclo de trabalho do vigilante em quatro módulos sequenciais:

| Módulo | O que faz |
|---|---|
| Assunção de posto | Registro de presença no início do turno via leitura de QR Code no posto |
| Execução de rondas | Roteiros de inspeção padronizados com pontos de controle e checklists de conformidade |
| Registro de ocorrências | Formulários ilustrados, com comprovação fotográfica, classificando o evento como *prevenção* ou *perda* |
| Consolidação operacional | Fechamento de serviço com geração de histórico rastreável |

Recursos transversais: sincronização em tempo real, alertas instantâneos à supervisão, rastreamento de equipamentos (armamento, rádio, fardamento), dashboards personalizados e monitoramento de SLA contratual.

**Leitura estratégica:** o desenho por *ciclo de turno* (assume → executa → registra → fecha) é o ponto mais forte da referência e deve ser adotado. Ele transforma registros soltos em um dossiê auditável por turno, que é o artefato que o gestor e o cliente/contratante realmente consomem.

### 2.2 Benchmark — mercado nacional

| Produto | Diferenciais observados | Preço público |
|---|---|---|
| **Mobitraxx** | Rondas, presenças, *permanências* (tempo mínimo em local), ocorrências com foto/vídeo, botão de pânico, checklists, alerta de inatividade, ponto remoto, +40 relatórios | R$ 71/mês (1 licença); R$ 560/ano |
| **Ronda Fácil** | QR Code + NFC + GPS, relatórios automáticos, trial de 14 dias | não divulgado |
| **SSRonda (Veolink)** | Tags NFC ou QR Code, coordenadas GPS validadas contra o ponto esperado | não divulgado |
| **Marcozero Data** | Arquitetura *offline-first* explícita (registro completo no aparelho, sync ao recuperar rede), exportação CSV/GeoJSON | não divulgado |
| **MobRonda** | QR Code e tags NFC, 30 dias grátis | não divulgado |
| **Controle de Rondas / Trinity Guard** | GPS + QR, preço por nº de vigilantes e locais | por vigilante/local |
| **Rondel** | Bastão de ronda (hardware) — geração anterior | hardware |

### 2.3 Benchmark — mercado internacional

TrackTik, Silvertrac e Trackforce Valiant (as duas primeiras hoje sob o mesmo grupo) definem o teto de maturidade da categoria: escala de turnos, GPS em tempo real, *lone worker safety*, dispatch, portal do cliente, analytics de performance e faturamento por contrato. Silvertrac pratica US$ 249/mês flat; TrackTik e Trackforce trabalham com preço enterprise sob consulta. A ênfase declarada de todos é **simplicidade radical na interface do agente de campo** — o app do vigilante precisa ser operável com uma mão, com luva, no escuro e sem treinamento longo.

### 2.4 Padrões consolidados (o que é obrigatório ter)

- Ponto de controle identificado por **QR Code** (barato, imprimível, reposicionável) e/ou **tag NFC** (resistente, à prova de foto do crachá alheio).
- **Validação cruzada por GPS**: a leitura só é aceita se a coordenada estiver dentro do raio esperado do checkpoint.
- **Operação offline obrigatória** — subsolo, casa de máquinas, perímetro. Sem isso o produto não sobrevive ao piloto.
- **Evidência fotográfica** com timestamp e geotag embutidos.
- **Botão de pânico** e **alerta de inatividade** (variação do *homem-morto*).
- Ocorrência com **numeração sequencial padronizada** (formato `RO 001/2026`) e classificação por assunto/severidade.
- Relatórios em PDF prontos para envio ao contratante e dashboard de conformidade (rondas previstas × realizadas).

### 2.5 Lacunas exploráveis

1. **Integração com o cadastro institucional** (colaboradores, unidades, setores) — SaaS de nicho vive em silo.
2. **Trilha de auditoria forte**: encadeamento por hash dos registros do turno, tornando o RDO defensável em processo administrativo ou judicial.
3. **RDO como documento vivo** — hoje o mercado entrega o relatório como exportação; tratá-lo como entidade com workflow (rascunho → fechado → ciente da supervisão) é diferencial real.
4. **Análise de recorrência**: mapa de calor de ocorrências por local/horário/tipo, que orienta redesenho de rota — quase ninguém faz bem.

---

## 3. Decisão: construir × comprar

| Critério | Comprar SaaS | Construir (recomendado) |
|---|---|---|
| Tempo até operação | 1–2 semanas | 10–12 semanas (MVP) |
| Custo ano 1 | ~R$ 700–900/licença/ano × nº de vigilantes | custo de desenvolvimento interno, sem recorrência por usuário |
| Custo ano 3+ | recorrência crescente com a equipe | manutenção |
| Integração com base institucional | limitada / via planilha | nativa |
| Propriedade dos dados e do RDO | do fornecedor | interna |
| Aderência a processo próprio | o processo se adapta ao software | o software se adapta ao processo |
| Risco | baixo | médio, mitigado por stack já dominado |

O fator decisivo é o stack já existente e em produção no Portal de Segurança Digital (Laravel 13 + Filament + MySQL, com dompdf para relatórios). O custo marginal de construir o Notre Guard sobre essa base é substancialmente menor do que seria um projeto greenfield, e a equipe já opera o ambiente.

**Plano B explícito:** se o piloto da Fase 2 (ronda em campo) apresentar falha estrutural de sincronização ou rejeição pelos vigilantes, contratar Mobitraxx para o módulo de ronda e manter internamente apenas RDO/ocorrências/indicadores. Essa saída deve ser reavaliada no *gate* de fim da Fase 2.

---

## 4. Escopo funcional

### 4.1 MVP — Fase 1 e 2 (obrigatório para operar)

**Cadastros base**
- Unidades / sites (com geocoordenada e raio)
- Postos de serviço (vinculados à unidade, com QR Code próprio)
- Pontos de controle / checkpoints (código, QR/NFC, coordenada, raio de tolerância, instrução de verificação)
- Roteiros de ronda (sequência ordenada ou livre de checkpoints, tempo previsto, tolerância de atraso, janelas de execução)
- Vigilantes / equipe (integrado ao cadastro de colaboradores; registro profissional e validade da reciclagem — exigência do Estatuto da Segurança Privada, Lei 14.967/2024)
- Escalas de turno (12x36, 5x2, etc.)

**Ciclo de turno**
- Assunção de posto por leitura de QR do posto + selfie opcional + confirmação de recursos recebidos
- Passagem de serviço com pendências transferidas ao turno seguinte
- Fechamento de turno gerando o dossiê consolidado

**Ronda eletrônica (mobile)**
- Início de ronda a partir do roteiro atribuído
- Leitura de checkpoint: QR Code (câmera) com validação de GPS e horário
- Checklist por checkpoint (conforme / não conforme / não aplicável + observação + foto)
- Registro de checkpoint pulado com justificativa obrigatória
- Encerramento com resumo: X de Y pontos, tempo total, desvios
- **Tudo funcional offline**, com fila de sincronização visível ao usuário

**Ocorrências e RDO**
- Abertura de ocorrência de dentro ou fora da ronda
- Numeração automática `RO NNN/AAAA` por unidade
- Campos: data/hora do fato (distinta da hora do registro), local, tipo/assunto, severidade, classificação prevenção × perda, envolvidos, relato, providências tomadas, anexos (fotos/vídeo/áudio)
- Workflow: rascunho → registrada → em análise pela supervisão → encerrada
- **RDO** gerado por unidade/data, agregando turnos, rondas, checklists e ocorrências, com fechamento e PDF assinado digitalmente (hash + carimbo de tempo)

**Relatórios e dashboard**
- Dashboard operacional: rondas previstas × realizadas, aderência por vigilante/unidade, checkpoints com maior taxa de não conformidade, ocorrências abertas
- PDF: RDO diário, relatório de ronda individual, relatório de ocorrência, consolidado mensal por unidade
- Exportação CSV

### 4.2 Fase 3 (consolidação)

- Botão de pânico com escalonamento de notificação
- Alerta de inatividade / vigilante sem movimento por N minutos
- Controle de recursos: armamento, rádio, chaves, fardamento (entrega, devolução, divergência)
- Notificações push (PWA) e e-mail para supervisão em ocorrência de severidade alta
- Análise de recorrência: mapa de calor por local × horário × tipo
- Portal de consulta para gestores da unidade (visão restrita à própria unidade)

### 4.3 Fase 4 (evolução)

- Módulo de visitantes / controle de acesso
- Gestão de chaves e cofres
- Integração com CFTV (link do evento para o clipe da câmera)
- App wrapper nativo, se e somente se NFC ou geolocalização em background se provarem indispensáveis
- Indicadores de SLA contratual para terceirizados
- Ordens de serviço e planos de ação derivados de não conformidade

### 4.4 Fora de escopo (decisão consciente)

- **Registro de ponto eletrônico legal.** Marcar jornada exige conformidade com a Portaria 671/2021 (REP-P, arquivo AFD, certificação). Isso é um projeto próprio, de risco trabalhista alto. A assunção de posto registra *presença operacional*, não jornada — e isso deve estar escrito na tela e na política de uso, para não criar expectativa nem passivo.
- Folha de pagamento, faturamento de contrato, recrutamento.

---

## 5. Arquitetura técnica

### 5.1 Stack

| Camada | Escolha | Justificativa |
|---|---|---|
| Backend | **PHP 8.3 + Laravel 13** | requisito do projeto; stack já dominado e em produção |
| Backoffice/desktop | **Filament 4** | CRUD, tabelas, filtros e dashboards com custo de construção muito baixo; Filament 3 não roda em Laravel 13 |
| Mobile (campo) | **PWA própria** (Blade + Alpine.js ou Vue leve, service worker) | precisa de UI radicalmente enxuta e offline real — o Filament não serve para isso |
| Banco | **MySQL 8** | padrão do ambiente |
| Fila/cache | Redis (ou driver database no piloto) | geração de PDF, notificações, processamento de mídia |
| Storage de evidências | disco local no piloto, S3-compatível em produção | volume de fotos cresce rápido |
| API | rotas `api/v1` com **Laravel Sanctum** (token por dispositivo) | autenticação simples e revogável por aparelho |
| PDF | `barryvdh/laravel-dompdf` | mesmo componente já usado no Portal |
| Autenticação | Google Workspace, no mesmo modelo faseado do Portal | SSO institucional já resolvido |

**Filament 4 (decidido pelo ambiente):** o Portal está em Filament 3, e a intenção original era manter a paridade. Na instalação isso se mostrou impossível — Filament 3 exige `illuminate/auth ^12` e `symfony/console ^7`, incompatíveis com o Laravel 13 deste projeto. O Notre Guard usa Filament 4, cuja API de recursos é diferente (schemas e tables em classes separadas, `Filament\Schemas`), então componentes não são copiáveis entre os dois sistemas — o design system compartilhado precisa ser reimplementado, não reaproveitado como código.

### 5.2 Por que PWA e não app nativo

- Distribuição sem loja: instalação por link, atualização instantânea, sem ciclo de review.
- Um só código para Android e iOS, mesma linguagem do backend.
- Câmera, geolocalização e armazenamento local são acessíveis por Web API.
- Limitação real e conhecida: **Web NFC existe apenas no Chrome/Android.** Por isso o **QR Code é o mecanismo primário** de checkpoint, e o NFC entra como recurso opcional em dispositivos compatíveis. Se o NFC se tornar requisito rígido (ambiente com etiqueta sujeita a vandalismo, por exemplo), a saída é um wrapper nativo fino sobre a mesma PWA — decisão da Fase 4, não do MVP.
- Notificação push em PWA no iOS exige que o app esteja instalado na tela inicial (iOS 16.4+); o roteiro de implantação deve incluir esse passo no onboarding do aparelho.

### 5.3 Sincronização offline — desenho

Este é o componente de maior risco técnico e recebe atenção proporcional.

**Cliente**
- Armazenamento local em **IndexedDB** (via Dexie.js): roteiros, checkpoints e checklists baixados no início do turno; eventos produzidos em fila.
- Todo evento nasce com **UUID v7 gerado no dispositivo**, timestamp local e timestamp monotônico de dispositivo (para detectar relógio alterado).
- Fila de sincronização com estado visível ao vigilante: `N registros pendentes` — nunca esconder isso, é o que gera confiança.
- Fotos: comprimidas no dispositivo (máx. ~1600px, JPEG q80) antes de entrar na fila; enviadas em requisição separada do evento, referenciadas por UUID.
- Service worker com *background sync* onde disponível; retentativa com backoff exponencial; sync também disparada em `online` e ao abrir o app.

**Servidor**
- Endpoint `POST /api/v1/sync/events` recebe lote de eventos; **idempotente pelo UUID** (unique constraint + upsert silencioso). Reenvio nunca duplica.
- Resposta por item, para o cliente saber exatamente o que confirmar e o que retentar — nunca "tudo ou nada" no lote.
- O servidor **registra ambos os tempos**: `occurred_at` (do dispositivo) e `received_at` (do servidor). Relatórios usam `occurred_at`, auditoria compara os dois e sinaliza divergência suspeita.
- Sem resolução de conflito complexa: os eventos de campo são **append-only e imutáveis**. Correção se faz por evento de retificação vinculado ao original, nunca por edição destrutiva. Isso elimina a classe mais difícil de bug de sync e é o que dá valor probatório ao registro.
- Cadeia de integridade: cada evento de turno guarda o hash do anterior; o fechamento do turno sela a cadeia.

**Regra de ouro:** nenhuma validação que impeça o registro em campo. Se o GPS estiver fora do raio, ou o horário fora da janela, o registro é **aceito e marcado como desvio** — a análise é do supervisor, não do app. App que recusa registro produz vigilante que anota em papel.

### 5.4 Modelo de dados — entidades principais

```
units (unidades)                 ── id, nome, endereço, lat, lng, raio_m
posts (postos)                   ── unit_id, nome, qr_token, tipo
checkpoints                      ── unit_id, código, nome, qr_token, nfc_uid,
                                    lat, lng, raio_m, instrução, ativo
routes (roteiros)                ── unit_id, nome, ordenado(bool),
                                    duração_prevista_min, tolerância_min
route_checkpoints                ── route_id, checkpoint_id, ordem, obrigatório
route_schedules                  ── route_id, turno, janelas (json), dias_semana
guards (vigilantes)              ── user_id, matrícula, registro_prof,
                                    validade_reciclagem, unidade_padrão
shifts (turnos)                  ── guard_id, post_id, iniciado_em, encerrado_em,
                                    assunção_evidência, hash_cadeia, status
patrols (rondas)                 ── shift_id, route_id, iniciada_em, encerrada_em,
                                    status, pontos_lidos, pontos_previstos
patrol_scans                     ── uuid, patrol_id, checkpoint_id, occurred_at,
                                    received_at, lat, lng, precisão_m, método
                                    (qr|nfc|manual), desvios (json), justificativa
checklist_templates / _items     ── vinculáveis a checkpoint ou roteiro
checklist_responses              ── uuid, patrol_scan_id, item_id, resposta,
                                    observação
incidents (ocorrências)          ── uuid, número (RO NNN/AAAA), unit_id, shift_id,
                                    tipo_id, severidade, classificação
                                    (prevenção|perda), occurred_at, local,
                                    relato, providências, status, envolvidos(json)
incident_types                   ── taxonomia configurável, hierárquica
attachments                      ── anexável (polimórfico), path, mime, hash,
                                    exif_lat, exif_lng, capturado_em
daily_reports (RDO)              ── unit_id, data, status (rascunho|fechado),
                                    fechado_por, fechado_em, pdf_path, hash
resources / resource_handovers   ── armamento, rádio, chave, fardamento
panic_alerts                     ── guard_id, occurred_at, lat, lng,
                                    status, atendido_por
audit_logs                       ── ator, ação, entidade, antes/depois, ip, ua
sync_batches                     ── device_id, recebido_em, itens, resultado
```

### 5.5 Requisitos não funcionais

**LGPD** — o sistema trata dado pessoal de colaborador (localização, foto, biometria facial se houver selfie) e eventualmente de terceiros citados em ocorrência.
- Base legal: execução de contrato de trabalho e legítimo interesse na segurança patrimonial, documentados em registro de tratamento.
- **Localização só é coletada durante ronda ativa** — jamais rastreamento contínuo. Isso precisa estar tecnicamente garantido, não só prometido.
- Retenção definida por tipo de registro (proposta: evidência de ronda 12 meses, ocorrência 5 anos, RDO 5 anos) com expurgo automatizado.
- Anonimização de terceiros citados em relatório exportado para fora da unidade.
- Aviso de transparência exibido na primeira instalação do app, com aceite registrado — reaproveitando o mecanismo de aceite já existente no Portal.

**UX de campo** — alvos duros, tratados como requisito e não como polimento: alvo de toque ≥ 48px; operação com uma mão; contraste alto e modo escuro real (turno noturno); fluxo de leitura de checkpoint em no máximo 2 toques; feedback tátil e sonoro na leitura bem-sucedida; nenhuma tela crítica dependente de rede.

**Outros** — auditoria de toda alteração em registro consolidado; latência de sync percebida irrelevante (é assíncrona por desenho); consumo de bateria monitorado no piloto (GPS de alta precisão só no momento da leitura, nunca em polling contínuo).

---

## 6. Roadmap

Premissa: **1 desenvolvedor full-stack dedicado**, com apoio pontual de design. Prazos em semanas de trabalho.

| Fase | Entrega | Duração | Gate de saída |
|---|---|---|---|
| **0 — Descoberta** | Mapeamento do processo atual com a equipe de segurança; inventário de unidades, postos e rotas reais; taxonomia de ocorrências; definição dos KPIs | 2 sem | Processo desenhado e aprovado pelo gestor de segurança |
| **1 — Fundação** | Projeto Laravel, autenticação Google, cadastros base em Filament, geração e impressão de QR Codes, modelo de dados, seeds, CI | 3 sem | Gestor consegue cadastrar unidade, posto, checkpoint e roteiro sozinho |
| **2 — Campo (MVP)** | PWA do vigilante: assunção de posto, ronda com leitura QR, checklist, ocorrência com foto, offline + sync, fechamento de turno. **Piloto em 1 unidade** | 5 sem | 2 semanas de piloto com ≥ 95% dos registros sincronizados e aceitação dos vigilantes |
| **3 — Gestão** | RDO com workflow e PDF, dashboard de aderência, relatórios, notificações, portal do gestor de unidade | 3 sem | RDO substitui o relatório atual em papel/planilha na unidade piloto |
| **4 — Rollout** | Ajustes do piloto, treinamento, expansão para as demais unidades, documentação operacional | 3 sem | Todas as unidades operando; processo antigo desativado |
| **5 — Consolidação** | Botão de pânico, inatividade, controle de recursos, análise de recorrência | 4 sem | — |

**MVP operacional (Fases 0–2): 10 semanas.** **Sistema completo em produção (Fases 0–4): 16 semanas.**

Sequenciamento crítico: a Fase 2 **não pode** ser fatiada com o offline deixado para depois. Offline é arquitetura, não recurso — retrofitar sync em cima de um app que assumiu conectividade custa mais do que reescrever.

---

## 7. Riscos

| Risco | Impacto | Mitigação |
|---|---|---|
| Rejeição pelos vigilantes ("vigilância do vigilante") | Alto — mata o projeto | Envolver a equipe na Fase 0; comunicar como proteção do profissional (comprovação de trabalho feito, botão de pânico) e não como fiscalização; jamais rastrear fora da ronda |
| Sincronização offline com perda de registro | Alto | UUID no cliente + idempotência + eventos imutáveis + fila visível; testar com rede desligada como caso de teste de primeira classe |
| QR Code vandalizado, arrancado ou fotografado | Médio | Etiqueta resistente, código com token não sequencial, validação por GPS, alerta de leitura fora de raio; NFC nos pontos mais expostos |
| Aparelho inadequado (câmera ruim, bateria fraca, Android antigo) | Médio | Definir baseline mínimo de dispositivo na Fase 0 e testar nos aparelhos reais do piloto, não só em emulador |
| Confusão entre assunção de posto e registro de ponto | Médio — passivo trabalhista | Rótulo explícito na UI, política de uso assinada, RH ciente por escrito |
| Escopo crescendo para "sistema de tudo" | Médio | Fases 3 e 4 congeladas até o rollout completo da Fase 4 |
| Desenvolvedor único como ponto de falha | Médio | Documentação em `docs/` desde o dia 1, no padrão já adotado no Portal |

---

## 8. Métricas de sucesso

**Operacionais** — aderência de ronda (realizadas ÷ previstas) ≥ 95%; tempo médio entre o fato e o registro da ocorrência < 15 min; taxa de registro com desvio de GPS < 5%; % de RDO fechado no próprio dia = 100%.

**Técnicas** — registros perdidos: zero; taxa de sync bem-sucedida ≥ 99,5%; tempo mediano entre `occurred_at` e `received_at` < 5 min em área com cobertura.

**De adoção** — % de vigilantes usando sem suporte após 2 semanas ≥ 90%; ocorrências registradas em papel após rollout: zero.

---

## 9. Decisões pendentes

1. **Aparelhos:** dispositivo corporativo por posto ou BYOD? Impacta MDM, política de uso, LGPD e baseline técnico. *Recomendação: corporativo por posto, um por turno.*
2. **Unidade piloto:** definir na Fase 0 — idealmente uma com boa cobertura de rede e supervisão engajada, não a mais difícil.
3. ~~Filament 3 × 4?~~ **Resolvido em 25/07/2026 pelo ambiente:** Filament 3 exige `illuminate/auth ^12` e `symfony/console ^7`, e não é instalável em Laravel 13. O projeto usa **Filament 4**. Ver `docs/04-decisoes-tecnicas.md`.
4. ~~Aplicação separada ou módulo do Portal?~~ **Resolvido em 25/07/2026:** aplicação independente, em `C:\claude\notre_guard`, com banco próprio, compartilhando autenticação Google e design system com o Portal.
5. **Escopo de escala de turno:** o Notre Guard gerencia a escala ou apenas a consome de fonte externa? Definir na Fase 0.

---

## Fontes

- [Performance Lab — Segurança Patrimonial](https://www.performancelab.com.br/setores/seguranca-patrimonial/)
- [Mobitraxx](https://www.mobitraxx.com.br/)
- [Ronda Fácil](https://www.rondafacil.com.br/)
- [Marcozero Data — Ronda e gestão de incidentes](https://www.marcozerodata.com.br/solucoes/ronda-e-gestao-de-incidentes)
- [SSRonda / Veolink — Ronda informatizada](https://veolink.com.br/solucoes/ronda-informatizada/)
- [Controle de Rondas](https://controlederondas.com.br/)
- [MobRonda (Google Play)](https://play.google.com/store/apps/details?id=br.com.mobtech.mobronda)
- [Rondel Tecnologia — bastão de ronda](https://www.rondel.com.br/)
- [FindMe — bastão eletrônico × software de gestão de rondas](https://findme.id/bastao-eletronico-de-ronda-por-que-voce-deve-considerar-substituir-essa-tecnologia-por-software-para-gestao-de-rondas/)
- [Gestão de Segurança Privada — sistema de controle de ronda](https://gestaodesegurancaprivada.com.br/sistema-de-controle-de-ronda-da-seguranca-vigilancia-patrimonial/)
- [Gestão de Segurança Privada — relatório de ocorrência](https://gestaodesegurancaprivada.com.br/relatorio-de-ocorrencia-da-seguranca-patrimonial/)
- [Silvertrac / Trackforce](https://www.silvertracsoftware.com/silvertrac-is-part-of-trackforce)
- [Therms — best security guard reporting software 2026](https://www.therms.io/best-security-guard-reporting-software/)
- [Lei nº 14.967/2024 — texto original (Câmara)](https://www2.camara.leg.br/legin/fed/lei/2024/lei-14967-9-setembro-2024-796214-publicacaooriginal-172963-pl.html)
- [Guia da Lei 14.967/2024 — ESSP](https://www.essp.com.br/2024/10/07/lei-14-967-2024/)

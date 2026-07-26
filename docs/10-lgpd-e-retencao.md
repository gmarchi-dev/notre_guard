# 10 — LGPD e retenção de dados

O sistema trata dado pessoal de colaborador — **localização durante a ronda**, foto, nome,
matrícula — e eventualmente de terceiros citados em ocorrência. Guardar além do necessário é
tratamento sem base legal.

## Prazos

Definidos em [`config/retention.php`](../config/retention.php), ajustáveis por variável de
ambiente. Alterar prazo é decisão de negócio: registre o motivo aqui antes de mudar.

| Dado | Prazo | Por quê |
|---|---|---|
| Evidências (foto, vídeo, áudio) | 12 meses | Onde está a imagem do colaborador e de terceiros |
| Turnos, rondas e leituras | 12 meses | Onde está a localização do vigilante — o dado mais sensível e o de menor valor histórico |
| Ocorrências e RDO | 5 anos | Documentos que podem ser exigidos em processo administrativo ou judicial |
| Logs de sincronização | 6 meses | Dado técnico: aparelho, lote, erro |
| Notificações do painel | 12 meses | Carregam resumo de ocorrência |

## Duas regras que orientam o expurgo

**1. Evidência vencida perde o binário, não a linha.** Quando a foto de uma ocorrência ainda
retida vence, o arquivo é apagado e a linha permanece com `status = 'purged'` e o **hash
SHA-256 preservado**. A ocorrência continua mostrando que houve evidência, e o hash prova o que
existiu — sem guardar o conteúdo.

**2. Documento sobrevive ao dado operacional que o gerou.** Uma ocorrência de dois anos continua
legível depois de a ronda que a originou ser eliminada. As chaves estrangeiras de `incidents`
para turno, ronda e ponto são `nullOnDelete` justamente por isso.

## Ordem do expurgo

1. Ocorrências vencidas (5 anos), com suas evidências.
2. Turnos fechados vencidos (12 meses) — a cascata leva rondas, leituras e respostas de
   checklist. As evidências dessas leituras são **polimórficas e não têm cascata**, então são
   removidas antes; do contrário sobrariam linhas apontando para ids inexistentes.
3. Evidências vencidas cujo dono ainda está no prazo: apaga só o arquivo.
4. Rede de segurança: evidências órfãs, de exclusões que não passaram pelo expurgo.
5. Logs de sincronização e notificações vencidos.
6. Marca os RDOs cujas datas foram expurgadas.

**Turno aberto nunca é expurgado**, mesmo com data antiga. Turno aberto há mais de um ano é
sintoma de problema, não dado vencido — apagar esconderia a falha.

## Interação com o selo do RDO

Um RDO fechado é verificado recalculando o conteúdo e comparando com o `content_hash`. Depois do
expurgo, esse recálculo daria vazio e acusaria "chegaram registros após o fechamento" para todo
RDO antigo — um falso alarme permanente.

Por isso o expurgo marca `daily_reports.data_purged_at`, e tanto a verificação de integridade
quanto o recálculo de rascunho passam a ignorar essas datas. O documento segue selado e legível;
o que não dá mais é recalculá-lo.

## Execução

Agendado em [`routes/console.php`](../routes/console.php) para as 03:30, fora da janela da ronda
noturna. **Exige o agendador do Laravel ativo** — em produção, um cron chamando `schedule:run`.

```bash
php artisan notre-guard:purge-data --dry-run
```

```bash
php artisan notre-guard:purge-data
```

## Prestação de contas

Cada execução, inclusive as simulações, grava uma linha em `retention_runs` com os prazos
vigentes no momento e o que foi eliminado. A LGPD exige poder **demonstrar** o tratamento,
incluindo a eliminação: sem esse histórico, "nós apagamos" é afirmação sem prova.

O histórico fica visível em Configuração → Retenção de dados, somente leitura e só para
administrador, com um botão de simulação.

## O que ainda falta em conformidade

- **Termo de transparência com aceite registrado** na primeira instalação do aplicativo. O
  aviso já aparece na tela de login da PWA, mas o aceite não é gravado. O plano previa
  reaproveitar o mecanismo de aceite do Portal de Segurança Digital.
- **Anonimização de terceiros** em relatório exportado para fora da unidade.
- **Registro de tratamento** (base legal, finalidade, compartilhamento) como documento formal —
  isto aqui descreve a implementação, não substitui o documento do encarregado.
- Não há atendimento automatizado a pedido de titular (acesso, correção, eliminação).

# 13 — Controle de chaves

A portaria guarda todas as chaves e as libera a quem solicita. Este módulo é o **livro de
retiradas** dessa operação: quem levou, quando, com que prazo, e se devolveu.

> Não confundir com entrega de equipamento ao vigilante no início do turno. São processos
> diferentes: aquele acontece duas vezes por turno com a mesma pessoa; este acontece dezenas de
> vezes por dia com pessoas de fora da segurança.

## Onde se opera

**Painel próprio da portaria, em `/portaria`** — computador do balcão, teclado de verdade. Digitar
nome de solicitante dezenas de vezes por dia no celular seria lento.

O painel existe separado do administrativo porque o vigilante precisa entrar nele, e o
administrativo tem a operação inteira das duas unidades. **Separar é a diferença entre dar uma
tela e dar acesso ao sistema** — verificado: o vigilante logado na portaria recebe 403 em
`/admin`.

O login é por **matrícula**, a mesma credencial do aplicativo de campo. Pedir e-mail criaria uma
segunda credencial para a mesma pessoa.

## Modelo

| Tabela | O que é |
|---|---|
| `key_items` | A chave física. **Uma linha por cópia**: se há duas cópias da sala 203, são "12A" e "12B", porque a portaria pendura cada uma no seu gancho. Contar cópias num inteiro esconderia qual delas está fora. |
| `key_holders` | Cadastro de solicitantes: professores, funcionários, prestadores. Existe para evitar que "João", "joao silva" e "J. Silva" virem três pessoas. |
| `key_loans` | A retirada: chave, solicitante, quem entregou, quando, prazo, devolução, quem recebeu. |

**A situação da chave não é uma coluna.** "No quadro" ou "com fulano" é derivado do empréstimo em
aberto. Uma coluna de status precisaria ser mantida em sincronia com os empréstimos, e é
exatamente aí que esse tipo de sistema começa a mentir.

## Garantias

**Uma chave não pode estar com duas pessoas.** A liberação usa `lockForUpdate` na linha da chave:
duas portarias registrando ao mesmo tempo criariam dois empréstimos abertos. Há teste.

**A devolução não apaga a retirada.** O empréstimo ganha `returned_at` e continua no livro. Quem
entregou e quem recebeu podem ser pessoas diferentes — a chave atravessa o turno.

**O atraso fica registrado mesmo depois da devolução.** `overdueMinutes()` de uma chave devolvida
com duas horas de atraso continua sendo 120. É o dado que sustenta a conversa com quem sempre
devolve fora do prazo.

## Prazo e atraso

O prazo é informado **em cada retirada** (padrão: fim do expediente). O atraso é medido contra
ele.

Aviso de chaves não devolvidas: **uma vez por dia, às 19h, em dias úteis**, com a lista completa
por unidade. Avisar de hora em hora transformaria a pendência em ruído, e a portaria já vê o
atraso no próprio quadro. Um aviso com a lista, e não um por chave: cinco e-mails separados às
19h viram cinco e-mails ignorados.

## No RDO

As chaves entram no relatório diário: retiradas no dia, devolvidas, e **as que ficaram fora do
quadro** — que é a pendência que a portaria passa ao turno seguinte.

"Em aberto" é medido **no fim do dia do relatório**, não no momento da consulta: um RDO de ontem
não pode mudar porque a chave voltou hoje de manhã. Há teste para isso.

## Cadastro

O cadastro do quadro (quais chaves existem) é da **supervisão**, em Cadastros → Chaves no painel
administrativo. A portaria opera, não cadastra.

Já o cadastro de **solicitante** pode ser feito na hora da retirada, direto no modal: quem chega
no balcão e não está na lista não pode virar motivo para anotar no papel.

## Fora de escopo

**Armamento**, informado em 26/07/2026: não se aplica a este contexto.

**Rádio**: em avaliação. Um controle de rádio seria cadastro de aparelhos mais entrega e
devolução por turno. Com dois postos e equipe pequena, quem estava com qual rádio é dedutível do
turno, então o ganho é baixo e o custo é mais dois cliques na assunção de posto todo dia.
Reavaliar depois do piloto.

## Pendências

- Não há autorização permanente ("quem pode pegar quais chaves"). Toda retirada é registrada,
  mas o sistema não valida se aquela pessoa poderia levar aquela chave.
- Sem chave-mestra ou molho: cada chave é liberada individualmente.
- Sem histórico de troca de segredo ou registro de chave perdida — hoje isso seria uma
  observação em texto.
- A portaria não tem busca por QR/código de barras na chave; a busca é por texto.

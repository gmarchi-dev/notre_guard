# 08 — Painel operacional

Página inicial do painel (`/admin`). Dois filtros no topo: **período** (7, 30 ou 90 dias) e
**unidade**.

## Indicadores

| Indicador | Cálculo | Faixas |
|---|---|---|
| Aderência de ronda | pontos lidos ÷ pontos previstos | verde ≥ 95%, amarelo ≥ 80%, vermelho abaixo |
| Rondas realizadas | total, com quantas ficaram incompletas | amarelo se houver incompleta |
| Ocorrências | total, em aberto e graves (alta + crítica) | vermelho se houver grave |
| Leituras com desvio | leituras com ao menos um desvio ÷ total de leituras | verde ≤ 5%, amarelo ≤ 20% |
| Turnos abertos agora | turnos em aberto neste instante | ignora o filtro de período, de propósito |

## Gráficos

- **Aderência por dia** (linha), com a meta de 95% tracejada.
- **Ocorrências por hora do dia** (barras) — a recorrência por faixa horária é o que orienta
  reforço de posto e redesenho de janela de ronda.
- **Pontos com mais não conformidade** (barras horizontais) — onde o problema é crônico.
- **Ocorrências por tipo** (barras horizontais).

## Decisões que afetam a leitura dos números

**Dia sem ronda fica vazio, não zero.** "Nenhuma ronda prevista" e "as rondas falharam" são
coisas diferentes; ligar os pontos por cima de um domingo sem operação desenharia uma queda que
não existiu. Por isso `adherenceByDay()` devolve `null` nesses dias e o gráfico usa
`spanGaps: false`.

**Aderência sem rondas é `null`, não 0%.** Mesmo motivo: o cartão mostra "—".

**A taxa de desvio é por leitura, não por marca.** Uma leitura pode acumular vários desvios
(sem GPS *e* fora da janela); contar marcas faria a taxa passar de 100%.

**Agregação por dia e por hora é feita em PHP.** `HOUR()` e `strftime()` divergem entre MySQL e
SQLite, e o volume aqui é de centenas de registros por mês. Portabilidade vale mais que os
microssegundos economizados.

## Escopo

O filtro de unidade é uma conveniência da tela, não o mecanismo de segurança: o
`ReadsDashboardFilters` **reforça a unidade do gestor no servidor**, ignorando o que vier do
Livewire. Um gestor de unidade que peça "todas as unidades" continua vendo apenas a dele — há
teste para isso.

## Testes

Widgets de gráfico são *lazy*: a página abre sem executar `getData()`, então um erro no gráfico
só apareceria para o gestor, na tela. `DashboardWidgetsTest` monta cada widget via Livewire, com
e sem dados, justamente para cobrir isso — o caso "sem dados" é onde mora a divisão por zero.

# 12 - Segurança do vigilante

Botão de pânico e alerta de inatividade. Os dois vivem na mesma tabela
(`safety_alerts`) porque têm origens diferentes mas o mesmo ciclo de vida
(aberto → reconhecido → encerrado ou falso alarme), a mesma tela e o mesmo aviso.

> **O sistema avisa; quem confirma se o vigilante está bem é o rádio.** Nenhuma
> tela substitui procedimento de atendimento.

## Botão de pânico

Sempre visível no rodapé da PWA, em qualquer tela depois do login. Vermelho reservado: nenhuma
outra ação da interface usa essa cor de fundo.

**Duas etapas para acionar.** Um toque só disparia alarme com o celular no bolso; três etapas
custariam segundos que importam. A confirmação é tela cheia, com alvo de 84px - é acionado sob
estresse, possivelmente correndo.

### Não passa pela fila

Endpoint dedicado `POST /api/v1/panic`, fora da sincronização normal. A fila roda a cada 30
segundos e em lote - irrelevante para uma leitura de ponto, inaceitável para um pedido de
socorro.

O GPS é lido com prazo de 4 segundos. Se demorar, envia sem coordenada: **alerta sem localização
chega e serve; alerta que não chega não serve para nada.**

### Contingência offline

Se a entrega direta falhar (sem rede, servidor lento, timeout de 6s), o app enfileira o mesmo
evento **com o mesmo uuid**. Isso é o que garante que a entrega tardia não crie um segundo
alerta - e é por isso que `enqueue()` aceita um uuid explícito.

O vigilante vê qual dos dois aconteceu: "Emergência recebida pela supervisão às HH:MM" ou "Sem
rede. O acionamento está salvo no aparelho e sobe assim que houver sinal. **Use o rádio.**"

### A notificação não é enfileirada

`SafetyAlertRaised` **não** implementa `ShouldQueue`, ao contrário da notificação de ocorrência.
Um botão de pânico que depende do worker da fila estar no ar é um botão quebrado. O envio
acontece na própria requisição: pânico é raro, e aqui a certeza vale mais que a latência.

Se o envio falhar (SMTP fora, por exemplo), o alerta **continua gravado** e visível na tela - a
falha do aviso não pode apagar o acionamento. Há teste para isso.

## Alerta de inatividade

Agendado a cada 5 minutos (`notre-guard:watch-inactivity`). O intervalo define a pior latência
entre o vigilante parar de registrar e a supervisão saber.

**Vigia rondas em andamento, não turnos.** Um vigilante em portaria pode passar horas sem ler um
ponto, e isso é normal - alertar nesse caso geraria ruído constante e mataria a credibilidade do
alerta. Ronda iniciada e silenciosa, não: ou aconteceu algo com ele, ou o aparelho ficou sem
bateria, e as duas coisas a supervisão precisa saber.

Limite em `config/safety.php`, padrão **30 minutos**, partindo da duração prevista das rondas
cadastradas (30–40 min para o perímetro completo). Ajustar depois do piloto, com dado real de
quanto tempo leva entre pontos.

**O silêncio é medido por `occurred_at`**, a hora do aparelho, não pela chegada no servidor. Uma
leitura feita há 5 minutos que só sincronizou agora não é inatividade: o vigilante agiu, a rede
é que estava fora.

**Um alerta por ronda**, garantido por índice único `(kind, patrol_id)`. Sem isso o agendador
criaria um alerta novo a cada 5 minutos enquanto o silêncio durasse.

A localização registrada é a **última posição conhecida** - não é onde ele está, é onde foi visto
por último, que é o que orienta a busca.

## Tela da supervisão

Operação → Alertas de segurança, primeiro item do grupo, com contador na navegação (vermelho se
houver pânico aberto, amarelo se só inatividade). Filtro "somente em aberto" ativo por padrão,
alertas abertos sempre no topo, e recarga automática a cada 30s - é tela de plantão.

Três ações: **Reconhecer** (sem confirmação: é dizer "estou vendo", e cada clique extra custa
segundos), **Encerrar** com relato obrigatório, e **Falso alarme**, que exige descrever como foi
confirmado com o vigilante.

A coluna de situação mostra em quantos minutos o alerta foi reconhecido. É o número que diz se a
supervisão está de fato atendendo - um botão de pânico que ninguém vê não protege ninguém.

## Pendências

- **Sem escalonamento.** Se ninguém reconhecer em N minutos, não há segundo aviso nem contato
  alternativo. É a lacuna mais séria que resta aqui.
- Sem push no celular: o supervisor em trânsito depende do e-mail.
- O vigilante não recebe confirmação de que a supervisão viu o alerta.
- Sem indicador de tempo médio de atendimento no painel de indicadores.

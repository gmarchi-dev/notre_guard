# 09 - Notificações

Aviso de ocorrência que não pode esperar o RDO do dia seguinte.

## Quando dispara

Na **criação** da ocorrência, não na análise: uma ocorrência grave que espera alguém abrir o
painel já perdeu o propósito. O gatilho é o observer `IncidentObserver`.

Dois critérios, avaliados em `IncidentNotifier::deservesNotification()`:

1. Gravidade **alta** ou **crítica**;
2. Tipo de ocorrência marcado com `notify_supervision` - é como a gestão sinaliza um assunto
   sensível independente da gravidade que o vigilante escolheu em campo (por exemplo "câmera
   inoperante", que costuma ser registrada como média).

Ocorrência rotineira **não** notifica ninguém. Ruído constante é o caminho mais curto para a
supervisão passar a ignorar o sistema.

## Quem recebe

| Perfil | Recebe |
|---|---|
| Administrador | todas as unidades |
| Supervisão | todas as unidades |
| Gestor de unidade | apenas ocorrências da unidade dele |
| Vigilante | nunca - está em campo, não em posição de tratar |

Usuário inativo não recebe.

## Canais

- **Sino no painel** (`database`), com polling de 30s. É a via que funciona sem WebSockets.
- **E-mail**, com número da ocorrência e gravidade no assunto:
  `[SEDE] Crítica - RO 002/2026`.

Ambos trazem link direto para a ficha da ocorrência.

## Fila

A notificação é `ShouldQueue` com **`afterCommit`**. A ocorrência nasce dentro da transação do
evento de sincronização, e sem isso a fila poderia processar o aviso antes do commit e não
encontrar o registro.

Cada destinatário gera um job por canal - dois destinatários com dois canais são quatro jobs.
**É preciso ter um worker rodando**, senão os avisos ficam parados na tabela `jobs`:

```bash
php artisan queue:work
```

Em produção isso é um serviço supervisionado, não um comando de terminal.

## Não duplica

O evento `incident.report` é idempotente pelo uuid: o reenvio da fila do aparelho devolve
`duplicate` sem criar registro, e o observer não roda de novo. Há teste cobrindo isso.

## Cuidado de implementação

`Illuminate\Bus\Queueable` já declara `$afterCommit`; redeclarar a propriedade com tipo gera
erro fatal de composição de trait. Atribuir no construtor.

A ação da notificação do Filament vem de `Filament\Actions\Action`, não de
`Filament\Notifications\Actions\Action` (que não existe na v4).

## Pendências

- **Sem push no celular.** A PWA tem service worker, mas não há chaves VAPID nem assinatura de
  push. O vigilante e o supervisor em trânsito só veem pelo e-mail.
- Sem escalonamento: não há "se ninguém abriu em N minutos, avise o próximo".
- Sem preferência por usuário: quem tem o perfil recebe pelos dois canais.

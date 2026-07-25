# 06 — PWA de campo

Acesso: `/campo`. Instalável na tela inicial. Aparelho corporativo, um por posto/turno.

## Arquivos

| Arquivo | Papel |
|---|---|
| `resources/views/field/app.blade.php` | Telas (Alpine.js) |
| `resources/css/field.css` | Estilo de campo, escrito à mão |
| `resources/js/field/app.js` | Estado e fluxo |
| `resources/js/field/db.js` | IndexedDB via Dexie: cache do turno e fila |
| `resources/js/field/sync.js` | Motor de sincronização |
| `resources/js/field/api.js` | Cliente HTTP |
| `resources/js/field/scanner.js` | Leitor de QR |
| `resources/js/field/geo.js` | Posição pontual |
| `public/sw.js` | Service worker (casca offline) |
| `scripts/generate-icons.php` | Gera os ícones da PWA |

## Fluxo

Login por matrícula → assumir posto → iniciar ronda → ler pontos (QR, manual ou pular com
justificativa) → checklist por ponto → encerrar ronda → encerrar turno. Ocorrência pode ser
aberta a qualquer momento, dentro ou fora da ronda.

O indicador da barra de estado abre a **tela de fila**, que lista o que está no aparelho:
aguardando envio, tentando de novo e recusado. Os recusados mostram o motivo devolvido pelo
servidor e têm ação de tentar de novo ou descartar.

## Decisões da interface

Tratadas como requisito, não como acabamento:

- **Tema escuro fixo.** A tela é usada de madrugada; branco cega e destrói a visão noturna.
- **Alvo de toque de 56 px** e ações principais na base da tela: operação com uma mão, de luva.
- **Fila sempre visível** na barra de estado. Esconder pendência é o que faz o vigilante
  desconfiar do sistema e voltar para o papel.
- **Feedback tátil e sonoro** na leitura: confirma sem exigir que ele olhe para a tela.
- **Nenhuma tela crítica depende de rede.** "Sem rede" é estado normal, não erro.

## Offline

O evento nasce no IndexedDB com uuid gerado no aparelho e só sai da fila quando o servidor
confirma aquele uuid. A sincronização roda a cada 30 s, ao voltar a rede (`online`) e ao trazer
o app para frente.

O service worker cacheia **só a casca** — nunca respostas da API. Uma resposta velha de
`/bootstrap` faria o vigilante rondar com roteiro desatualizado, que é pior do que não abrir.

Se o aparelho for trocado no meio do turno, o novo recebe `open_shift` no bootstrap e assume o
turno em curso.

## Limitações conhecidas

- **HTTPS é obrigatório.** Câmera, geolocalização e service worker não funcionam em HTTP, nem
  em `localhost` do celular. Em desenvolvimento, usar `herd secure` ou túnel.
- **NFC não está implementado.** Web NFC só existe em Chrome/Android; o QR é o mecanismo
  primário e o campo `nfc_uid` já está no modelo para quando isso for necessário.
- **Notificação push exige o app instalado na tela inicial** no iOS (16.4+). Entra no roteiro
  de preparação do aparelho.
- A tela de fila lista os eventos, mas **não as fotos individualmente** — só o total pendente.

## Privacidade

A localização é lida **pontualmente**, no momento de cada registro, via
`navigator.geolocation.getCurrentPosition`. Não há watch, nem polling, nem coleta fora da ronda.
O aviso na tela de login diz isso ao vigilante e deixa explícito que o app **não é registro de
ponto** e não substitui a marcação de jornada.

Negar a permissão de localização não bloqueia nada: o registro entra com a marca `no_gps`.

# 07 — RDO (Relatório Diário de Ocorrências)

Um RDO por **unidade e data**. Agrega o que foi registrado em campo: turnos, rondas, leituras
com desvio, não conformidades de checklist e ocorrências.

## Ciclo

**Rascunho é espelho.** Enquanto está aberto, o conteúdo é recalculado a cada vez que a
listagem ou a ficha é aberta. Números de RDO em rascunho nunca envelhecem em silêncio.

**Fechado é fotografia.** No fechamento o conteúdo é congelado no campo `summary`, selado com
`content_hash` (SHA-256 do JSON) e o PDF é gerado em `storage/app/private/reports/AAAA/MM/`.
A partir daí `buildOrUpdate()` não recalcula mais nada.

Só as **observações da supervisão** são editáveis, e apenas enquanto o RDO está aberto — elas
entram no PDF.

## Fechar exige turnos encerrados

`DailyReportBuilder::close()` recusa quando ainda há turno aberto na data. Fechar com turno em
aberto produziria um documento que nasce desatualizado, porque os registros daquele turno ainda
vão chegar. Na prática o RDO é fechado no dia seguinte, depois de encerrados os turnos.

## Registros que chegam depois

Um aparelho pode passar dias sem rede. Quando os eventos finalmente sobem, eles podem pertencer
a uma data cujo RDO já foi fechado.

`hasLateRecords()` recalcula o conteúdo da data e compara com o `content_hash` selado. Se
divergir, a ficha exibe um aviso persistente: o documento continua válido como registro do que
foi fechado, mas deixou de refletir o que existe hoje. Administrador pode **reabrir**, o que
invalida o selo e o PDF, e fechar de novo.

Essa é a razão de existir o selo. Sem ele, um RDO fechado e um banco alterado depois seriam
indistinguíveis.

## PDF

Gerado com dompdf a partir de `resources/views/pdf/daily-report.blade.php`, fonte DejaVu Sans
(necessária para os acentos). Fica em disco privado e é servido por
`GET /rdo/{dailyReport}/pdf`, que verifica a unidade do usuário — o escopo do painel não
protege um link direto.

O rodapé traz quem fechou, quando, e o selo SHA-256 por extenso.

## Cuidado ao consultar por data

O cast `date` grava `'Y-m-d H:i:s'`. Comparar com `where('report_date', '2026-07-25')` não casa
e cria um segundo RDO, que o índice único derruba. Sempre `whereDate()`.

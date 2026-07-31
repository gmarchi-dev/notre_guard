# Gera todos os derivados da marca a partir de img/logo_notre.svg.
#
#   resources/svg/marca-completa.svg   brasão + assinatura, em currentColor
#   resources/svg/marca-brasao.svg     só o brasão, em currentColor
#   public/icons/notre-guard.svg       ícone da PWA
#   public/icons/notre-guard-maskable.svg  idem, com área segura de 22%
#
# Rodar só quando a marca original mudar:
#   powershell -File scripts/gerar-marca.ps1
#
# ATENÇÃO: este arquivo precisa ser salvo em UTF-8 COM BOM. O Windows
# PowerShell 5.1 lê script sem BOM como ANSI e corrompe todo acento - foi
# assim que a primeira geração saiu com "BrasÃ£o" dentro dos SVGs.

$ErrorActionPreference = 'Stop'
$raiz = Split-Path -Parent $PSScriptRoot
Set-Location $raiz

$utf8 = New-Object System.Text.UTF8Encoding($false)

# ReadAllText com UTF8 explícito, não Get-Content: a mesma armadilha de encoding.
$origem = [System.IO.File]::ReadAllText((Join-Path $raiz 'img\logo_notre.svg'), [System.Text.Encoding]::UTF8)
$linhas = $origem -split "`r?`n"

# Linhas 15-40 do original: assinatura. 41-70: brasão.
$assinatura = ($linhas[14..39] -join "`n")
$brasao = ($linhas[40..69] -join "`n")

# Caixa real do desenho, medida no navegador com getBBox - o viewBox do
# arquivo original é uma folha A4 com folga enorme em volta.
$vbX = 160.0; $vbY = 457.0; $vbLarg = 173.0; $vbAlt = 278.0
$assinaturaLarg = 521.0

function Gravar([string]$destino, [string]$conteudo) {
    $pasta = Split-Path -Parent (Join-Path $raiz $destino)
    New-Item -ItemType Directory -Force -Path $pasta | Out-Null
    [System.IO.File]::WriteAllText((Join-Path $raiz $destino), $conteudo, $script:utf8)
    Write-Output ("{0}  {1:n0} bytes" -f $destino.PadRight(40), (Get-Item (Join-Path $raiz $destino)).Length)
}

# --- marcas para a interface -------------------------------------------------
# A cor literal do original (#223f51) vira currentColor: a mesma marca serve os
# quatro temas do app de campo e os dois painéis, sem um arquivo por fundo.

$cabecalhoMarca = @'
<!--
    Marca do Colégio Notre Dame Campinas.

    Gerado por scripts/gerar-marca.ps1 a partir de img/logo_notre.svg.
    Não editar à mão.

    A cor do original virou `currentColor`: a mesma marca serve os quatro temas
    do app de campo e os dois painéis, sem precisar de um arquivo por fundo.
-->
'@

Gravar 'resources\svg\marca-completa.svg' ($cabecalhoMarca + @"

<svg xmlns="http://www.w3.org/2000/svg" viewBox="$vbX $vbY $assinaturaLarg $vbAlt" fill="currentColor">
  <defs><style>.st0{fill-rule:evenodd}</style></defs>
$assinatura
$brasao
</svg>
"@)

Gravar 'resources\svg\marca-brasao.svg' ($cabecalhoMarca + @"

<svg xmlns="http://www.w3.org/2000/svg" viewBox="$vbX $vbY $vbLarg $vbAlt" fill="currentColor">
  <defs><style>.st0{fill-rule:evenodd}</style></defs>
$brasao
</svg>
"@)

# --- ícones da PWA -----------------------------------------------------------
# SVG e não PNG: nítido em qualquer densidade e um arquivo em vez de um por
# tamanho. O apple-touch-icon continua em PNG, que é o que o Safari aceita.

$lado = 512.0

function GerarIcone([string]$destino, [double]$margemPct, [string]$titulo) {
    $margem = $script:lado * $margemPct
    $disponivel = $script:lado - $margem * 2
    $escala = $disponivel / $script:vbAlt
    $largura = $script:vbLarg * $escala
    $x = [math]::Round(($script:lado - $largura) / 2, 2)
    $y = [math]::Round($margem, 2)
    $e = [math]::Round($escala, 4)

    Gravar $destino @"
<!--
    $titulo

    Brasão do colégio sobre o navy institucional (#013d53, o navy-900 do
    design system). Gerado por scripts/gerar-marca.ps1 - não editar à mão.
-->
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="512" height="512">
  <defs><style>.st0{fill-rule:evenodd}</style></defs>
  <rect width="512" height="512" fill="#013d53"/>
  <g fill="#ffffff" transform="translate($x $y) scale($e) translate(-$script:vbX -$script:vbY)">
$script:brasao
  </g>
</svg>
"@
}

GerarIcone 'public\icons\notre-guard.svg' 0.10 'Ícone da PWA.'
GerarIcone 'public\icons\notre-guard-maskable.svg' 0.22 'Ícone maskable da PWA, com área segura de 22% que o recorte do Android exige.'

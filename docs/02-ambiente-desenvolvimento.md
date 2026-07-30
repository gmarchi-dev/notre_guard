# 02 - Ambiente de desenvolvimento

Mesmo ambiente do Portal de Segurança Digital: **Windows 11** com **Laravel Herd**
(PHP + Composer) e **MySQL 8.4** como processo standalone.

O projeto vive em `C:\claude\notre_guard`, ao lado de `C:\claude\seguranca_digital` - caminho
local, curto, sem espaços nem acentos e fora de qualquer pasta sincronizada. Isso evita o
atrito de Composer, npm e Vite com caminhos citados e a sobrecarga de sincronizar `vendor/`
e `node_modules/`.

## Componentes

| Ferramenta | Origem / caminho |
|---|---|
| Laravel Herd | `C:\Program Files\Herd` |
| PHP 8.4 | `C:\Users\<usuario>\.config\herd\bin\php84\php.exe` |
| Composer | `C:\Users\<usuario>\.config\herd\bin\composer.bat` (+ `.phar`) |
| MySQL 8.4 | `C:\Program Files\MySQL\MySQL Server 8.4\bin` |

> **Nota (PowerShell):** o PowerShell remove o `^` de constraints como `^3.0` ao repassar
> para o Composer. Use o Bash ou chame o Composer direto:
> `php "C:\Users\<usuario>\.config\herd\bin\composer.phar" require "vendor/pkg:^1.0"`.

## Banco de dados

O MySQL instalado via MSI não cria serviço. Iniciar como processo standalone:

```powershell
$bin = "C:\Program Files\MySQL\MySQL Server 8.4\bin"
Start-Process "$bin\mysqld.exe" -ArgumentList '--datadir="C:\Users\gmarchi\mysqldata"' -WindowStyle Hidden
```

Banco e usuário da aplicação:

```sql
CREATE DATABASE notre_guard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ng_app'@'localhost' IDENTIFIED BY 'ng_local_dev';
GRANT ALL PRIVILEGES ON notre_guard.* TO 'ng_app'@'localhost';
FLUSH PRIVILEGES;
```

Banco **separado** do `seguranca_digital` - os sistemas são independentes.

## `.env` previsto

```env
APP_NAME="Notre Guard"
APP_LOCALE=pt_BR
APP_TIMEZONE=America/Sao_Paulo

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=notre_guard
DB_USERNAME=ng_app
DB_PASSWORD=ng_local_dev
```

## Teste em dispositivo real

A PWA do vigilante exige **HTTPS** para câmera, geolocalização e service worker - inclusive
no celular durante o desenvolvimento. `php artisan serve` em HTTP não serve. Usar o domínio
`.test` com TLS do Herd (`herd secure`) e acessar do celular pela rede local, ou um túnel
(`herd share`). Sem isso não é possível testar leitura de QR Code no aparelho.

## Backup e versionamento

O projeto está fora do OneDrive, então não há cópia automática: o versionamento é
responsabilidade do Git. Inicializar o repositório antes de escrever a primeira linha de
código e definir o remoto institucional na Fase 1.

# 11 - Autenticação Google Workspace

**Estado: implementada e desligada.** `GOOGLE_AUTH_ENABLED=false`. Com o flag desligado, as
rotas devolvem 404 e o botão não aparece na tela de login - o acesso ao painel continua sendo
por e-mail e senha.

Não há nada a fazer no código para ativar: só as credenciais e o flag.

## Princípio

Autenticar no Google não é o mesmo que ter acesso ao Notre Guard. O Google diz **quem a pessoa
é**; o sistema decide **se ela entra**.

## Regras de aceite

| Situação | Resultado |
|---|---|
| Conta fora do domínio institucional | recusada |
| E-mail sem usuário cadastrado | **recusada** (não provisiona) |
| Usuário inativo | recusada |
| E-mail já vinculado a **outro** `google_id` | recusada |
| Usuário ativo e cadastrado | entra, e o `google_id` é vinculado no primeiro acesso |

**Não provisionar é decisão, não omissão.** Este é um sistema de segurança patrimonial: criar
acesso para qualquer pessoa do domínio que clicasse no botão abriria a operação para a escola
inteira. O administrador cria a conta em Configuração → Usuários; o Google apenas autentica quem
já existe.

Existe `GOOGLE_AUTH_ALLOW_PROVISIONING`, desligado. Se algum dia for ligado, a conta nasce com
perfil **vigilante** - que não acessa o painel. É o menor privilégio possível.

## Por que o `google_id`

O e-mail identifica, mas endereço institucional é reaproveitado: alguém sai, e meses depois o
mesmo `coordenacao@` é dado a outra pessoa. Sem o vínculo pelo id da conta Google, o sucessor
herdaria silenciosamente o acesso do antecessor.

O vínculo é gravado no primeiro login. Se um `google_id` diferente chegar para um e-mail já
vinculado, o login é recusado e a situação precisa ser resolvida pela administração - que é o
comportamento correto quando não se sabe se é a mesma pessoa.

## Convivência com a senha

A adoção é faseada, como no Portal de Segurança Digital: ligar o Google **não** desliga o login
por senha. Isso mantém um caminho de entrada se o OAuth falhar, e não impede o acesso do
administrador durante a transição.

Quando o Google estiver consolidado, desativar a senha é uma decisão à parte - hoje não há
mecanismo para isso, e desabilitar exigiria uma alteração no painel.

## O aplicativo de campo não usa Google

O vigilante entra com **matrícula e senha** no aparelho corporativo. Manter assim é deliberado:
matrícula é o que ele sabe de cor e o que está no crachá, e um fluxo OAuth num celular
compartilhado entre turnos criaria o risco de a sessão Google de um vigilante ficar aberta para
o próximo.

## Roteiro de ativação

1. No Google Cloud Console do domínio, criar um projeto e credenciais **OAuth 2.0 Client ID**
   do tipo *Web application*.
2. Cadastrar como *Authorized redirect URI* exatamente a URL pública do callback:
   `https://SEU-DOMINIO/auth/google/callback`. Precisa ser **HTTPS** em produção.
3. Na tela de consentimento, marcar o tipo como **Internal** - restringe ao domínio.
4. Preencher no `.env`:

```
GOOGLE_AUTH_ENABLED=true
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_HOSTED_DOMAIN=notredamecampinas.net.br
GOOGLE_REDIRECT_URI=https://SEU-DOMINIO/auth/google/callback
```

5. `php artisan config:clear`.
6. Garantir que os usuários existem em Configuração → Usuários, com o **mesmo e-mail** da conta
   Google.

O `hd` é enviado no redirect para reduzir o ruído do seletor de contas, mas não substitui a
validação de domínio no servidor - o parâmetro é do cliente e não é confiável.

## Implementação

| Arquivo | Papel |
|---|---|
| `config/google.php` | Flag, domínio e provisionamento |
| `app/Services/GoogleWorkspaceAuth.php` | Regras de aceite e vínculo |
| `app/Http/Controllers/Auth/GoogleController.php` | Redirect e callback; aborta 404 se desligado |
| `resources/views/auth/google-button.blade.php` | Botão, injetado por render hook |
| `tests/Feature/GoogleAuthTest.php` | 11 testes cobrindo aceite e recusa |

O botão entra por **render hook** (`AUTH_LOGIN_FORM_AFTER`), não por subclasse da página de
login do Filament: é um acréscimo, e substituir a página inteira criaria dívida a cada upgrade.
A condição fica dentro da closure, avaliada a cada render, então virar o flag basta.

## Pendências

- Login recusado registra `warning` no log com o motivo e o IP, mas não há tela de auditoria de
  tentativas.
- Não há revogação de vínculo pelo painel: desvincular um `google_id` hoje exige acesso ao banco.
- O aviso de recusa aparece na tela de login como erro do campo de e-mail, o que funciona mas
  não é o lugar mais óbvio.

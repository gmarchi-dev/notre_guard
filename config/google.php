<?php

/*
|--------------------------------------------------------------------------
| Autenticação Google Workspace
|--------------------------------------------------------------------------
|
| Desligada por padrão. Enquanto GOOGLE_AUTH_ENABLED=false, as rotas retornam
| 404 e o botão não aparece na tela de login - o acesso continua sendo por
| e-mail e senha.
|
| Para ligar: criar as credenciais OAuth no Google Cloud do domínio, preencher
| as variáveis e virar o flag. Ver docs/11-autenticacao-google.md.
|
*/

return [
    'enabled' => (bool) env('GOOGLE_AUTH_ENABLED', false),

    /*
     * Domínio institucional aceito. Login de conta fora dele é recusado, mesmo
     * que o Google autentique com sucesso - sem isso, qualquer conta Google do
     * mundo poderia tentar entrar.
     */
    'hosted_domain' => env('GOOGLE_HOSTED_DOMAIN', 'notredamecampinas.net.br'),

    /*
     * Provisionamento automático de conta no primeiro login.
     *
     * Fica FALSE de propósito: este é um sistema de segurança patrimonial, e
     * criar acesso para qualquer pessoa do domínio que clicar no botão seria
     * abrir a operação para a escola inteira. O administrador cria a conta
     * antes; o Google só autentica quem já existe.
     */
    'allow_provisioning' => (bool) env('GOOGLE_AUTH_ALLOW_PROVISIONING', false),
];

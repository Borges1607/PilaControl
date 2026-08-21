<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Mensagens de autenticação
|--------------------------------------------------------------------------
|
| O Fortify lê `auth.failed` e `auth.throttle` direto daqui.
|
| Nota: `failed` não diz qual dos dois campos errou, de propósito — é o que
| impede descobrir se um e-mail está cadastrado tentando entrar com ele.
|
*/

return [

    'failed' => 'E-mail ou senha incorretos.',
    'password' => 'A senha informada está incorreta.',
    'throttle' => 'Tentativas demais. Tente de novo em :seconds segundos.',

];

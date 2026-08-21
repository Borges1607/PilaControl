<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Mensagens da recuperação de senha
|--------------------------------------------------------------------------
|
| Devolvidas pelo password broker do Laravel nas telas de "Esqueci a senha" e
| "Redefinir senha".
|
| Nota: `sent` e `user` dizem a mesma coisa de propósito nas telas — o Fortify
| responde com `sent` mesmo para e-mail que não existe, para não revelar quem
| tem conta. `user` só aparece se essa proteção for desligada.
|
*/

return [

    'reset' => 'Sua senha foi redefinida.',
    'sent' => 'Enviamos o link de recuperação para o seu e-mail.',
    'throttled' => 'Aguarde um pouco antes de tentar de novo.',
    'token' => 'Este link de recuperação é inválido ou já expirou.',
    'user' => 'Não encontramos ninguém com esse e-mail.',

];

<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Mensagens de validação
|--------------------------------------------------------------------------
|
| Tradução das mensagens padrão do Laravel. O `:attribute` vem do nome do
| campo — as telas passam o nome em português no argumento `attributes:` do
| `$this->validate()`, e o que não passar cai no bloco `attributes` do fim
| deste arquivo.
|
| **Por que "O campo :attribute" e não ":attribute" direto.** Em português o
| adjetivo concorda em gênero com o substantivo, e o nome do campo entra como
| variável: ":attribute é obrigatório" viraria "descrição é obrigatório", que
| está errado. Concordando com "campo" — masculino, singular — a frase fecha
| para qualquer nome: "O campo descrição é obrigatório." É mais longa, e é o
| preço de não ter que declarar o gênero de cada campo do sistema.
|
| Pela mesma razão, onde a frase original começa pelo valor a tradução usa "O
| valor" ou "O valor selecionado" em vez do nome solto.
|
*/

return [

    'accepted' => 'O campo :attribute deve ser aceito.',
    'accepted_if' => 'O campo :attribute deve ser aceito quando :other é :value.',
    'active_url' => 'O campo :attribute deve ser uma URL válida.',
    'after' => 'O campo :attribute deve ser uma data posterior a :date.',
    'after_or_equal' => 'O campo :attribute deve ser uma data igual ou posterior a :date.',
    'alpha' => 'O campo :attribute só aceita letras.',
    'alpha_dash' => 'O campo :attribute só aceita letras, números, hífens e sublinhados.',
    'alpha_num' => 'O campo :attribute só aceita letras e números.',
    'any_of' => 'O campo :attribute é inválido.',
    'array' => 'O campo :attribute deve ser uma lista.',
    'array_keys' => 'O campo :attribute só aceita as chaves: :values.',
    'ascii' => 'O campo :attribute só aceita caracteres e símbolos de um byte.',
    'base64' => 'O campo :attribute deve ser um Base64 válido.',
    'before' => 'O campo :attribute deve ser uma data anterior a :date.',
    'before_or_equal' => 'O campo :attribute deve ser uma data igual ou anterior a :date.',
    'between' => [
        'array' => 'O campo :attribute deve ter entre :min e :max itens.',
        'file' => 'O campo :attribute deve ter entre :min e :max kilobytes.',
        'numeric' => 'O campo :attribute deve estar entre :min e :max.',
        'string' => 'O campo :attribute deve ter entre :min e :max caracteres.',
    ],
    'boolean' => 'O campo :attribute deve ser sim ou não.',
    'can' => 'O campo :attribute contém um valor não permitido.',
    'confirmed' => 'A confirmação do campo :attribute não corresponde.',
    'contains' => 'O campo :attribute não tem um valor obrigatório.',
    'current_password' => 'A senha informada está incorreta.',
    'date' => 'O campo :attribute não é uma data válida.',
    'date_equals' => 'O campo :attribute deve ser a data :date.',
    'date_format' => 'O campo :attribute deve seguir o formato :format.',
    'decimal' => 'O campo :attribute deve ter :decimal casas decimais.',
    'declined' => 'O campo :attribute deve ser recusado.',
    'declined_if' => 'O campo :attribute deve ser recusado quando :other é :value.',
    'different' => 'Os campos :attribute e :other devem ser diferentes.',
    'digits' => 'O campo :attribute deve ter :digits dígitos.',
    'digits_between' => 'O campo :attribute deve ter entre :min e :max dígitos.',
    'dimensions' => 'As dimensões da imagem em :attribute são inválidas.',
    'distinct' => 'O campo :attribute tem valor repetido.',
    'doesnt_contain' => 'O campo :attribute não pode conter: :values.',
    'doesnt_end_with' => 'O campo :attribute não pode terminar com: :values.',
    'doesnt_start_with' => 'O campo :attribute não pode começar com: :values.',
    'email' => 'O campo :attribute deve ser um e-mail válido.',
    'encoding' => 'O campo :attribute deve estar codificado em :encoding.',
    'ends_with' => 'O campo :attribute deve terminar com um destes: :values.',
    'enum' => 'O valor selecionado para :attribute é inválido.',
    'exists' => 'O valor selecionado para :attribute é inválido.',
    'extensions' => 'O campo :attribute deve ter uma destas extensões: :values.',
    'file' => 'O campo :attribute deve ser um arquivo.',
    'filled' => 'O campo :attribute não pode ficar em branco.',
    'gt' => [
        'array' => 'O campo :attribute deve ter mais de :value itens.',
        'file' => 'O campo :attribute deve ser maior que :value kilobytes.',
        'numeric' => 'O campo :attribute deve ser maior que :value.',
        'string' => 'O campo :attribute deve ter mais de :value caracteres.',
    ],
    'gte' => [
        'array' => 'O campo :attribute deve ter :value itens ou mais.',
        'file' => 'O campo :attribute deve ser maior ou igual a :value kilobytes.',
        'numeric' => 'O campo :attribute deve ser maior ou igual a :value.',
        'string' => 'O campo :attribute deve ter :value caracteres ou mais.',
    ],
    'hex_color' => 'O campo :attribute deve ser uma cor hexadecimal válida.',
    'image' => 'O campo :attribute deve ser uma imagem.',
    'in' => 'O valor selecionado para :attribute é inválido.',
    'in_array' => 'O campo :attribute deve existir em :other.',
    'in_array_keys' => 'O campo :attribute deve conter ao menos uma destas chaves: :values.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'ip' => 'O campo :attribute deve ser um endereço IP válido.',
    'ipv4' => 'O campo :attribute deve ser um endereço IPv4 válido.',
    'ipv6' => 'O campo :attribute deve ser um endereço IPv6 válido.',
    'json' => 'O campo :attribute deve ser um JSON válido.',
    'list' => 'O campo :attribute deve ser uma lista.',
    'lowercase' => 'O campo :attribute deve estar em minúsculas.',
    'lt' => [
        'array' => 'O campo :attribute deve ter menos de :value itens.',
        'file' => 'O campo :attribute deve ser menor que :value kilobytes.',
        'numeric' => 'O campo :attribute deve ser menor que :value.',
        'string' => 'O campo :attribute deve ter menos de :value caracteres.',
    ],
    'lte' => [
        'array' => 'O campo :attribute não pode ter mais de :value itens.',
        'file' => 'O campo :attribute deve ser menor ou igual a :value kilobytes.',
        'numeric' => 'O campo :attribute deve ser menor ou igual a :value.',
        'string' => 'O campo :attribute não pode ter mais de :value caracteres.',
    ],
    'mac_address' => 'O campo :attribute deve ser um endereço MAC válido.',
    'max' => [
        'array' => 'O campo :attribute não pode ter mais de :max itens.',
        'file' => 'O campo :attribute não pode passar de :max kilobytes.',
        'numeric' => 'O campo :attribute não pode ser maior que :max.',
        'string' => 'O campo :attribute não pode ter mais de :max caracteres.',
    ],
    'max_digits' => 'O campo :attribute não pode ter mais de :max dígitos.',
    'mimes' => 'O campo :attribute deve ser um arquivo do tipo: :values.',
    'mimetypes' => 'O campo :attribute deve ser um arquivo do tipo: :values.',
    'min' => [
        'array' => 'O campo :attribute deve ter ao menos :min itens.',
        'file' => 'O campo :attribute deve ter ao menos :min kilobytes.',
        'numeric' => 'O campo :attribute deve ser ao menos :min.',
        'string' => 'O campo :attribute deve ter ao menos :min caracteres.',
    ],
    'min_digits' => 'O campo :attribute deve ter ao menos :min dígitos.',
    'missing' => 'O campo :attribute não pode ser enviado.',
    'missing_if' => 'O campo :attribute não pode ser enviado quando :other é :value.',
    'missing_unless' => 'O campo :attribute não pode ser enviado a não ser que :other seja :value.',
    'missing_with' => 'O campo :attribute não pode ser enviado junto com :values.',
    'missing_with_all' => 'O campo :attribute não pode ser enviado junto com :values.',
    'multiple_of' => 'O campo :attribute deve ser múltiplo de :value.',
    'not_in' => 'O valor selecionado para :attribute é inválido.',
    'not_regex' => 'O formato do campo :attribute é inválido.',
    'numeric' => 'O campo :attribute deve ser um número.',
    'password' => [
        'letters' => 'O campo :attribute deve conter ao menos uma letra.',
        'mixed' => 'O campo :attribute deve conter ao menos uma letra maiúscula e uma minúscula.',
        'numbers' => 'O campo :attribute deve conter ao menos um número.',
        'symbols' => 'O campo :attribute deve conter ao menos um símbolo.',
        'uncompromised' => 'O valor informado em :attribute apareceu em um vazamento de dados. Escolha outro.',
    ],
    'present' => 'O campo :attribute deve ser enviado.',
    'present_if' => 'O campo :attribute deve ser enviado quando :other é :value.',
    'present_unless' => 'O campo :attribute deve ser enviado a não ser que :other seja :value.',
    'present_with' => 'O campo :attribute deve ser enviado junto com :values.',
    'present_with_all' => 'O campo :attribute deve ser enviado junto com :values.',
    'prohibited' => 'O campo :attribute não é permitido.',
    'prohibited_if' => 'O campo :attribute não é permitido quando :other é :value.',
    'prohibited_if_accepted' => 'O campo :attribute não é permitido quando :other é aceito.',
    'prohibited_if_declined' => 'O campo :attribute não é permitido quando :other é recusado.',
    'prohibited_unless' => 'O campo :attribute não é permitido a não ser que :other esteja em :values.',
    'prohibits' => 'O campo :attribute impede o envio de :other.',
    'regex' => 'O formato do campo :attribute é inválido.',
    'required' => 'O campo :attribute é obrigatório.',
    'required_array_keys' => 'O campo :attribute deve conter entradas para: :values.',
    'required_if' => 'O campo :attribute é obrigatório quando :other é :value.',
    'required_if_accepted' => 'O campo :attribute é obrigatório quando :other é aceito.',
    'required_if_declined' => 'O campo :attribute é obrigatório quando :other é recusado.',
    'required_unless' => 'O campo :attribute é obrigatório a não ser que :other esteja em :values.',
    'required_with' => 'O campo :attribute é obrigatório junto com :values.',
    'required_with_all' => 'O campo :attribute é obrigatório junto com :values.',
    'required_without' => 'O campo :attribute é obrigatório quando :values não é enviado.',
    'required_without_all' => 'O campo :attribute é obrigatório quando nenhum de :values é enviado.',
    'same' => 'O campo :attribute deve ser igual a :other.',
    'size' => [
        'array' => 'O campo :attribute deve ter :size itens.',
        'file' => 'O campo :attribute deve ter :size kilobytes.',
        'numeric' => 'O campo :attribute deve ser :size.',
        'string' => 'O campo :attribute deve ter :size caracteres.',
    ],
    'starts_with' => 'O campo :attribute deve começar com um destes: :values.',
    'string' => 'O campo :attribute deve ser um texto.',
    'timezone' => 'O campo :attribute deve ser um fuso horário válido.',
    'unique' => 'O valor informado em :attribute já está em uso.',
    'uploaded' => 'Falha ao enviar o campo :attribute.',
    'uppercase' => 'O campo :attribute deve estar em maiúsculas.',
    'url' => 'O campo :attribute deve ser uma URL válida.',
    'ulid' => 'O campo :attribute deve ser um ULID válido.',
    'uuid' => 'O campo :attribute deve ser um UUID válido.',

    /*
    |--------------------------------------------------------------------------
    | Mensagens por campo
    |--------------------------------------------------------------------------
    |
    | Convenção "campo.regra" para o caso em que a mensagem genérica não serve.
    | Preferir o argumento `messages:` do `$this->validate()` quando a mensagem
    | é de uma tela só; aqui entra o que vale para o app inteiro.
    |
    */

    'custom' => [
        'password' => [
            'confirmed' => 'As senhas não conferem.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Nomes dos campos
    |--------------------------------------------------------------------------
    |
    | Substitui o nome técnico do campo na mensagem. Cobre os formulários do
    | starter kit, que validam pelos nomes em inglês — as telas do domínio
    | passam o nome em português na própria chamada de validação.
    |
    */

    'attributes' => [
        'code' => 'código',
        'current_password' => 'senha atual',
        'email' => 'e-mail',
        'name' => 'nome',
        'password' => 'senha',
        'password_confirmation' => 'confirmação da senha',
        'recovery_code' => 'código de recuperação',
        'token' => 'token',
    ],

];

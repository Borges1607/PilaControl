<?php

declare(strict_types=1);

use App\Models\User;

/**
 * Usuário que veio do Google e ainda não escolheu uma senha.
 */
function semSenha(): User
{
    return User::factory()->create(['password' => null, 'google_id' => '1234567890']);
}

it('desvia para a tela de senha quem ainda não definiu uma', function (): void {
    $this->actingAs(semSenha());

    $this->get(route('dashboard'))->assertRedirect(route('password.create'));
    $this->get(route('profile.edit'))->assertRedirect(route('password.create'));
});

it('renderiza a tela de definir senha', function (): void {
    $this->actingAs(semSenha());

    $this->get(route('password.create'))
        ->assertOk()
        ->assertSee('Só falta escolher uma senha', escape: false)
        ->assertSee('Salvar e continuar');
});

it('grava a senha e libera o resto do app', function (): void {
    $user = semSenha();

    $this->actingAs($user)
        ->post(route('password.store'), [
            'password' => 'senha-bem-comprida',
            'password_confirmation' => 'senha-bem-comprida',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(config('fortify.home'));

    expect($user->refresh()->password)->not->toBeNull();

    $this->get(route('dashboard'))->assertOk();
});

it('recusa senha sem confirmação', function (): void {
    $user = semSenha();

    $this->actingAs($user)
        ->post(route('password.store'), [
            'password' => 'senha-bem-comprida',
            'password_confirmation' => 'outra-coisa',
        ])
        ->assertSessionHasErrors('password');

    expect($user->refresh()->password)->toBeNull();
});

it('tira da tela quem já tem senha', function (): void {
    $this->actingAs(User::factory()->create());

    $this->get(route('password.create'))->assertRedirect(config('fortify.home'));
});

it('não deixa quem já tem senha trocá-la por aqui', function (): void {
    $user = User::factory()->create();
    $antes = $user->password;

    $this->actingAs($user)
        ->post(route('password.store'), [
            'password' => 'nova-senha-comprida',
            'password_confirmation' => 'nova-senha-comprida',
        ])
        ->assertRedirect(config('fortify.home'));

    expect($user->refresh()->password)->toBe($antes);
});

<?php

declare(strict_types=1);

namespace App\Livewire\Goals;

use App\Actions\Goals\CreateGoal;
use App\Actions\Goals\DeleteGoal;
use App\Actions\Goals\DepositIntoGoal;
use App\Models\Goal;
use App\Support\Money;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * @property-read Collection<int, Goal> $goals
 */
#[Title('Metas')]
class Index extends Component
{
    /**
     * Id da meta com o campo de aporte aberto.
     */
    public ?int $depositing = null;

    public string $depositValue = '';

    // Formulário do modal de nova meta.
    public string $formIcon = '🎯';

    public string $formName = '';

    public string $formTarget = '';

    public string $formCurrent = '';

    public string $formDeadline = '';

    /**
     * @return Collection<int, Goal>
     */
    #[Computed]
    public function goals(): Collection
    {
        return Auth::user()->goals()->byDeadline()->get();
    }

    public function startDeposit(int $goalId): void
    {
        $this->depositing = $goalId;
        $this->depositValue = '';

        $this->resetValidation();
    }

    public function cancelDeposit(): void
    {
        $this->depositing = null;
        $this->depositValue = '';

        $this->resetValidation();
    }

    public function saveDeposit(DepositIntoGoal $depositIntoGoal): void
    {
        if ($this->depositing === null) {
            return;
        }

        $this->validate([
            'depositValue' => ['required', 'numeric', 'gt:0', 'max:99999999'],
        ], attributes: ['depositValue' => 'valor']);

        $goal = $this->goal($this->depositing);

        $this->authorize('update', $goal);

        // O aporte não passa do alvo — a regra está na Action.
        $depositIntoGoal->handle($goal, Money::fromReais($this->depositValue));

        $this->cancelDeposit();

        unset($this->goals);
    }

    public function save(CreateGoal $createGoal): void
    {
        $validated = $this->validate([
            'formIcon' => ['required', 'string', 'max:8'],
            'formName' => ['required', 'string', 'max:255'],
            'formTarget' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'formCurrent' => ['nullable', 'numeric', 'min:0', 'lte:formTarget'],
            'formDeadline' => ['required', 'date', 'after:today'],
        ], attributes: [
            'formIcon' => 'ícone',
            'formName' => 'nome',
            'formTarget' => 'valor alvo',
            'formCurrent' => 'valor já guardado',
            'formDeadline' => 'prazo',
        ]);

        $createGoal->handle(
            user: Auth::user(),
            name: $validated['formName'],
            icon: $validated['formIcon'],
            target: Money::fromReais($validated['formTarget']),
            current: Money::fromReais($validated['formCurrent'] ?: 0),
            deadline: Date::parse($validated['formDeadline']),
        );

        $this->resetForm();

        unset($this->goals);

        Flux::modal('nova-meta')->close();
        Flux::toast(variant: 'success', text: 'Meta criada.');
    }

    public function delete(int $id, DeleteGoal $deleteGoal): void
    {
        $goal = $this->goal($id);

        $this->authorize('delete', $goal);

        $deleteGoal->handle($goal);

        if ($this->depositing === $id) {
            $this->cancelDeposit();
        }

        unset($this->goals);

        Flux::toast(variant: 'success', text: 'Meta removida.');
    }

    public function resetForm(): void
    {
        $this->formIcon = '🎯';
        $this->formName = '';
        $this->formTarget = '';
        $this->formCurrent = '';
        $this->formDeadline = '';

        $this->resetValidation();
    }

    /**
     * A meta sai da relação do usuário: id de fora não existe.
     */
    private function goal(int $id): Goal
    {
        return Auth::user()->goals()->findOrFail($id);
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire\Goals;

use App\Support\Demo\Goal;
use App\Support\DemoData;
use App\Support\Money;
use Flux\Flux;
use Illuminate\Support\Collection;
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
     * Metas criadas nesta visita.
     *
     * Enquanto a tabela `goals` não existe, o estado do componente é a única
     * memória possível. Ao criar o model: apagar estas três propriedades e ligar
     * o formulário a Actions\Goals\CreateGoal / UpdateGoal / DeleteGoal.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $added = [];

    /**
     * @var array<int, string>
     */
    public array $removed = [];

    /**
     * Aportes feitos nesta visita, em centavos, por id da meta.
     *
     * @var array<string, int>
     */
    public array $deposits = [];

    /**
     * Id da meta com o campo de aporte aberto.
     */
    public ?string $depositing = null;

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
        return DemoData::goals()
            ->concat($this->addedGoals())
            ->reject(fn (Goal $goal): bool => in_array($goal->id, $this->removed, true))
            ->map(fn (Goal $goal): Goal => $this->applyDeposit($goal))
            ->values();
    }

    public function startDeposit(string $goalId): void
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

    public function saveDeposit(): void
    {
        if ($this->depositing === null) {
            return;
        }

        $this->validate([
            'depositValue' => ['required', 'numeric', 'gt:0', 'max:99999999'],
        ], attributes: ['depositValue' => 'valor']);

        $goal = $this->goals->firstWhere('id', $this->depositing);

        if ($goal === null) {
            $this->cancelDeposit();

            return;
        }

        // O aporte não passa do alvo, como no protótipo.
        $room = max(0, $goal->target_cents - $goal->current_cents);
        $amount = min($room, Money::fromReais($this->depositValue)->cents);

        $this->deposits[$this->depositing] = ($this->deposits[$this->depositing] ?? 0) + $amount;

        $this->cancelDeposit();

        unset($this->goals);
    }

    public function save(): void
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

        $this->added[] = [
            'id' => 'new-'.count($this->added).'-'.Date::now()->getTimestamp(),
            'name' => $validated['formName'],
            'icon' => $validated['formIcon'],
            'target_cents' => Money::fromReais($validated['formTarget'])->cents,
            'current_cents' => Money::fromReais($validated['formCurrent'] ?: 0)->cents,
            'deadline' => $validated['formDeadline'],
        ];

        $this->resetForm();

        unset($this->goals);

        Flux::modal('nova-meta')->close();
        Flux::toast(variant: 'success', text: 'Meta criada.');
    }

    public function delete(string $id): void
    {
        $this->removed[] = $id;
        $this->added = array_values(array_filter(
            $this->added,
            fn (array $row): bool => $row['id'] !== $id,
        ));

        unset($this->deposits[$id]);
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
     * Nome do método importa: o Livewire trata `hydrate{Propriedade}` como hook de
     * ciclo de vida e tenta chamá-lo de fora. `hydrateAdded` colidiria com `$added`.
     *
     * @return Collection<int, Goal>
     */
    private function addedGoals(): Collection
    {
        return collect($this->added)->map(fn (array $row): Goal => new Goal(
            id: (string) $row['id'],
            name: (string) $row['name'],
            icon: (string) $row['icon'],
            target_cents: (int) $row['target_cents'],
            current_cents: (int) $row['current_cents'],
            deadline: Date::parse((string) $row['deadline'])->startOfDay(),
        ));
    }

    private function applyDeposit(Goal $goal): Goal
    {
        $deposited = $this->deposits[$goal->id] ?? 0;

        if ($deposited === 0) {
            return $goal;
        }

        return new Goal(
            id: $goal->id,
            name: $goal->name,
            icon: $goal->icon,
            target_cents: $goal->target_cents,
            current_cents: min($goal->target_cents, $goal->current_cents + $deposited),
            deadline: $goal->deadline,
        );
    }
}

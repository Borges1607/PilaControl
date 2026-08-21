<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\Transaction;
use App\Queries\BalanceTimeline;
use App\Queries\MonthlySummary;
use App\Queries\Results\CategorySpending;
use App\Queries\Results\MonthPoint;
use App\Queries\Results\PeriodSummary;
use App\Queries\SpendingByCategory;
use App\Support\Money;
use App\Support\MonthLabel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * @property-read string $month
 * @property-read PeriodSummary $summary
 * @property-read Money $accumulated
 * @property-read SupportCollection<int, MonthPoint> $timeline
 * @property-read SupportCollection<int, CategorySpending> $topExpenses
 * @property-read Collection<int, Transaction> $recent
 */
#[Title('Dashboard')]
class Index extends Component
{
    /**
     * Quantos lançamentos a lista do rodapé mostra.
     */
    private const RECENT = 8;

    #[Computed]
    public function month(): string
    {
        return MonthLabel::currentKey();
    }

    #[Computed]
    public function summary(): PeriodSummary
    {
        return (new MonthlySummary)->handle(Auth::user(), $this->month);
    }

    /**
     * Saldo de tudo que já foi lançado, sem recorte de mês.
     */
    #[Computed]
    public function accumulated(): Money
    {
        return (new MonthlySummary)->handle(Auth::user())->balance;
    }

    /**
     * @return SupportCollection<int, MonthPoint>
     */
    #[Computed]
    public function timeline(): SupportCollection
    {
        return (new BalanceTimeline)->handle(Auth::user());
    }

    /**
     * @return SupportCollection<int, CategorySpending>
     */
    #[Computed]
    public function topExpenses(): SupportCollection
    {
        return (new SpendingByCategory)->handle(Auth::user(), $this->month, limit: 5);
    }

    /**
     * @return Collection<int, Transaction>
     */
    #[Computed]
    public function recent(): Collection
    {
        return Auth::user()->transactions()
            // A tabela mostra ícone e pílula de cada linha.
            ->with('category')
            ->latestFirst()
            ->limit(self::RECENT)
            ->get();
    }
}

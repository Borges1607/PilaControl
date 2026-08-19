<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Queries\BalanceTimeline;
use App\Queries\MonthlySummary;
use App\Queries\Results\CategorySpending;
use App\Queries\Results\MonthPoint;
use App\Queries\Results\PeriodSummary;
use App\Queries\SpendingByCategory;
use App\Support\Demo\Transaction;
use App\Support\DemoData;
use App\Support\Money;
use App\Support\MonthLabel;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * @property-read Collection<int, Transaction> $transactions
 * @property-read string $month
 * @property-read PeriodSummary $summary
 * @property-read Money $accumulated
 * @property-read Collection<int, MonthPoint> $timeline
 * @property-read Collection<int, CategorySpending> $topExpenses
 * @property-read Collection<int, Transaction> $recent
 */
#[Title('Dashboard')]
class Index extends Component
{
    /**
     * Fonte de dados provisória. Trocar por Eloquent quando os models existirem —
     * as propriedades computadas abaixo não mudam.
     *
     * @return Collection<int, Transaction>
     */
    #[Computed]
    public function transactions(): Collection
    {
        return DemoData::transactions();
    }

    #[Computed]
    public function month(): string
    {
        return MonthLabel::currentKey();
    }

    #[Computed]
    public function summary(): PeriodSummary
    {
        return (new MonthlySummary)->handle($this->transactions, $this->month);
    }

    #[Computed]
    public function accumulated(): Money
    {
        return (new MonthlySummary)->handle($this->transactions)->balance;
    }

    /**
     * @return Collection<int, MonthPoint>
     */
    #[Computed]
    public function timeline(): Collection
    {
        return (new BalanceTimeline)->handle($this->transactions);
    }

    /**
     * @return Collection<int, CategorySpending>
     */
    #[Computed]
    public function topExpenses(): Collection
    {
        return (new SpendingByCategory)->handle($this->transactions, $this->month, limit: 5);
    }

    /**
     * @return Collection<int, Transaction>
     */
    #[Computed]
    public function recent(): Collection
    {
        return $this->transactions->take(8);
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RecurringBill;
use App\Models\Transaction;
use Carbon\Carbon;

class ProcessRecurringBills extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bills:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for recurring bills due today and create transactions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();

        // 1. Find active bills due today (or in the past if the cron missed a day)
        $bills = RecurringBill::where('is_active', true)
            ->whereDate('next_payment_date', '<=', $today)
            ->get();

        $count = 0;

        foreach ($bills as $bill) {
            // 2. Create the Transaction record
            Transaction::create([
                'user_id' => $bill->user_id,
                'amount' => $bill->amount, // Amount is already stored in base currency (IDR)
                'type' => $bill->type,
                'description' => $bill->description . ' (Recurring)',
                'category' => $bill->category,
                'created_at' => $bill->next_payment_date, // Backdate to the actual due date
            ]);

            // 3. Calculate the NEXT payment date
            $nextDate = Carbon::parse($bill->next_payment_date);
            
            switch ($bill->frequency) {
                case 'weekly':
                    $nextDate->addWeek();
                    break;
                case 'monthly':
                    $nextDate->addMonth();
                    break;
                case 'yearly':
                    $nextDate->addYear();
                    break;
            }

            // 4. Update the Recurring Bill with the new date
            $bill->update(['next_payment_date' => $nextDate]);
            
            $this->info("Processed: {$bill->description}");
            $count++;
        }

        $this->info("Success! Processed {$count} bills.");
    }
}
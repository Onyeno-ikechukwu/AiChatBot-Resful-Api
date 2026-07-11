<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiredSubscriptions extends Command
{
    protected $signature = 'subscriptions:check-expired';

    protected $description = 'Mark expired subscriptions as expired';

    public function handle()
    {
        $expiredPayments = Payment::where('status', 'successful')
            ->where('expires_at', '<=', now())
            ->get();

        if ($expiredPayments->isEmpty()) {
            $this->info('No expired subscriptions found.');
            return Command::SUCCESS;
        }

        foreach ($expiredPayments as $payment) {

            try {

                $payment->update([
                    'status' => 'expired'
                ]);

                // Optional notification
                // $payment->user->notify(new SubscriptionExpiredNotification());

                $this->info(
                    "Subscription expired for User #{$payment->user_id}"
                );

            } catch (\Exception $e) {

                Log::error(
                    "Failed to expire subscription for User {$payment->user_id}: "
                    . $e->getMessage()
                );

                $this->error(
                    "Failed User {$payment->user_id}"
                );
            }
        }

        return Command::SUCCESS;
    }
}
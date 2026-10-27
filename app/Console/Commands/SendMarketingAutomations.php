<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\MarketingTemplate;
use App\Models\CampaignLog;
use Carbon\Carbon;

class SendMarketingAutomations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marketing:send-automations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends automated birthday and anniversary greetings to customers.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        $this->info("Scanning for automations for date: " . $today->format('d M'));

        // 1. Birthday Greetings
        $this->sendBirthdayGreetings($today);

        // 2. Anniversary Greetings
        $this->sendAnniversaryGreetings($today);

        // 3. Post-Travel Feedback
        $this->sendPostTravelFeedback($today);

        $this->info('Automated sequences completed.');
    }

    private function sendPostTravelFeedback($today)
    {
        $yesterday = $today->copy()->subDay();
        
        // Find bookings that ended yesterday
        $bookings = \App\Models\Booking::where('travel_end_date', $yesterday->toDateString())
            ->where('status', 'confirmed')
            ->with('customer')
            ->get();

        if ($bookings->isEmpty()) {
            $this->comment('No bookings ended yesterday.');
            return;
        }

        $template = MarketingTemplate::where('name', 'LIKE', '%Feedback%')
            ->orWhere('name', 'LIKE', '%Post-Travel%')
            ->where('is_active', true)
            ->first();

        if (!$template) {
            $this->error('No active template found for "Feedback".');
            return;
        }

        foreach ($bookings as $booking) {
            if ($booking->customer) {
                $this->dispatchMessage($booking->customer, $template, 'Post-Travel Feedback');
            }
        }
    }

    private function sendBirthdayGreetings($today)
    {
        $customers = Customer::whereMonth('dob', $today->month)
            ->whereDay('dob', $today->day)
            ->where('is_active', true)
            ->get();

        if ($customers->isEmpty()) {
            $this->comment('No birthdays today.');
            return;
        }

        $template = MarketingTemplate::where('name', 'LIKE', '%Birthday%')
            ->where('is_active', true)
            ->first();

        if (!$template) {
            $this->error('No active template found for "Birthday".');
            return;
        }

        foreach ($customers as $customer) {
            $this->dispatchMessage($customer, $template, 'Birthday Trigger');
        }
    }

    private function sendAnniversaryGreetings($today)
    {
        $customers = Customer::whereMonth('anniversary_date', $today->month)
            ->whereDay('anniversary_date', $today->day)
            ->where('is_active', true)
            ->get();

        if ($customers->isEmpty()) {
            $this->comment('No anniversaries today.');
            return;
        }

        $template = MarketingTemplate::where('name', 'LIKE', '%Anniversary%')
            ->where('is_active', true)
            ->first();

        if (!$template) {
            $this->error('No active template found for "Anniversary".');
            return;
        }

        foreach ($customers as $customer) {
            $this->dispatchMessage($customer, $template, 'Anniversary Trigger');
        }
    }

    private function dispatchMessage($recipient, $template, $triggerName)
    {
        try {
            // Shortcode Replacement
            $placeholders = [
                '{customer_name}' => $recipient->name,
                '{email}' => $recipient->email,
                '{phone}' => $recipient->phone ?? '--',
            ];

            $content = str_replace(array_keys($placeholders), array_values($placeholders), $template->content);

            // In a real app, integrate Mail/SMS here
            
            CampaignLog::create([
                'recipient_email' => $recipient->email,
                'recipient_phone' => $recipient->phone,
                'status' => 'Sent',
                'error_message' => "Automated: {$triggerName} using template '{$template->name}'"
            ]);

            $this->info("Message sent to {$recipient->name} ({$triggerName})");
        } catch (\Exception $e) {
            $this->error("Failed to send to {$recipient->name}: " . $e->getMessage());
        }
    }
}

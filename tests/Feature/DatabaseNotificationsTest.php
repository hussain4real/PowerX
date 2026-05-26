<?php

use App\Models\User;
use Database\Seeders\PowerXAccessSeeder;
use Filament\Facades\Filament;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    $this->seed(PowerXAccessSeeder::class);
});

test('database notifications are stored for users and enabled in filament', function (): void {
    $user = User::factory()->create();

    $user->notify(new class extends Notification
    {
        /**
         * @return array<int, string>
         */
        public function via(object $notifiable): array
        {
            return ['database'];
        }

        /**
         * @return array<string, string>
         */
        public function toArray(object $notifiable): array
        {
            return [
                'title' => 'Payment proof uploaded',
                'body' => 'Finance can review the new proof from the admin panel.',
            ];
        }
    });

    $notification = $user->notifications()->firstOrFail();

    expect(Schema::hasTable('notifications'))->toBeTrue()
        ->and(Filament::getPanel('admin')->hasDatabaseNotifications())->toBeTrue()
        ->and($user->notifications()->count())->toBe(1)
        ->and($user->unreadNotifications()->count())->toBe(1)
        ->and($notification->data)->toMatchArray([
            'title' => 'Payment proof uploaded',
            'body' => 'Finance can review the new proof from the admin panel.',
        ]);
});

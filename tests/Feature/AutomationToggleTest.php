<?php

use App\Models\AutomationSetting;
use App\Models\User;

it('allows an authenticated user to toggle automation status', function () {
    $user = User::factory()->create();
    AutomationSetting::query()->create(['automation_enabled' => true]);

    $this->actingAs($user)
        ->patch(route('dashboard.automation.toggle'))
        ->assertRedirect(route('dashboard.overview'))
        ->assertSessionHas('status', 'Automatisasi scraping berhasil dinonaktifkan.');

    expect(AutomationSetting::query()->value('automation_enabled'))->toBeFalse();
});

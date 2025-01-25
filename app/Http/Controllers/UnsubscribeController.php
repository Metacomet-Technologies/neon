<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;

final class UnsubscribeController
{
    /**
     * Display the specified resource.
     */
    public function show(string $email): \Inertia\Response
    {
        return Inertia::render('Unsubscribe', ['email' => $email]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(string $email): \Illuminate\Http\RedirectResponse
    {
        $user = User::where('email', $email)
            ->first();

        if ($user) {
            $user->update(['is_on_mailing_list' => false]);
        }

        return redirect()->route('unsubscribe.show', ['email' => $email]);
    }
}

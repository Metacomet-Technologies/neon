@extends('mail.layouts.base', ['title' => 'Thank you for your purchase!'])

@section('title', 'Purchase Confirmation - Neon')

@section('content')
    @include('mail.partials.content-start', ['title' => '🎉 Thank you for your purchase!'])
        <p style="font-size: 18px; margin-bottom: 20px;">Your <strong>{{ ucfirst($license->type) }} License</strong> has been successfully activated and is ready to use.</p>

        <h2 style="color: #1B1B1B; font-family: 'Figtree', Arial, Helvetica, sans-serif; font-size: 20px; margin: 24px 0 12px 0;">Purchase Details</h2>
        <ul style="font-family: 'Figtree', Arial, Helvetica, sans-serif; line-height: 1.6; margin: 12px 0; padding-left: 20px;">
            <li><strong>License Type:</strong> {{ ucfirst($license->type) }}</li>
            <li><strong>Amount Paid:</strong> ${{ number_format($amountPaid / 100, 2) }} {{ $currency }}</li>
            <li><strong>License ID:</strong> {{ $license->id }}</li>
            <li><strong>Status:</strong> {{ ucfirst($license->status) }}</li>
        </ul>

        <h2 style="color: #1B1B1B; font-family: 'Figtree', Arial, Helvetica, sans-serif; font-size: 20px; margin: 24px 0 12px 0;">Next Steps</h2>
        <p>Your license is currently <strong>parked</strong> and ready to be assigned to a Discord server.</p>
    @include('mail.partials.content-end')

    @include('mail.partials.button', ['url' => $billingUrl, 'text' => 'Manage Your License'])

    @include('mail.partials.content-start', ['title' => 'How to Assign Your License'])
        <ol style="font-family: 'Figtree', Arial, Helvetica, sans-serif; line-height: 1.6; margin: 12px 0; padding-left: 20px;">
            <li>Visit your billing dashboard using the button above</li>
            <li>Find your new license in the "Licenses" section</li>
            <li>Select a Discord server from the dropdown</li>
            <li>Click "Assign to Guild" to activate it</li>
        </ol>

        <h2 style="color: #1B1B1B; font-family: 'Figtree', Arial, Helvetica, sans-serif; font-size: 20px; margin: 24px 0 12px 0;">Need Help?</h2>
        <p>If you have any questions about your license or need assistance with setup, feel free to reach out to our support team.</p>
    @include('mail.partials.content-end')

    @include('mail.partials.content-start', ['title' => 'Important Notes'])
        <ul style="font-family: 'Figtree', Arial, Helvetica, sans-serif; line-height: 1.6; margin: 12px 0; padding-left: 20px;">
            @if ($license->type === 'subscription')
            <li>Your subscription will renew automatically each month</li>
            <li>You can cancel anytime through the billing portal</li>
            @else
            <li>This is a one-time payment - no recurring charges</li>
            @endif
            <li>Licenses have a 30-day cooldown between server transfers</li>
            <li>Only one active license per Discord server is allowed</li>
        </ul>

        <p style="margin-top: 30px;">Thanks for choosing Neon!</p>

        <p style="margin-top: 20px; font-weight: bold;">Best regards,<br>The Neon Team</p>
    @include('mail.partials.content-end')
@endsection
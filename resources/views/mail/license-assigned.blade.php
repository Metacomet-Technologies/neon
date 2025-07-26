@extends('mail.layouts.base', ['title' => 'License Successfully Assigned'])

@section('title', 'License Assignment Confirmation - Neon')

@section('content')
    @include('mail.partials.content-start', ['title' => '✅ License Successfully Assigned'])
        <p style="font-size: 18px; margin-bottom: 20px;">Your <strong>{{ ucfirst($license->type) }} License</strong> has been assigned to <strong>{{ $guild->name }}</strong> and is now active!</p>

        <h2 style="color: #1B1B1B; font-family: 'Figtree', Arial, Helvetica, sans-serif; font-size: 20px; margin: 24px 0 12px 0;">Assignment Details</h2>
        <ul style="font-family: 'Figtree', Arial, Helvetica, sans-serif; line-height: 1.6; margin: 12px 0; padding-left: 20px;">
            <li><strong>License Type:</strong> {{ ucfirst($license->type) }}</li>
            <li><strong>License ID:</strong> {{ $license->id }}</li>
            <li><strong>Discord Server:</strong> {{ $guild->name }}</li>
            <li><strong>Server ID:</strong> {{ $guild->id }}</li>
            <li><strong>Assigned At:</strong> {{ $license->last_assigned_at->format('M j, Y \a\t g:i A') }}</li>
        </ul>

        <h2 style="color: #1B1B1B; font-family: 'Figtree', Arial, Helvetica, sans-serif; font-size: 20px; margin: 24px 0 12px 0;">What This Means</h2>
        <p>🎉 Your Discord server now has access to all premium Neon features!</p>

        <p><strong>Premium features include:</strong></p>
        <ul style="font-family: 'Figtree', Arial, Helvetica, sans-serif; line-height: 1.6; margin: 12px 0; padding-left: 20px;">
            <li>Advanced moderation commands</li>
            <li>Custom server management tools</li>
            <li>Priority support and updates</li>
            <li>And much more!</li>
        </ul>
    @include('mail.partials.content-end')

    @include('mail.partials.button', ['url' => $billingUrl, 'text' => 'View License Details'])

    @include('mail.partials.content-start', ['title' => 'Important Reminders'])
        <ul style="font-family: 'Figtree', Arial, Helvetica, sans-serif; line-height: 1.6; margin: 12px 0; padding-left: 20px;">
            <li><strong>Transfer Cooldown:</strong> You cannot reassign this license to another server for 30 days</li>
            <li><strong>One License Per Server:</strong> Each Discord server can only have one active license</li>
            <li><strong>Manage Anytime:</strong> Use your billing dashboard to park or transfer this license</li>
        </ul>

        <p>Need to make changes? Visit your billing dashboard using the button above.</p>

        <p style="margin-top: 30px;">Thanks for using Neon!</p>

        <p style="margin-top: 20px; font-weight: bold;">Best regards,<br>The Neon Team</p>
    @include('mail.partials.content-end')
@endsection
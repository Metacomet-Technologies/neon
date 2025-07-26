@extends('mail.layouts.base', ['title' => 'Welcome to Neon. The world\'s best Discord Bot.'])

@section('title', 'Welcome to Neon')

@section('content')
    @include('mail.partials.content-start', ['title' => 'Welcome to the Neon Community!'])
        <p style="font-size: 18px; margin-bottom: 20px;">Thank you for joining Neon! You're now part of a community that's revolutionizing Discord server management.</p>

        <h2 style="color: #1B1B1B; font-family: 'Figtree', Arial, Helvetica, sans-serif; font-size: 20px; margin: 24px 0 12px 0;">What's Next?</h2>
        <p>Get started with Neon by inviting our bot to your Discord server and exploring our powerful features:</p>

        <ul style="font-family: 'Figtree', Arial, Helvetica, sans-serif; line-height: 1.6; margin: 12px 0; padding-left: 20px;">
            <li><strong>Advanced Moderation:</strong> Keep your server safe with automated tools</li>
            <li><strong>Custom Commands:</strong> Create personalized interactions for your community</li>
            <li><strong>Server Analytics:</strong> Track engagement and growth metrics</li>
            <li><strong>Premium Features:</strong> Unlock additional capabilities with a license</li>
        </ul>

        <p>Ready to transform your Discord experience? Visit our dashboard to get started!</p>
    @include('mail.partials.content-end')

    @include('mail.partials.button', ['url' => route('home'), 'text' => 'Visit Dashboard'])

    @include('mail.partials.content-start', ['title' => 'Need Help?'])
        <p>If you have any questions or need assistance getting started, our support team is here to help.</p>

        <p>Join our community Discord server for:</p>
        <ul style="font-family: 'Figtree', Arial, Helvetica, sans-serif; line-height: 1.6; margin: 12px 0; padding-left: 20px;">
            <li>Live support from our team</li>
            <li>Tips and tricks from other users</li>
            <li>Early access to new features</li>
            <li>Community events and updates</li>
        </ul>

        <p style="margin-top: 30px;">Welcome aboard!</p>

        <p style="margin-top: 20px; font-weight: bold;">Best regards,<br>The Neon Team</p>
    @include('mail.partials.content-end')
@endsection
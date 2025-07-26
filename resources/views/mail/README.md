# Neon Mail Template System

This directory contains an extendable mail template system that provides consistent branding and styling across all email communications.

## Structure

```
mail/
├── layouts/
│   ├── base.blade.php      # Main template with header/footer
│   └── simple.blade.php    # Simple content-only template
├── partials/
│   ├── header.blade.php    # Header with Neon branding and logo
│   └── footer.blade.php    # Footer with links and unsubscribe
├── components/
│   ├── content.blade.php   # White content section wrapper
│   ├── button.blade.php    # Styled buttons with color variants
│   └── alert.blade.php     # Alert/notification boxes
└── templates/              # Your email templates go here
```

## Usage

### Basic Template

```blade
@extends('mail.layouts.base', ['title' => 'Your email subject line'])

@section('title', 'Page Title for Email Client')

@section('content')
    @include('mail.components.content', ['title' => 'Main Heading'])
        <p>Your email content here...</p>
        
        <h2>Subheading</h2>
        <p>More content...</p>
    @endinclude
    
    @include('mail.components.button', ['url' => 'https://example.com'])
        Click Me
    @endinclude
@endsection
```

### Simple Template

```blade
@extends('mail.layouts.simple', ['title' => 'Simple notification'])

@section('message')
    <h1>Simple Message</h1>
    <p>This uses the simple layout with just content.</p>
@endsection
```

## Components

### Content Component

Wraps content in a white rounded box with proper spacing.

```blade
@include('mail.components.content', ['title' => 'Optional Title'])
    <p>Your content here</p>
@endinclude
```

### Button Component

Creates styled buttons with different color variants.

```blade
@include('mail.components.button', [
    'url' => 'https://example.com',
    'color' => 'primary' // primary, secondary, danger, success
])
    Button Text
@endinclude
```

### Alert Component

Creates alert boxes for important notifications.

```blade
@include('mail.components.alert', ['type' => 'success'])
    Your license has been activated successfully!
@endinclude
```

Alert types: `info` (default), `success`, `warning`, `error`

## Styling

The base template includes:

- **Neon brand colors**: `#53eafd` (primary), `#1B1B1B` (dark)
- **Typography**: Gugi for brand, Figtree for content
- **Responsive design**: Mobile-optimized layouts
- **Email client compatibility**: Tested across major email clients

## Variables

Templates can receive these variables:

- `$email` - User's email address (for unsubscribe links)
- `$title` - Header subtitle text
- Any variables passed from your Mailable classes

## Migration from Laravel Mail Components

If you're migrating from `x-mail::message`, the equivalent structure is:

**Old:**
```blade
<x-mail::message>
# Heading

Content here

<x-mail::button :url="$url">
Button Text
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
```

**New:**
```blade
@extends('mail.layouts.base')

@section('content')
    @include('mail.components.content', ['title' => 'Heading'])
        <p>Content here</p>
    @endinclude
    
    @include('mail.components.button', ['url' => $url])
        Button Text
    @endinclude
    
    @include('mail.components.content')
        <p>Thanks,<br>The Neon Team</p>
    @endinclude
@endsection
```

## Best Practices

1. **Consistent branding**: Always use the base layout for branded emails
2. **Mobile-first**: Test your emails on mobile devices
3. **Accessibility**: Use proper heading hierarchy and alt text
4. **Content sections**: Wrap related content in `content` components
5. **Clear CTAs**: Use the button component for primary actions
6. **Unsubscribe**: The footer automatically includes unsubscribe links when `$email` is available

## Examples

See the existing templates for examples:
- `license-assigned-new.blade.php` - Complex email with multiple sections
- `license-purchased-new.blade.php` - Purchase confirmation with steps
- `welcome-email-new.blade.php` - Welcome email with community info
@props(['url', 'color' => 'primary', 'text'])

@php
$buttonColors = [
    'primary' => 'background-color: #53eafd; color: #1B1B1B;',
    'secondary' => 'background-color: #1B1B1B; color: #53eafd; border: 2px solid #53eafd;',
    'danger' => 'background-color: #ef4444; color: #ffffff;',
    'success' => 'background-color: #10b981; color: #ffffff;',
];

$buttonStyle = $buttonColors[$color] ?? $buttonColors['primary'];
@endphp

<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="margin: 20px 0;">
    <tr>
        <td align="center">
            <table border="0" cellpadding="0" cellspacing="0" role="presentation">
                <tr>
                    <td align="center" style="border-radius: 6px; {{ $buttonStyle }}">
                        <a href="{{ $url }}"
                           style="display: inline-block; padding: 12px 24px; {{ $buttonStyle }} text-decoration: none; font-weight: bold; font-family: 'Figtree', Arial, Helvetica, sans-serif; font-size: 16px; border-radius: 6px;"
                           target="_blank">
                            {{ $text }}
                        </a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
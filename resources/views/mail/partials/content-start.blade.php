@props(['title' => null])

<table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation" style="padding: 20px 0;">
    <tr>
        <td>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
                <tr>
                    <td class="content-white" style="background-color: #ffffff; color: #2D3A41; padding: 40px; border-radius: 8px; margin: 20px 0;">
                        @if ($title)
                        <h1 style="color: #1B1B1B; font-family: 'Figtree', Arial, Helvetica, sans-serif; font-size: 24px; margin: 0 0 16px 0; font-weight: bold;">{{ $title }}</h1>
                        @endif

                        <div style="font-family: 'Figtree', Arial, Helvetica, sans-serif; line-height: 1.6; color: #2D3A41;">
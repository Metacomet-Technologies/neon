<tr>
    <td valign="top">
        <table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
            <tr>
                <td style="padding: 0px 0px 0px 0px;">
                    <table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
                        <tr>
                            <td valign="top" class="pc-w520-padding-30-30-30-30 pc-w620-padding-35-35-35-35 neon-bg-dark"
                                style="padding: 40px 40px 40px 40px; height: unset; border-radius: 0px;">

                                <!-- Links Section -->
                                <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
                                    <tr>
                                        <td style="padding: 0px 0px 69px 0px;">
                                            <table class="pc-width-fill pc-w620-gridCollapsed-1" width="100%" border="0"
                                                cellpadding="0" cellspacing="0" role="presentation">
                                                <tr class="pc-grid-tr-first pc-grid-tr-last">
                                                    <!-- Website Link -->
                                                    <td class="pc-grid-td-first pc-w620-itemsSpacings-40-40" align="left" valign="top"
                                                        style="width: 50%; padding-top: 0px; padding-right: 20px; padding-bottom: 0px; padding-left: 0px;">
                                                        <table style="border-collapse: separate; border-spacing: 0; width: 100%;" width="100%"
                                                            border="0" cellpadding="0" cellspacing="0" role="presentation">
                                                            <tr>
                                                                <td class="pc-w620-halign-center pc-w620-valign-top" align="left" valign="top">
                                                                    <div class="pc-font-alt pc-w620-align-center" style="text-decoration: none;">
                                                                        <div style="font-size: 14px; line-height: 143%; text-align: left; text-align-last: left; color: #d8d8d8; letter-spacing: -0.2px; font-weight: 400;">
                                                                            <a href="{{ route('home') }}" class="neon-link pc-font-alt"
                                                                                style="line-height: 143%; letter-spacing: -0.2px; font-family: 'Figtree', Arial, Helvetica, sans-serif; font-size: 14px; font-weight: 500;">
                                                                                {{ route('home') }}
                                                                            </a>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>

                                                    <!-- Social Links -->
                                                    <td class="pc-grid-td-last pc-w620-itemsSpacings-40-40" align="left" valign="top"
                                                        style="width: 50%; padding-top: 0px; padding-right: 0px; padding-bottom: 0px; padding-left: 20px;">
                                                        <table style="border-collapse: separate; border-spacing: 0; width: 100%;" width="100%"
                                                            border="0" cellpadding="0" cellspacing="0" role="presentation">
                                                            <tr>
                                                                <td class="pc-w620-halign-center pc-w620-valign-top" align="right" valign="top">
                                                                    <table class="pc-w620-halign-center" align="right" width="100%" border="0"
                                                                        cellpadding="0" cellspacing="0" role="presentation" style="width: 100%;">
                                                                        <tr>
                                                                            <td class="pc-w620-halign-center" align="right" valign="top">
                                                                                <table class="pc-w620-halign-center" align="right" border="0"
                                                                                    cellpadding="0" cellspacing="0" role="presentation">
                                                                                    <tr>
                                                                                        <td class="pc-w620-valign-middle pc-w620-halign-center" align="right">
                                                                                            <a href="https://x.com/metacomet_tech">
                                                                                                <img src="https://cloudfilesdm.com/postcards/52f04ccb7ccb3a7cd6d84aeecdc63696.png"
                                                                                                    width="15" height="15"
                                                                                                    style="display: block; border: 0; outline: 0; line-height: 100%; -ms-interpolation-mode: bicubic; width: 15px; height: 15px;"
                                                                                                    alt="X" />
                                                                                            </a>
                                                                                        </td>
                                                                                    </tr>
                                                                                </table>
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>

                                <!-- Unsubscribe Link -->
                                <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
                                    <tr>
                                        <td align="center">
                                            <table class="pc-width-hug pc-w620-gridCollapsed-0" align="center" border="0"
                                                cellpadding="0" cellspacing="0" role="presentation">
                                                <tr class="pc-grid-tr-first pc-grid-tr-last">
                                                    <td class="pc-grid-td-first pc-grid-td-last pc-w620-itemsSpacings-10-0" valign="top"
                                                        style="padding-top: 0px; padding-right: 0px; padding-bottom: 0px; padding-left: 0px;">
                                                        <table style="border-collapse: separate; border-spacing: 0;" border="0"
                                                            cellpadding="0" cellspacing="0" role="presentation">
                                                            <tr>
                                                                <td align="center" valign="top">
                                                                    @if (isset($email))
                                                                    <a href="{{ route('unsubscribe.update', ['email' => $email]) }}" class="neon-link pc-font-alt"
                                                                        style="line-height: 143%; letter-spacing: -0.2px; font-family: 'Figtree', Arial, Helvetica, sans-serif; font-size: 14px; font-weight: 500;">
                                                                        Unsubscribe
                                                                    </a>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </td>
</tr>
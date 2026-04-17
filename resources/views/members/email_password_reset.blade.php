<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ trans('MembersBundle/Members.email_password_reset_subject') }}</title>
</head>
<body style="margin:0;padding:24px;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;color:#111111;">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;padding:32px 28px;box-sizing:border-box;">
        <div style="text-align:left;font-size:16px;font-weight:600;margin-bottom:28px;">{{ e($brand) }}</div>
        <h1 style="text-align:left;font-size:22px;font-weight:700;margin:0 0 20px;line-height:1.3;">{{ trans('MembersBundle/Members.email_password_reset_headline') }}</h1>
        <p style="text-align:left;font-size:16px;line-height:1.55;margin:0 0 28px;color:#333333;">{{ trans('MembersBundle/Members.email_password_reset_intro') }}</p>
        <div style="text-align:left;margin-bottom:28px;">
            <a href="{{ e($resetUrl) }}" style="display:inline-block;padding:14px 32px;border-radius:8px;background:#000000;color:#ffffff;text-decoration:none;font-weight:700;font-size:16px;">{{ trans('MembersBundle/Members.email_password_reset_button') }}</a>
        </div>
        <div style="height:1px;background:#e5e7eb;margin:0 0 20px;"></div>
        <p style="text-align:left;font-size:14px;line-height:1.6;margin:0 0 12px;color:#6b7280;">{{ trans('MembersBundle/Members.email_password_reset_expire_line', ['minutes' => $ttlMinutes]) }}</p>
        <p style="text-align:left;font-size:14px;line-height:1.6;margin:0;color:#6b7280;">{{ trans('MembersBundle/Members.email_password_reset_footer_hint') }}</p>
    </div>
</body>
</html>

<x-mail::message>
# Your verification code

Use this code to continue your instructor onboarding:

<x-mail::panel>
# {{ $code }}
</x-mail::panel>

It expires in 15 minutes. If it runs out, reload the onboarding page and a new
one will be sent.

If you were not expecting this, you can ignore it — nothing happens until the
code is used.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

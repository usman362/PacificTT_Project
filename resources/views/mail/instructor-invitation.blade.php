<x-mail::message>
# Instructor onboarding

{{ $invite->name ? 'Hello '.$invite->name.',' : 'Hello,' }}

You have been invited to join Pacific Trade Tech as an independent contractor
instructor at {{ $rate }} per scheduled instructional day.

<x-mail::button :url="$link">
Start onboarding
</x-mail::button>

When you open the link, a 6-digit verification code is sent to this address.
The link is for you alone, works once, and expires on
{{ $invite->expires_at->format('F j, Y') }}.

If you were not expecting this, you can ignore it.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

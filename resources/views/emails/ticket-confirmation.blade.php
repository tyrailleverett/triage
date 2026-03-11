@component('mail::message')
# We received your support request

Hi {{ $ticket->submitter_name }},

Thank you for reaching out. We've received your support request and will get back to you as soon as possible.

**Subject:** {{ $ticket->subject }}

@component('mail::panel')
{{ $ticket->messages->first()?->body ?? '' }}
@endcomponent

You can reply directly to this email to add more information to your ticket.

Thanks,
{{ config('app.name') }}
@endcomponent
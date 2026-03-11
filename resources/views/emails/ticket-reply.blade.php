@component('mail::message')
# Reply to your support request

Hi {{ $ticket->submitter_name }},

Our team has replied to your support request regarding **{{ $ticket->subject }}**.

@component('mail::panel')
{{ $message->body }}
@endcomponent

You can reply directly to this email to continue the conversation.

Thanks,
{{ config('app.name') }}
@endcomponent

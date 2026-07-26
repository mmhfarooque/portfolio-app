<x-mail::message>
{!! nl2br(e($replyMessage)) !!}

Mahmud Farooque<br>
[mfaruk.com](https://mfaruk.com)

---

**On {{ $contact->created_at->format('F j, Y') }}, you wrote:**

<x-mail::panel>
{!! nl2br(e($contact->message)) !!}
</x-mail::panel>
</x-mail::message>

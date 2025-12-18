<x-mail::message>
# New Contact Form Submission

You have received a new message from your photography portfolio website.

**From:** {{ $contact->name }}
**Email:** {{ $contact->email }}
**Subject:** {{ $contact->subject }}

---

## Message

{{ $contact->message }}

---

**Submitted:** {{ $contact->created_at->format('F j, Y \a\t g:i A') }}
**IP Address:** {{ $contact->ip_address }}

<x-mail::button :url="route('admin.contacts.show', $contact)">
View in Admin Panel
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

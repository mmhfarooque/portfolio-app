<x-mail::message>
# New Print Inquiry

You have received a print inquiry for one of your photographs.

## Customer Information

**Name:** {{ $contact->name }}
**Email:** {{ $contact->email }}

---

## Photo Details

**Title:** {{ $photo->title }}
@if($photo->category)
**Category:** {{ $photo->category->name }}
@endif

<x-mail::button :url="route('photos.show', $photo)">
View Photo
</x-mail::button>

---

## Inquiry Details

{{ $contact->message }}

---

**Submitted:** {{ $contact->created_at->format('F j, Y \a\t g:i A') }}

<x-mail::button :url="route('admin.contacts.show', $contact)">
View in Admin Panel
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

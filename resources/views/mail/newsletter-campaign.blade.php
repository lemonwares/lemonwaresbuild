<x-mail::message>
@if ($subscriberName)
Hi {{ $subscriberName }},
@else
Hello,
@endif

@foreach ($campaign->imageUrls() as $imageUrl)
![Campaign image]({{ $imageUrl }})

@endforeach

{!! nl2br(e($campaign->body)) !!}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

@props(['url'])
<tr>
<td class="header" style="padding: 25px 0; text-align: center;">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
<img
    src="{{ \App\Support\ZeptoMailSettings::logoUrl() }}"
    width="180"
    alt="{{ config('site.name', config('app.name')) }}"
    style="border: none; display: block; height: auto; margin: 0 auto; max-width: 180px; width: 180px;"
>
</a>
</td>
</tr>

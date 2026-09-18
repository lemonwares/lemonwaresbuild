@props(['url'])
<tr>
<td class="header" style="padding: 28px 0 18px; text-align: center; background-color: #fff6f6;">
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
<tr>
<td class="header-accent" style="background-color: #c51a13; height: 4px; line-height: 4px; font-size: 0;">&nbsp;</td>
</tr>
<tr>
<td style="height: 20px; line-height: 20px; font-size: 0; background-color: #fff6f6;">&nbsp;</td>
</tr>

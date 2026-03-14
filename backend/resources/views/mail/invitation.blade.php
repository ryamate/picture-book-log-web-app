<x-mail::message>
# {{ $familyName }} に招待されました

{{ $inviterName }} さんから「{{ $familyName }}」への招待が届きました。

以下のボタンをクリックして招待を受け入れてください。

<x-mail::button :url="$acceptUrl">
招待を受け入れる
</x-mail::button>

このリンクは7日間有効です。

心当たりがない場合は、このメールを無視してください。

{{ config('app.name') }}
</x-mail::message>

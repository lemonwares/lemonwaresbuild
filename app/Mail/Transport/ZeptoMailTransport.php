<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;

class ZeptoMailTransport extends AbstractTransport
{
    public function __construct(
        protected string $token,
        protected string $endpoint = 'https://api.zeptomail.com/v1.1/email',
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $from = $email->getFrom()[0] ?? null;
        if (! $from instanceof Address) {
            throw new \RuntimeException('ZeptoMail requires a From address.');
        }

        $payload = [
            'from' => [
                'address' => $from->getAddress(),
                'name' => $from->getName() ?: config('mail.from.name'),
            ],
            'to' => collect($email->getTo())
                ->map(fn (Address $address) => [
                    'email_address' => array_filter([
                        'address' => $address->getAddress(),
                        'name' => $address->getName() ?: null,
                    ]),
                ])
                ->values()
                ->all(),
            'subject' => $email->getSubject() ?? '',
        ];

        $html = $email->getHtmlBody();
        $text = $email->getTextBody();

        if (filled($html)) {
            [$html, $inlineImages] = $this->embedBrandedLogo((string) $html);
            $payload['htmlbody'] = $html;
            if ($inlineImages !== []) {
                $payload['inline_images'] = $inlineImages;
            }
        } elseif (filled($text)) {
            $payload['textbody'] = $text;
        } else {
            $payload['textbody'] = '';
        }

        $cc = $email->getCc();
        if ($cc !== []) {
            $payload['cc'] = collect($cc)
                ->map(fn (Address $address) => [
                    'email_address' => array_filter([
                        'address' => $address->getAddress(),
                        'name' => $address->getName() ?: null,
                    ]),
                ])
                ->values()
                ->all();
        }

        $bcc = $email->getBcc();
        if ($bcc !== []) {
            $payload['bcc'] = collect($bcc)
                ->map(fn (Address $address) => [
                    'email_address' => array_filter([
                        'address' => $address->getAddress(),
                        'name' => $address->getName() ?: null,
                    ]),
                ])
                ->values()
                ->all();
        }

        $replyTo = $email->getReplyTo();
        if ($replyTo !== []) {
            $payload['reply_to'] = collect($replyTo)
                ->map(fn (Address $address) => array_filter([
                    'address' => $address->getAddress(),
                    'name' => $address->getName() ?: null,
                ]))
                ->values()
                ->all();
        }

        $token = \App\Support\ZeptoMailSettings::normalizeToken($this->token);

        $response = Http::timeout(20)
            ->withHeaders([
                'Authorization' => 'Zoho-enczapikey '.$token,
                'Accept' => 'application/json',
            ])
            ->asJson()
            ->post($this->endpoint, $payload);

        if (! $response->successful()) {
            $detail = $this->formatError($response->json(), $response->body(), $response->status());

            Log::warning('ZeptoMail send failed', [
                'status' => $response->status(),
                'endpoint' => $this->endpoint,
                'from' => $from->getAddress(),
                'detail' => $detail,
            ]);

            throw new \RuntimeException('ZeptoMail send failed: '.$detail);
        }
    }

    /**
     * Swap remote logo URLs for a ZeptoMail CID and attach the branded image inline.
     *
     * @return array{0:string,1:list<array{content:string,mime_type:string,cid:string}>}
     */
    protected function embedBrandedLogo(string $html): array
    {
        $inline = \App\Support\ZeptoMailSettings::logoInlineImage();
        if ($inline === null) {
            return [$html, []];
        }

        $cidSrc = 'cid:'.$inline['cid'];
        $logoUrl = \App\Support\ZeptoMailSettings::logoUrl();

        if ($logoUrl !== '') {
            $html = str_replace($logoUrl, $cidSrc, $html);
        }

        $html = preg_replace(
            '#https?://[^"\'\s>]+lemonwareslogo\.(?:png|webp|jpe?g)#i',
            $cidSrc,
            $html,
        ) ?? $html;

        // Ensure the header always points at the inline asset even if URL variants differed.
        if (! str_contains($html, $cidSrc) && str_contains($html, 'lemonwareslogo')) {
            $html = preg_replace(
                '#src=(["\'])[^"\']*lemonwareslogo\.(?:png|webp|jpe?g)\1#i',
                'src=$1'.$cidSrc.'$1',
                $html,
            ) ?? $html;
        }

        return [$html, [$inline]];
    }

    /**
     * @param  array<string, mixed>|null  $json
     */
    protected function formatError(?array $json, string $body, int $status): string
    {
        $parts = [];

        $message = trim((string) data_get($json, 'error.message', ''));
        if ($message !== '') {
            $parts[] = $message;
        }

        $details = data_get($json, 'error.details');
        if (is_array($details)) {
            foreach ($details as $row) {
                $rowMessage = trim((string) data_get($row, 'message', ''));
                $target = trim((string) data_get($row, 'target', ''));
                if ($rowMessage !== '') {
                    $parts[] = $target !== '' ? "{$target}: {$rowMessage}" : $rowMessage;
                }
            }
        }

        $code = trim((string) data_get($json, 'error.code', ''));
        if ($code !== '') {
            $parts[] = 'code '.$code;
        }

        if ($parts === []) {
            $snippet = trim(strip_tags($body));
            if ($snippet !== '') {
                $parts[] = \Illuminate\Support\Str::limit($snippet, 240);
            } else {
                $parts[] = 'HTTP '.$status;
            }
        }

        return implode(' · ', $parts);
    }

    public function __toString(): string
    {
        return 'zeptomail';
    }
}

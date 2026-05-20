# GoodSender SDK for PHP

Official client library for the GoodSender email API. Package: `good-sender/php-sdk`

## Quick start

```php
<?php
require 'vendor/autoload.php';

use GoodSender\Api\EmailsApi;
use GoodSender\Api\DomainsApi;
use GoodSender\Configuration;
use GoodSender\Model\Address;
use GoodSender\Model\SendEmail;
use GoodSender\Model\SendEmailRequest;
use GuzzleHttp\Client;

$config = Configuration::getDefaultConfiguration()
    ->setHost('https://api.goodsender.com')
    ->setAccessToken('YOUR_API_KEY');

$emails  = new EmailsApi(new Client(), $config);
$domains = new DomainsApi(new Client(), $config);

$req = new SendEmailRequest(['emails' => [
    new SendEmail([
        'from'         => new Address(['email' => 'sender@example.com']),
        'to'           => [new Address(['email' => 'recipient@example.com'])],
        'subject'      => 'Hello',
        'text_content' => 'Body',
    ]),
]]);
$res = $emails->sendEmail($req);
echo "sent={$res->getSent()} declined={$res->getDeclined()}\n";
```

## Examples

### Send via a template

```php
use GoodSender\Model\TemplateEmailRequest;
use GoodSender\Model\TemplateEmailRequestTemplate;

$req = new TemplateEmailRequest([
    'from'     => new Address(['email' => 'sender@example.com']),
    'to'       => new Address(['email' => 'recipient@example.com']),
    'subject'  => 'Your OTP',
    'template' => new TemplateEmailRequestTemplate([
        'template_id' => 'otp_code',
        'variables'   => ['code' => '123456'],
    ]),
]);
$res = $emails->sendTemplateEmail($req);
echo 'status=' . $res->getStatus() . "\n";
```

### List domains

```php
$res = $domains->listDomains(50);
echo 'domains=' . count($res->getDomains()) . "\n";
```

### Check consent status

```php
$res = $emails->getEmailConsentStatus('user@example.com', 'example.com');
echo 'entries=' . count($res) . "\n";

// List all consents for a domain
$res = $emails->listEmailConsents('example.com', 50);
echo 'emails=' . count($res->getEmails() ?? []) . "\n";
```

## Documentation

- API reference: <https://goodsender.com/docs>
- OpenAPI spec: `openapi/goodsender.yaml` in this repo
- Conformance tests: `tests/`

## Development

- Regenerate from spec: `scripts/regen.sh` (preserves `tests/`, `.github/`, and hand-curated files per `.regen-ignore`)
- Run conformance tests against local mock: `tests/run.sh mock`
- Run conformance against real dev API: `tests/run.sh dev` (requires `tests/.env.dev`)

## License

MIT — see [LICENSE](LICENSE).

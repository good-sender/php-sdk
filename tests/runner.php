<?php
// Mock-mode smoke test for the PHP SDK.

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use GoodSender\Api\DomainsApi;
use GoodSender\Api\EmailsApi;
use GoodSender\Configuration;
use GoodSender\Model\Address;
use GoodSender\Model\ConsentEmailEntry;
use GoodSender\Model\ConsentEmailRequest;
use GoodSender\Model\SendEmail;
use GoodSender\Model\SendEmailRequest;
use GoodSender\Model\TemplateEmailRequest;
use GoodSender\Model\TemplateEmailRequestTemplate;
use GuzzleHttp\Client;

$baseUrl = getenv('BASE_URL') ?: 'http://localhost:4010';
$apiKey = getenv('GOODSENDER_API_KEY') ?: 'test-key';

$config = Configuration::getDefaultConfiguration()
    ->setHost($baseUrl)
    ->setAccessToken($apiKey);

$emails  = new EmailsApi(new Client(), $config);
$domains = new DomainsApi(new Client(), $config);

$results = [];

function run(string $method, callable $body, array &$results): void {
    try {
        $detail = $body();
        $results[] = [$method, true, $detail];
    } catch (\Throwable $ex) {
        $msg = substr($ex->getMessage(), 0, 200);
        $results[] = [$method, false, get_class($ex) . ': ' . $msg];
    }
}

run('sendEmail', function () use ($emails) {
    $req = new SendEmailRequest(['emails' => [
        new SendEmail([
            'from'         => new Address(['email' => 'sender@example.com']),
            'to'           => [new Address(['email' => 'recipient@example.com'])],
            'subject'      => 'Hello',
            'text_content' => 'Body',
        ]),
    ]]);
    $res = $emails->sendEmail($req);
    return sprintf('sent=%d declined=%d', $res->getSent(), $res->getDeclined());
}, $results);

run('sendTemplateEmail', function () use ($emails) {
    $req = new TemplateEmailRequest([
        'from'     => new Address(['email' => 'sender@example.com']),
        'to'       => new Address(['email' => 'recipient@example.com']),
        'subject'  => 'OTP',
        'template' => new TemplateEmailRequestTemplate([
            'template_id' => 'otp_code',
            'variables'   => ['code' => '123456'],
        ]),
    ]);
    $res = $emails->sendTemplateEmail($req);
    return 'status=' . $res->getStatus();
}, $results);

run('requestEmailConsent', function () use ($emails) {
    // PHP's generated ConsentEmailEntry is a regular model class that takes
    // an array, not a polymorphic oneOf wrapper. For the string variant the
    // SDK accepts the raw email directly in the emails array.
    $req = new ConsentEmailRequest([
        'domain' => 'example.com',
        'emails' => ['smoke-php@example.com'],
    ]);
    $res = $emails->requestEmailConsent($req);
    return 'emails=' . count($res->getEmails() ?? []);
}, $results);

run('getEmailConsentStatus', function () use ($emails) {
    $res = $emails->getEmailConsentStatus('user@example.com', 'example.com');
    return 'entries=' . count($res);
}, $results);

run('listEmailConsents', function () use ($emails) {
    $res = $emails->listEmailConsents('example.com', 50);
    return 'emails=' . count($res->getEmails() ?? []);
}, $results);

run('listDomains', function () use ($domains) {
    $res = $domains->listDomains(50);
    return 'domains=' . count($res->getDomains());
}, $results);

foreach ($results as [$method, $ok, $detail]) {
    $tag = $ok ? 'PASS' : 'FAIL';
    printf("%-4s  php     %-22s  %s\n", $tag, $method, $detail);
}
$failed = count(array_filter($results, fn($r) => !$r[1]));
$passed = count($results) - $failed;
echo "\n$passed passed, $failed failed\n";
exit($failed > 0 ? 1 : 0);

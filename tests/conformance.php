<?php
// State-aware conformance test for the PHP SDK against the real dev API.
// Mirrors tests/runners/node/conformance.ts (same scenario IDs).

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use GoodSender\Api\DomainsApi;
use GoodSender\Api\EmailsApi;
use GoodSender\ApiException;
use GoodSender\Configuration;
use GoodSender\Model\Address;
use GoodSender\Model\ConsentEmailRecipient;
use GoodSender\Model\ConsentEmailRequest;
use GoodSender\Model\SendEmail;
use GoodSender\Model\SendEmailRequest;
use GoodSender\Model\TemplateEmailRequest;
use GoodSender\Model\TemplateEmailRequestTemplate;
use GuzzleHttp\Client;

function require_env(string $key): string {
    $v = getenv($key);
    if ($v === false || $v === '') {
        fwrite(STDERR, "FATAL: $key is not set in .env.dev\n");
        exit(2);
    }
    return $v;
}

$BASE_URL = require_env('BASE_URL');
$API_KEY  = require_env('GOODSENDER_API_KEY');
$ALLOW_DESTRUCTIVE = getenv('ALLOW_DESTRUCTIVE') === '1';

$VERIFIED_DOMAIN   = require_env('VERIFIED_SENDER_DOMAIN');
$VERIFIED_EMAIL    = require_env('VERIFIED_SENDER_EMAIL');
$VERIFIED_NAME     = getenv('VERIFIED_SENDER_NAME') ?: 'GoodSender SDK Tests';
$UNVERIFIED_DOMAIN = require_env('UNVERIFIED_SENDER_DOMAIN');
$UNVERIFIED_EMAIL  = require_env('UNVERIFIED_SENDER_EMAIL');
$GRANTED_1 = require_env('RECIPIENT_GRANTED_1');
$GRANTED_2 = require_env('RECIPIENT_GRANTED_2');
$DENIED_1  = require_env('RECIPIENT_DENIED_1');
$DENIED_2  = require_env('RECIPIENT_DENIED_2');
$TEMPLATE_ID = require_env('TEMPLATE_ID');

$RUN_TAG = 'sdk-' . dechex(time()) . '-' . bin2hex(random_bytes(3));
$FRESH_1 = "$RUN_TAG-1@$VERIFIED_DOMAIN";
$FRESH_2 = "$RUN_TAG-2@$VERIFIED_DOMAIN";

$config = Configuration::getDefaultConfiguration()->setHost($BASE_URL)->setAccessToken($API_KEY);
$emails  = new EmailsApi(new Client(), $config);
$domains = new DomainsApi(new Client(), $config);

$results = [];

function record(array &$results, string $id, string $name, string $status, string $detail): void {
    $results[] = [$id, $name, $status, $detail];
}

function scenario(array &$results, string $id, string $name, callable $body): void {
    try {
        [$ok, $detail] = $body();
        record($results, $id, $name, $ok ? 'PASS' : 'FAIL', $detail);
    } catch (\Throwable $e) {
        record($results, $id, $name, 'FAIL', "unexpected: " . get_class($e) . ': ' . substr($e->getMessage(), 0, 160));
    }
}

function http_body(ApiException $e): string {
    return substr((string)$e->getResponseBody(), 0, 160);
}

// ─── Read-only (R1–R6) ─────────────────────────────────────────

scenario($results, 'R1', 'listDomains returns both fixtures with correct verification flags', function () use ($domains, $VERIFIED_DOMAIN, $UNVERIFIED_DOMAIN) {
    $res = $domains->listDomains(100);
    $byName = [];
    foreach ($res->getDomains() as $d) { $byName[$d->getDomain()] = $d; }
    $v = $byName[$VERIFIED_DOMAIN] ?? null;
    $u = $byName[$UNVERIFIED_DOMAIN] ?? null;
    if (!$v) return [false, "$VERIFIED_DOMAIN not in listDomains response"];
    if (!$u) return [false, "$UNVERIFIED_DOMAIN not in listDomains response"];
    if (!$v->getVerification()->getVerified()) return [false, "$VERIFIED_DOMAIN has verification.verified=false; should be true"];
    if ($u->getVerification()->getVerified())  return [false, "$UNVERIFIED_DOMAIN has verification.verified=true; should be false"];
    return [true, sprintf("domains=%d, verified=true, unverified=false", count($res->getDomains()))];
});

scenario($results, 'R2', 'getEmailConsentStatus returns granted for approved recipient', function () use ($emails, $GRANTED_1, $VERIFIED_DOMAIN) {
    $res = $emails->getEmailConsentStatus($GRANTED_1, $VERIFIED_DOMAIN);
    $entry = null;
    foreach ($res as $e) { if ($e->getDomain() === $VERIFIED_DOMAIN) { $entry = $e; break; } }
    if (!$entry) return [false, "no entry for domain=$VERIFIED_DOMAIN"];
    if ($entry->getConsentStatus() !== 'granted') return [false, 'consentStatus=' . $entry->getConsentStatus() . ', expected granted'];
    return [true, 'consentStatus=' . $entry->getConsentStatus()];
});

scenario($results, 'R3', 'getEmailConsentStatus returns denied for rejected recipient', function () use ($emails, $DENIED_1, $VERIFIED_DOMAIN) {
    $res = $emails->getEmailConsentStatus($DENIED_1, $VERIFIED_DOMAIN);
    $entry = null;
    foreach ($res as $e) { if ($e->getDomain() === $VERIFIED_DOMAIN) { $entry = $e; break; } }
    if (!$entry) return [false, "no entry for domain=$VERIFIED_DOMAIN"];
    if ($entry->getConsentStatus() !== 'denied') return [false, 'consentStatus=' . $entry->getConsentStatus() . ', expected denied'];
    return [true, 'consentStatus=' . $entry->getConsentStatus()];
});

scenario($results, 'R4', 'getEmailConsentStatus returns 404 for unknown recipient', function () use ($emails, $RUN_TAG, $VERIFIED_DOMAIN) {
    $probe = "$RUN_TAG-r4-probe@$VERIFIED_DOMAIN";
    try {
        $res = $emails->getEmailConsentStatus($probe, $VERIFIED_DOMAIN);
        return [false, "expected 404, got 200 with " . count($res) . " entries"];
    } catch (ApiException $e) {
        if ($e->getCode() === 404) return [true, "404 (probe=$probe)"];
        return [false, "expected 404, got " . $e->getCode() . ' ' . http_body($e)];
    }
});

scenario($results, 'R5', 'listEmailConsents for verified domain includes all 4 fixtures', function () use ($emails, $VERIFIED_DOMAIN, $GRANTED_1, $GRANTED_2, $DENIED_1, $DENIED_2) {
    $collected = [];
    $cursor = null;
    for ($p = 0; $p < 20; $p++) {
        $res = $emails->listEmailConsents($VERIFIED_DOMAIN, 100, $cursor);
        foreach ($res->getEmails() ?? [] as $e) { $collected[] = $e->getEmail(); }
        $cursor = $res->getNextCursor();
        if (!$cursor) break;
    }
    $missing = array_values(array_diff([$GRANTED_1, $GRANTED_2, $DENIED_1, $DENIED_2], $collected));
    if (empty($missing)) return [true, count($collected) . " entries scanned; all 4 fixtures present"];
    return [false, "missing from listEmailConsents: " . implode(', ', $missing)];
});

scenario($results, 'R6', 'listEmailConsents with consentStatus=granted filter excludes denied', function () use ($emails, $VERIFIED_DOMAIN, $GRANTED_1, $GRANTED_2, $DENIED_1, $DENIED_2) {
    $collected = []; $statuses = []; $cursor = null; $pages = 0;
    for ($p = 0; $p < 20; $p++) {
        $res = $emails->listEmailConsents($VERIFIED_DOMAIN, 100, $cursor, 'granted');
        $pages++;
        foreach ($res->getEmails() ?? [] as $e) {
            $collected[$e->getEmail()] = true;
            $statuses[$e->getConsentStatus()] = true;
        }
        $cursor = $res->getNextCursor();
        if (!$cursor) break;
    }
    $uniqStatuses = array_keys($statuses);
    if (count(array_diff($uniqStatuses, ['granted'])) > 0)
        return [false, 'filter leaked non-granted statuses: ' . implode(',', $uniqStatuses)];
    $missing = array_values(array_diff([$GRANTED_1, $GRANTED_2], array_keys($collected)));
    if (count($missing) > 0) {
        $sample = implode(', ', array_slice(array_keys($collected), 0, 5)) ?: '(none)';
        return [false, "filter returned " . count($collected) . " entries across $pages page(s); missing=" . implode(',', $missing) . "; sample=[$sample]"];
    }
    if (isset($collected[$DENIED_1]) || isset($collected[$DENIED_2])) return [false, 'denied fixtures leaked into granted filter'];
    return [true, count($collected) . " granted entries; denied fixtures absent"];
});

// ─── Destructive (D1–D6, E1–E5) ──────────────────────────────

if ($ALLOW_DESTRUCTIVE) {
    scenario($results, 'D1', 'sendEmail to 2 granted recipients delivers both', function () use ($emails, $VERIFIED_EMAIL, $VERIFIED_NAME, $GRANTED_1, $GRANTED_2, $RUN_TAG) {
        $req = new SendEmailRequest(['emails' => [new SendEmail([
            'from' => new Address(['email' => $VERIFIED_EMAIL, 'name' => $VERIFIED_NAME]),
            'to'   => [new Address(['email' => $GRANTED_1]), new Address(['email' => $GRANTED_2])],
            'subject' => "SDK conformance D1 $RUN_TAG",
            'text_content' => 'D1 — granted recipients',
        ])]]);
        $res = $emails->sendEmail($req);
        if ($res->getSent() === 2 && $res->getDeclined() === 0)
            return [true, 'sent=' . $res->getSent() . ' declined=' . $res->getDeclined()];
        return [false, 'sent=' . $res->getSent() . ' declined=' . $res->getDeclined() . ', expected 2/0'];
    });

    scenario($results, 'D2', 'sendEmail to 2 denied recipients declines both', function () use ($emails, $VERIFIED_EMAIL, $VERIFIED_NAME, $DENIED_1, $DENIED_2, $RUN_TAG) {
        $req = new SendEmailRequest(['emails' => [new SendEmail([
            'from' => new Address(['email' => $VERIFIED_EMAIL, 'name' => $VERIFIED_NAME]),
            'to'   => [new Address(['email' => $DENIED_1]), new Address(['email' => $DENIED_2])],
            'subject' => "SDK conformance D2 $RUN_TAG",
            'text_content' => 'D2',
        ])]]);
        $res = $emails->sendEmail($req);
        if ($res->getSent() === 0 && $res->getDeclined() === 2)
            return [true, 'sent=' . $res->getSent() . ' declined=' . $res->getDeclined()];
        return [false, 'sent=' . $res->getSent() . ' declined=' . $res->getDeclined() . ', expected 0/2'];
    });

    scenario($results, 'D3', 'sendEmail granted+denied mix splits correctly', function () use ($emails, $VERIFIED_EMAIL, $VERIFIED_NAME, $GRANTED_1, $DENIED_1, $RUN_TAG) {
        $req = new SendEmailRequest(['emails' => [new SendEmail([
            'from' => new Address(['email' => $VERIFIED_EMAIL, 'name' => $VERIFIED_NAME]),
            'to'   => [new Address(['email' => $GRANTED_1]), new Address(['email' => $DENIED_1])],
            'subject' => "SDK conformance D3 $RUN_TAG",
            'text_content' => 'D3',
        ])]]);
        $res = $emails->sendEmail($req);
        if ($res->getSent() === 1 && $res->getDeclined() === 1)
            return [true, 'sent=' . $res->getSent() . ' declined=' . $res->getDeclined()];
        return [false, 'sent=' . $res->getSent() . ' declined=' . $res->getDeclined() . ', expected 1/1'];
    });

    scenario($results, 'D4', 'sendTemplateEmail to granted recipient returns status=sent', function () use ($emails, $VERIFIED_EMAIL, $VERIFIED_NAME, $GRANTED_1, $TEMPLATE_ID, $RUN_TAG) {
        $req = new TemplateEmailRequest([
            'from' => new Address(['email' => $VERIFIED_EMAIL, 'name' => $VERIFIED_NAME]),
            'to'   => new Address(['email' => $GRANTED_1]),
            'subject' => "SDK conformance D4 $RUN_TAG",
            'template' => new TemplateEmailRequestTemplate(['template_id' => $TEMPLATE_ID, 'variables' => new \stdClass()]),
        ]);
        $res = $emails->sendTemplateEmail($req);
        return $res->getStatus() === 'sent' ? [true, 'status=' . $res->getStatus()] : [false, 'status=' . $res->getStatus() . ', expected sent'];
    });

    scenario($results, 'D5', 'sendTemplateEmail to denied recipient returns status=declined', function () use ($emails, $VERIFIED_EMAIL, $VERIFIED_NAME, $DENIED_1, $TEMPLATE_ID, $RUN_TAG) {
        $req = new TemplateEmailRequest([
            'from' => new Address(['email' => $VERIFIED_EMAIL, 'name' => $VERIFIED_NAME]),
            'to'   => new Address(['email' => $DENIED_1]),
            'subject' => "SDK conformance D5 $RUN_TAG",
            'template' => new TemplateEmailRequestTemplate(['template_id' => $TEMPLATE_ID, 'variables' => new \stdClass()]),
        ]);
        $res = $emails->sendTemplateEmail($req);
        return $res->getStatus() === 'declined' ? [true, 'status=' . $res->getStatus()] : [false, 'status=' . $res->getStatus() . ', expected declined'];
    });

    scenario($results, 'D6', 'requestEmailConsent registers 2 fresh addresses', function () use ($emails, $VERIFIED_DOMAIN, $FRESH_1, $FRESH_2) {
        $req = new ConsentEmailRequest([
            'domain' => $VERIFIED_DOMAIN,
            'emails' => [
                new ConsentEmailRecipient(['email' => $FRESH_1, 'name' => 'Fresh 1']),
                new ConsentEmailRecipient(['email' => $FRESH_2, 'name' => 'Fresh 2']),
            ],
        ]);
        $res = $emails->requestEmailConsent($req);
        $entries = $res->getEmails() ?? [];
        if (count($entries) === 2) {
            $statuses = array_map(fn($e) => $e->getConsentStatus(), $entries);
            sort($statuses);
            return [true, "2 fresh addresses; statuses=[" . implode(',', $statuses) . "] $FRESH_1 $FRESH_2"];
        }
        return [false, 'expected 2 entries in ConsentEmailResult.emails, got ' . count($entries)];
    });

    scenario($results, 'E1', 'sendEmail from unverified domain is rejected', function () use ($emails, $UNVERIFIED_EMAIL, $GRANTED_1, $RUN_TAG) {
        try {
            $req = new SendEmailRequest(['emails' => [new SendEmail([
                'from' => new Address(['email' => $UNVERIFIED_EMAIL]),
                'to'   => [new Address(['email' => $GRANTED_1])],
                'subject' => "SDK conformance E1 $RUN_TAG",
                'text_content' => 'should be rejected',
            ])]]);
            $res = $emails->sendEmail($req);
            return [false, "expected 4xx, got 200 sent=" . $res->getSent()];
        } catch (ApiException $e) {
            $c = $e->getCode();
            return ($c >= 400 && $c < 500) ? [true, "$c " . http_body($e)] : [false, "expected 4xx, got $c " . http_body($e)];
        }
    });

    scenario($results, 'E2', 'sendTemplateEmail from unverified domain is rejected', function () use ($emails, $UNVERIFIED_EMAIL, $GRANTED_1, $TEMPLATE_ID, $RUN_TAG) {
        try {
            $req = new TemplateEmailRequest([
                'from' => new Address(['email' => $UNVERIFIED_EMAIL]),
                'to'   => new Address(['email' => $GRANTED_1]),
                'subject' => "SDK conformance E2 $RUN_TAG",
                'template' => new TemplateEmailRequestTemplate(['template_id' => $TEMPLATE_ID, 'variables' => new \stdClass()]),
            ]);
            $res = $emails->sendTemplateEmail($req);
            return [false, "expected 4xx, got 200 status=" . $res->getStatus()];
        } catch (ApiException $e) {
            $c = $e->getCode();
            return ($c >= 400 && $c < 500) ? [true, "$c " . http_body($e)] : [false, "expected 4xx, got $c " . http_body($e)];
        }
    });

    scenario($results, 'E3', 'sendTemplateEmail with bogus template_id returns 404', function () use ($emails, $VERIFIED_EMAIL, $GRANTED_1, $RUN_TAG) {
        $bad = "$RUN_TAG-does-not-exist";
        try {
            $req = new TemplateEmailRequest([
                'from' => new Address(['email' => $VERIFIED_EMAIL]),
                'to'   => new Address(['email' => $GRANTED_1]),
                'subject' => "SDK conformance E3 $RUN_TAG",
                'template' => new TemplateEmailRequestTemplate(['template_id' => $bad, 'variables' => new \stdClass()]),
            ]);
            $res = $emails->sendTemplateEmail($req);
            return [false, "expected 404, got 200 status=" . $res->getStatus()];
        } catch (ApiException $e) {
            return $e->getCode() === 404 ? [true, '404 ' . http_body($e)] : [false, "expected 404, got " . $e->getCode() . ' ' . http_body($e)];
        }
    });

    scenario($results, 'E4', 'requestEmailConsent for unverified domain is rejected', function () use ($emails, $UNVERIFIED_DOMAIN, $RUN_TAG) {
        $fresh = "$RUN_TAG-e4-target@example.com";
        try {
            $req = new ConsentEmailRequest(['domain' => $UNVERIFIED_DOMAIN, 'emails' => [$fresh]]);
            $res = $emails->requestEmailConsent($req);
            return [false, "expected 4xx, got 200 emails=" . count($res->getEmails() ?? [])];
        } catch (ApiException $e) {
            $c = $e->getCode();
            return ($c >= 400 && $c < 500) ? [true, "$c " . http_body($e)] : [false, "expected 4xx, got $c " . http_body($e)];
        }
    });

    scenario($results, 'E5', 'listEmailConsents for non-existent domain', function () use ($emails, $RUN_TAG) {
        $bogus = "not-a-real-domain-$RUN_TAG.invalid";
        try {
            $res = $emails->listEmailConsents($bogus, 1);
            return [true, '200 emails=' . count($res->getEmails() ?? []) . ' (no error path for unknown domain)'];
        } catch (ApiException $e) {
            $c = $e->getCode();
            return ($c >= 400 && $c < 500) ? [true, "$c " . http_body($e)] : [false, "unexpected: $c " . http_body($e)];
        }
    });
} else {
    foreach ([
        ['D1', 'sendEmail to 2 granted recipients'],
        ['D2', 'sendEmail to 2 denied recipients'],
        ['D3', 'sendEmail granted+denied mix'],
        ['D4', 'sendTemplateEmail to granted'],
        ['D5', 'sendTemplateEmail to denied'],
        ['D6', 'requestEmailConsent for 2 fresh addresses'],
        ['E1', 'sendEmail from unverified domain rejected'],
        ['E2', 'sendTemplateEmail from unverified domain rejected'],
        ['E3', 'sendTemplateEmail with bogus template_id'],
        ['E4', 'requestEmailConsent for unverified domain rejected'],
        ['E5', 'listEmailConsents for non-existent domain'],
    ] as [$sid, $name]) {
        record($results, $sid, $name, 'SKIP', 'destructive — set ALLOW_DESTRUCTIVE=1');
    }
}

// ─── Report ──────────────────────────────────────────────────

foreach ($results as [$sid, $name, $status, $detail]) {
    printf("%-4s  php     %s  %-58s  %s\n", $status, $sid, substr($name, 0, 58), $detail);
}
$passed  = count(array_filter($results, fn($r) => $r[2] === 'PASS'));
$failed  = count(array_filter($results, fn($r) => $r[2] === 'FAIL'));
$skipped = count(array_filter($results, fn($r) => $r[2] === 'SKIP'));
echo "\n$passed passed, $failed failed, $skipped skipped\n";
if ($ALLOW_DESTRUCTIVE) {
    echo "\nDestructive run created consent records for cleanup:\n  $FRESH_1\n  $FRESH_2\n";
}
exit($failed > 0 ? 1 : 0);

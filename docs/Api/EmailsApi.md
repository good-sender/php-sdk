# GoodSender\EmailsApi

API to send and manage emails.

All URIs are relative to https://api.goodsender.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**getEmailConsentStatus()**](EmailsApi.md#getEmailConsentStatus) | **GET** /v1/emails/{email} | Get recipient consent status |
| [**listEmailConsents()**](EmailsApi.md#listEmailConsents) | **GET** /v1/emails | List email consent statuses |
| [**requestEmailConsent()**](EmailsApi.md#requestEmailConsent) | **POST** /v1/emails/consent | Request recipients&#39; consent to receive emails from your domain |
| [**sendEmail()**](EmailsApi.md#sendEmail) | **POST** /v1/emails/send | Send an email or a batch of emails |
| [**sendTemplateEmail()**](EmailsApi.md#sendTemplateEmail) | **POST** /v1/emails/template | Send a transactional email using a template |


## `getEmailConsentStatus()`

```php
getEmailConsentStatus($email, $domain): \GoodSender\Model\EmailAccount[]
```

Get recipient consent status

Retrieve the current consent status for an email address. Optionally filter by sender domain.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (ApiKey) authorization: bearerAuth
$config = GoodSender\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoodSender\Api\EmailsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$email = user@example.com; // string | Email address to look up.
$domain = example.com; // string | Optional sender domain to filter consent records by. When omitted, returns consent across all domains.

try {
    $result = $apiInstance->getEmailConsentStatus($email, $domain);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EmailsApi->getEmailConsentStatus: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **email** | **string**| Email address to look up. | |
| **domain** | **string**| Optional sender domain to filter consent records by. When omitted, returns consent across all domains. | [optional] |

### Return type

[**\GoodSender\Model\EmailAccount[]**](../Model/EmailAccount.md)

### Authorization

[bearerAuth](../../README.md#bearerAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `listEmailConsents()`

```php
listEmailConsents($domain, $limit, $cursor, $consent_status, $engagement_status): \GoodSender\Model\EmailListResponse
```

List email consent statuses

Retrieve a paginated list of email consent statuses for a domain.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (ApiKey) authorization: bearerAuth
$config = GoodSender\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoodSender\Api\EmailsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$domain = example.com; // string | Sender domain to filter consent records by.
$limit = 50; // int | Maximum number of records to return.
$cursor = 'cursor_example'; // string | Cursor for pagination.
$consent_status = 'consent_status_example'; // string | Status of the recipient's consent for receiving emails. 'pending' = awaiting consent email send, 'requested' = consent email dispatched, 'failed' = consent email delivery failed, 'granted' = recipient consented to receive emails, 'denied' = recipient declined to receive emails.
$engagement_status = 'engagement_status_example'; // string | Status of the recipient's engagement with the emails.

try {
    $result = $apiInstance->listEmailConsents($domain, $limit, $cursor, $consent_status, $engagement_status);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EmailsApi->listEmailConsents: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **domain** | **string**| Sender domain to filter consent records by. | |
| **limit** | **int**| Maximum number of records to return. | [optional] [default to 50] |
| **cursor** | **string**| Cursor for pagination. | [optional] |
| **consent_status** | **string**| Status of the recipient&#39;s consent for receiving emails. &#39;pending&#39; &#x3D; awaiting consent email send, &#39;requested&#39; &#x3D; consent email dispatched, &#39;failed&#39; &#x3D; consent email delivery failed, &#39;granted&#39; &#x3D; recipient consented to receive emails, &#39;denied&#39; &#x3D; recipient declined to receive emails. | [optional] |
| **engagement_status** | **string**| Status of the recipient&#39;s engagement with the emails. | [optional] |

### Return type

[**\GoodSender\Model\EmailListResponse**](../Model/EmailListResponse.md)

### Authorization

[bearerAuth](../../README.md#bearerAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `requestEmailConsent()`

```php
requestEmailConsent($consent_email_request): \GoodSender\Model\ConsentEmailResult
```

Request recipients' consent to receive emails from your domain

Send a consent message to each address so recipients can approve or reject future emails from your domain. Include the email addresses in the request body to start the consent flow.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (ApiKey) authorization: bearerAuth
$config = GoodSender\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoodSender\Api\EmailsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$consent_email_request = new \GoodSender\Model\ConsentEmailRequest(); // \GoodSender\Model\ConsentEmailRequest

try {
    $result = $apiInstance->requestEmailConsent($consent_email_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EmailsApi->requestEmailConsent: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **consent_email_request** | [**\GoodSender\Model\ConsentEmailRequest**](../Model/ConsentEmailRequest.md)|  | |

### Return type

[**\GoodSender\Model\ConsentEmailResult**](../Model/ConsentEmailResult.md)

### Authorization

[bearerAuth](../../README.md#bearerAuth)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `sendEmail()`

```php
sendEmail($send_email_request): \GoodSender\Model\SendEmailResponse
```

Send an email or a batch of emails

Send one or more emails. Emails can be sent only to recipients who have opted in to receive communications from your domain. The response indicates how many emails were sent versus not sent, based on each recipient's consent state.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (ApiKey) authorization: bearerAuth
$config = GoodSender\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoodSender\Api\EmailsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$send_email_request = {"emails":[{"from":{"email":"sender@example.com","name":"Sender Name"},"to":[{"email":"recipient@example.com","name":"Recipient Name"}],"subject":"Test Email","text_content":"This is a test email"}]}; // \GoodSender\Model\SendEmailRequest | List of emails to send

try {
    $result = $apiInstance->sendEmail($send_email_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EmailsApi->sendEmail: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **send_email_request** | [**\GoodSender\Model\SendEmailRequest**](../Model/SendEmailRequest.md)| List of emails to send | |

### Return type

[**\GoodSender\Model\SendEmailResponse**](../Model/SendEmailResponse.md)

### Authorization

[bearerAuth](../../README.md#bearerAuth)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `sendTemplateEmail()`

```php
sendTemplateEmail($template_email_request): \GoodSender\Model\TemplateEmailResponse
```

Send a transactional email using a template

Send a transactional email using a predefined template for common use cases like OTP codes, order confirmations, and new device login alerts. If the recipient has \"denied\" consent, the response returns `{\"status\": \"declined\"}` and the email is not sent. Unknown recipients are auto-registered with \"pending\" consent. The template endpoint does not change the recipient's consent. Each email includes an approve/reject footer allowing the recipient to manage future communications. Provide the template ID and any variables to fill in the placeholders. All variables are optional and will be replaced with an empty string if omitted. URL-type variables must point to the same domain as the sender's email address.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (ApiKey) authorization: bearerAuth
$config = GoodSender\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoodSender\Api\EmailsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$template_email_request = {"from":{"email":"sender@example.com","name":"Sender Name"},"to":{"email":"recipient@example.com","name":"Recipient Name"},"subject":"Test Email","template":{"template_id":"otp_code","variables":{"app_name":"MyApp","otp_code":"482916"}}}; // \GoodSender\Model\TemplateEmailRequest | Template email to send

try {
    $result = $apiInstance->sendTemplateEmail($template_email_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EmailsApi->sendTemplateEmail: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **template_email_request** | [**\GoodSender\Model\TemplateEmailRequest**](../Model/TemplateEmailRequest.md)| Template email to send | |

### Return type

[**\GoodSender\Model\TemplateEmailResponse**](../Model/TemplateEmailResponse.md)

### Authorization

[bearerAuth](../../README.md#bearerAuth)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

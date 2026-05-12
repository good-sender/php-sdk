# GoodSender\DomainsApi

API to manage sender domains.

All URIs are relative to https://api.goodsender.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**listDomains()**](DomainsApi.md#listDomains) | **GET** /v1/domains | List domains |


## `listDomains()`

```php
listDomains($limit, $cursor): \GoodSender\Model\DomainListResponse
```

List domains

Retrieve a paginated list of sender domains for the workspace the API key belongs to. Each entry includes the domain's verification state so callers can detect when DNS records still need attention.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (ApiKey) authorization: bearerAuth
$config = GoodSender\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoodSender\Api\DomainsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$limit = 50; // int | Maximum number of records to return.
$cursor = 'cursor_example'; // string | Cursor for pagination, returned as `nextCursor` from a previous response.

try {
    $result = $apiInstance->listDomains($limit, $cursor);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling DomainsApi->listDomains: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **limit** | **int**| Maximum number of records to return. | [optional] [default to 50] |
| **cursor** | **string**| Cursor for pagination, returned as &#x60;nextCursor&#x60; from a previous response. | [optional] |

### Return type

[**\GoodSender\Model\DomainListResponse**](../Model/DomainListResponse.md)

### Authorization

[bearerAuth](../../README.md#bearerAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

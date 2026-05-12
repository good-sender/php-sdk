# DomainVerification

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**verified** | **bool** | Overall verification status. True only when every required DNS record is in place. |
**tracking_verified** | **bool** | Whether the tracking subdomain CNAME is in place. |
**return_path_verified** | **bool** | Whether the return-path subdomain CNAME is in place. |
**dkim1_verified** | **bool** | Whether the first DKIM record is in place. |
**dkim2_verified** | **bool** | Whether the second DKIM record is in place. |

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)

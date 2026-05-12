# QuotaExceededError

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**code** | **string** | Machine-readable error code. |
**kind** | **string** | Whether the daily or monthly quota was exhausted. |
**message** | **string** | Human-readable error message. |
**limit** | **int** | Quota limit that was reached. |
**used** | **int** | Number of emails already used against the quota. |
**reset_at** | **\DateTime** | Timestamp at which the quota window resets. |

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)

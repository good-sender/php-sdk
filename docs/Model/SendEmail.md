# SendEmail

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**from** | [**\GoodSender\Model\Address**](Address.md) | Sender address (required) |
**to** | [**\GoodSender\Model\Address[]**](Address.md) | To recipients. At least one recipient (to, cc, or bcc) is required. Maximum 1000 recipients per email. |
**subject** | **string** | The subject of the email (required) | [default to '']
**text_content** | **string** | Plain text content | [optional]
**html_content** | **string** | HTML content | [optional]
**markdown_content** | **string** | Markdown content. When provided, text_content and html_content are ignored. The raw markdown is used as text_content and rendered to HTML for html_content. | [optional]
**template_id** | **string** | Template ID for templated emails | [optional]
**template_data** | **array<string,mixed>** | Data to populate template variables | [optional]
**attachments** | [**\GoodSender\Model\Attachment[]**](Attachment.md) | Email attachments | [optional]
**headers** | **array<string,string>** | Custom email headers | [optional]
**reply_to** | [**\GoodSender\Model\Address**](Address.md) | Reply-to address | [optional]
**send_time** | **int** | Unix timestamp for when to send the email. Must not be more than 72 hours in the future. If 0, sends immediately. | [optional]
**webhook_data** | **array<string,string>** | Custom data to include in webhook events. Maximum 10 keys, key length 50 chars, value length 100 chars. | [optional]
**tag** | **string** | Custom tag for tracking. Maximum 100 characters. | [optional]
**tracking** | [**\GoodSender\Model\TrackingSettings**](TrackingSettings.md) | Email tracking settings | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)

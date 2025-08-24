# ESD Laravel WhatsApp Cloud

Laravel package to integrate with Meta WhatsApp Business Cloud API:
- Whatsapp Template Management
- Send all variation of whatsapp message (text, media, template, interactive, document, location)
- Multi-account (default auto)
- Broadcast with scheduling + queue
- Webhook status (sent, delivered, read, failed)

## Installation
1. `composer require elgibor-solution/laravel-whatsapp-cloud:*`
3. Publish config & migrations (optional):
```
php artisan vendor:publish --tag=whatsapp-config
php artisan vendor:publish --tag=whatsapp-migrations
php artisan migrate
```
4. .env example for single account:
```
WHATSAPP_SINGLE_ACCOUNT=true
WHATSAPP_NAME=default
WHATSAPP_PHONE_NUMBER_ID=YOUR_PHONE_ID
WHATSAPP_ACCESS_TOKEN=YOUR_LONG_LIVED_TOKEN
WHATSAPP_WABA_ID=YOUR_WABA_ID
WHATSAPP_WEBHOOK_VERIFY_TOKEN=change-me
```
5. Scheduler
Add to `routes/console.php`:
```php
  $schedule->command('whatsapp:broadcast-run')->everyMinute();
```


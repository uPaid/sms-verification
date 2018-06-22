SMS code verification
================

[![License: MIT](https://img.shields.io/badge/License-MIT-brightgreen.svg?style=flat-square)](https://opensource.org/licenses/MIT)
This package provides a set of tools for sending and verifying SMS codes.  

Installation
------------

This package can be installed through Composer:
```bash
composer require upaid/sms-verification
```
Or by adding the following line to the `require` section of your Laravel app's `composer.json` file:
```javascript
    "require": {
        "upaid/sms-verification": "0.1.*"
    }
```
Run `composer update upaid/sms-verification` to install the package.

Overview
------------

The core part of the package are so called "managers" - classes that implement the following interface:
```php
interface SmsManagerInterface
{
    public function sendSmsCode(string $action, string $phone, array $messageTranslationPlaceholders = []): string;
    public function checkSmsCode(string $action, string $phone, string $code): string;
    public function sendSmsAgain(string $action, string $phone, array $messageTranslationPlaceholders = []): string;
    public function flushPendingSmsValidation(string $id): void;
}
```
Short description below:
* *sendSmsCode* - generates code, stores it in cache in context of a given action and phone number, sends a text message with generated code and returns status of this operation
* *checkSmsCode* - checks if a given code is the same as the one stored in cache (in the same context) and returns status; in addition it is prepared to handle reaching the limit of failed attempts (you can define how to do that in config and in callbacks)
* *sendSmsAgain* - resets the counter of failed check attempts and then does the same as *sendSmsCode* (you can limitate usage of this method in config)
* *flushPendingSmsValidation* - removes all SMS verification keys from cache (can be used for example when user logs in)
Of course all of these subtasks are delegated to dependencies injected via interface, so you can modify most part of this packages without editing existing classes.

Configuration
------------

Package configuration is placed in *config/sms_verification.php* file. You have to copy this file to *config* folder in your Laravel project. In order to do that you can execute
```bash
php artisan vendor:publish --tag=config
```

Below is a list of configurable options: 
* *api* - SMS API connection settings
* *log_message* - templates used to log sent messages (if logging is configured)
* *status_map* - used for customising returned statuses
* *status_placeholders* - used for customizing the format of returned information about sent SMS number/count (if returned from SMS API)
* *multiLockTypes* - determines the list of available lock types used at *MultiTypeLockManager* class
* *cacheLifeTime* - how long items are stored in cache 
* *lockLifeTime* - lock duration (used in *BaseCacheLockManager*, not tightly related to the core functionality, but important if you use locks)
* *smsCodeLength* - length of generated SMS verification code
* *checksLimit* - max number of failed check attempts for one SMS code; reaching this limit may cause creating a lock or resending SMS code, depending on which manager you use
* *sendAgainLimit* - defines how many times *sendSmsAgain* feature can be used in given context. Used in *LimitedResendManager* class
* *actions* - list of available actions. Class *Components/Actions* should be overridden and extended in your project
* *translations* - translation configuration. If you use Laravel translator (*Components/Callbacks/MessageComposer*) pass to this parameter array with format [action => translation_key]. Also you can override *MessageComposer* implementation in your project
* *callbacks* - the list of configurable callbacks. You can define your own callback classes by implementing method *__invoke*
    * *dummyServices* - you have to pass callback class that makes a decision if a real SMS code should be generated and sent (or a dummy sender should be used, alternatively). Dummy sender logs the message without real sending (if logging is configured) 
    * *manager* - you have to pass callback that creates at instance of *SmsManagerInterface*
    * *log* - pass callback implementing logging
    * *overLimit* - this callback is executed when reaching *sendAgainLimit* in *LimitedResendManager*
    * *messageComposer* - responsible for composing and translating SMS message content 
    * *lockManager* - callback creates an instance of *LockManagerInterface* that can be used to lock user in cache or in DB, or to lock only some of the features (like password reset or email change)
    
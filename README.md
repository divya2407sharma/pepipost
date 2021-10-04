This project is a copy of [pepipost/pepipost-laravel-driver](https://github.com/pepipost/pepipost-laravel-driver), with some customizations to make it compatible with Laravel 5.2

# Laravel Driver for [Pepipost](http://www.pepipost.com/?utm_campaign=GitHubSDK&utm_medium=GithubSDK&utm_source=GithubSDK)

A Mail Driver with support for Pepipost Send Email Web API, using the original Laravel API. This library extends the original Laravel classes, so it uses exactly the same methods.

To use this package required your [Pepipost Api Key](https://app.pepipost.com). Please make it [Here](https://app.pepipost.com).


We are trying to make our libraries Community Driven- which means we need your help in building the right things in proper order we would request you to help us by sharing comments, creating new [issues](https://github.com/pepipost/laravel-pepipost-driver/issues) or [pull requests](https://github.com/pepipost/laravel-pepipost-driver/pulls).


We welcome any sort of contribution to this library.

The latest 1.0.0 version of this library provides is fully compatible with the latest Pepipost v2.0 API.

For any update of this library check [Releases](https://github.com/pepipost/laravel-pepipost-driver/releases).

# Table of Content
  
* [Installation](#installation)
* [Quick Start](#quick-start)
* [Usage of library in Project](#inproject)
* [Sample Example](#eg)
* [Announcements](#announcements)
* [Roadmap](#roadmap)
* [About](#about)
* [License](#license)

<a name="installation"></a>
# Installation

<a name="prereq"></a>

### Prerequisites

[PHP >= 5.5.9](https://www.php.net/manual/en/install.php)

[Laravel 5.2](https://laravel.com/docs/5.8/installation)

[guzzlehttp/guzzle ~5.3|~6.0](https://docs.guzzlephp.org/)

A free account on Pepipost. If you don't have a one, [click here](https://app.pepipost.com) to signup.

## Usage

### Configuring laravel project

#### Step 1 - Create New Laravel project 

```bash 
laravel new testproject
```

#### Step 2 - Add the package to your composer.json and run composer update.

```json

"require": {
    "iyogesharma/pepipost": "~1.0"
},
```
#### or install with composer

```bash
$ composer require iyogesharma/pepipost
```

#### Step 3 - Configurations 

1) Add the pepipost service provider in config/app.php: (Laravel 5.5+ uses Package Auto-Discovery, so doesn't require you to                manually add the ServiceProvider.)

    ```php

    'providers' => [
        YS\PepiPost\PepipostTransportServiceProvider::class
    ];
    ```

2) Add pepipost api key, endpoint in config/services.php


    ```php
        'pepipost' => [
            'api_key' => env('PEPIPOST_API_KEY'),
        ],
     ```
 

        endpoint config

        If you need to set custom endpoint, you can set any endpoint by using endpoint key.
        For example,calls to Pepipost Web API through a proxy,configure endpoint in config/services.php.

              
          'pepipost' => [
                  'api_key' => env('PEPIPOST_API_KEY'),
                  'endpoint' => 'https://api.pepipost.com/v2/sendEmail',
              ],

3) Add following in .env file

      ```dotenv
      MAIL_DRIVER=pepipost

      PEPIPOST_API_KEY='YOUR_PEPIPOST_API_KEY'
      ```

#### Step 4-  Laravel Steps to create controller and view

1) Define Controller

    ```bash

    php artisan make:controller TestController

    ```
2) create file in resources/views/viewname/name.blade.php 
    and include your email content 

    include following function sendMail in TestController to send
    viewname.name as content of email and initialize $data to use it on view page

      ```php
      function sendMail(){

      Mail::send('viewname.name',$data, function ($message) {
          $message
              ->to('foo@example.com', 'foo_name')
              ->from('sender@example.com', 'sender_name')
              ->subject('subject')
              ->cc('cc@example.com','recipient_cc_name')
              ->bcc('recipient_bcc@example.com','recipient_bcc_name')
              ->replyTo('reply_to@example.com','recipient_bcc')
              ->attach('/myfilename.pdf');
      });

      return 'Email sent successfully';
      }
      ```

3) Create Route in routes/web.php

      ```php

      Route::get('/send/email', 'TestController@sendMail')->name('sendEmail');

      ```

#### Step 5 - Testing

Host your laravel project and enter url- http://your_url.com/send/email in browser

This will send email and display Email sent successfully on browser.

#### Additional Usage

IF want to pass others parameters of PepiPost SendEmail API use embedData function and include below code as below
Add parameters as per your requirement. Do not use multiple to's,cc's,bcc's with this method.

```php
function sendMail(){

Mail::send('viewname.name',$data, function ($message) {
    $message
        ->to('foo@example.com', 'foo_name')
        ->from('sender@example.com', 'sender_name')
        ->subject('subject')
        ->cc('cc@example.com','recipient_cc_name')
        ->bcc('recipient_bcc@example.com','recipient_bcc_name')
        ->replyTo('reply_to@example.com','recipient_bcc')
        ->attach('/myfilename.pdf')
        ->embedData([
            'personalizations' => ['attributes'=>['ACCOUNT_BAL'=>'String','NAME'=>'NAME'],'x-apiheader'=>'x-apiheader_value','x-apiheader_cc'=>'x-apiheader_cc_value'],'settings' => ['bcc'=>'bccemail@gmail.com','clicktrack'=>1,'footer'=>1,'opentrack'=>1,'unsubscribe'=>1 ],'tags'=>'tags_value','templateId'=>''
        ],'pepipostapi');
        
 return 'Email sent successfully';
}       

```

For multiple to's,cc's,bcc's pass recipient,recipient_cc,recipient_bcc as below, create personalizations as required

```php


function sendMail(){

Mail::send('viewname.name',$data, function ($message) {
    $message
        ->from('sender@example.com', 'sender_name')
        ->subject('subject')
        ->replyTo('reply_to'@example.com,'recipient_bcc')
        ->attach('/myfilename.pdf')
        ->embedData([
                    'personalizations' => [['recipient'=>'foo@example.com','attributes'=>['ACCOUNT_BAL'=>'String','NAME'=>'name'],'recipient_cc'=>['cc@example.com','cc2@example.com'],'recipient_bcc'=>['bcc@example.com','bcc2@example.com'],'x-apiheader'=>'x-apiheader_value','x-apiheader_cc'=>'x-apiheader_cc_value'],['recipient'=>'foo@example.com','attributes'=>['ACCOUNT_BAL'=>'String','NAME'=>'name'],'x-apiheader'=>'x-apiheader_value','x-apiheader_cc'=>'x-apiheader_cc_value']],'settings' => ['bcc'=>'bccemail@gmail.com','clicktrack'=>1,'footer'=>1,'opentrack'=>1,'unsubscribe'=>1 ],'tags'=>'tags_value','templateId'=>''
                ],'pepipostapi');
        });
        
return 'Email sent successfully';
}

```
## License
[MIT](https://choosealicense.com/licenses/mit/)

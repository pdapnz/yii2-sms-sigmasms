# Yii2 SMS Twilio

A [SigmaSMS](https://sigmasms.ru/) plugin for Yii2 based extention [Yii2 SMS](https://github.com/wadeshuler/yii2-sms), to make sending SMS messages as easy as emails!

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/)

Either run

    composer require --prefer-dist pdapnz/yii2-sms-sigmasms

or add

    "pdapnz/yii2-sms-sigmasms": "~1.0"

to the require section of your application's `composer.json` file.

Then add a new `sms` component to your `main-local.php` (advanced) or `web.php` (basic) like so:

    'sms' => [
        'class' => 'pdapnz\sms\sigmasms\Sms',

        // Advanced app use '@common/sms', basic use '@app/sms'
        'viewPath' => '@common/sms',     // Optional: defaults to '@app/sms'

        // send all sms to a file by default. You have to set
        // 'useFileTransport' to false and configure the messageConfig['from'],
        'useFileTransport' => true,

        'messageConfig' => [
            'from' => 'B-Media',  // Your SigmaSMS upproved sender name
        ],

        // You username and password at https://online.sigmasms.ru/
        'username' => 'yourUsername',
        'password' => 'yourPassword',

    ],

## Usage

You can send SMS messages two ways. One uses a view file, just like how the mailer does, by passing it in the `compose()` call. Only difference is, you don't specify html/text array keys. Just pass the string, since text messages don't use html.


### With a view file

In your controller/model use it like so:

    Yii::$app->sms->compose('test-message', ['name' => 'Wade'])
        //->setFrom('12345')  // if not set in config, or to override
        ->setTo('+15558881234')
        ->send();

You will need a view file located where your `viewPath` points to. By default, it is `@app/sms`. You can see in the configuration above that we overrode it to `@common/sms`. This is similar to the location Yii2 Advanced uses for the email views, the "common" directory.

View File: `common/sms/test-message.php` (advanced) or `/sms/test-message.php` (basic)

```
Hello <?= $name ?> This is a test!

Thanks!
```

### Without a view file

When sending a large amount of text messages quickly, you may want to skip the view file for performance reasons, or you just may find it overkill for your usage.

    $name = "Wade";

    Yii::$app->sms->compose()
        //->setFrom('12345')  // optional if set in main config
        ->setTo('+15558881234')
        ->setMessage("Hey {$name} this is a test!")
        ->send();

`setMessage()` is a nicename function, or alias, for `setTextBody()`. Since SMS messages only deal with text and not html, I felt this was cleaner. However, `setTextBody()` will work just the same if your familiar with the way the mailer handles things. Either is fine.

## Determining a successful send

A boolean is returned, so it is simple:

    $result = Yii::$app->sms->compose()
        ->setTo('+15558881234')
        ->setMessage("Hey {$name} this is a test!")
        ->send();

    if ( $result === true ) {
        echo 'SMS was sent!';
    } else {
        'Error sending SMS!';
    }


## Do I have to call `setFrom()`?

You only have to use `setFrom()` if you did not specify a `from` number in the configuration, within the `messageConfig` array.

If you want to override the `from` address in your main configuration, then you can call `setFrom()` specifically.

If you only send from 1 number, just configure it and don't call it directly so you don't have to change it in a bunch of places later.

*Tip: If you have multuple numbers, use the Yii2 params array for an all-in-one easy spot to modify the numbers later.*


## Where can I find error logs?

When an Exception is hit during sending, they will be recorded under `@runtime/sms` and named according to the type of exception.


<?php
namespace YS\PepiPost;

class MailServiceProvider extends \Illuminate\Mail\MailServiceProvider
{
    public function register()
    {
        parent::register();

        $this->app->register(PepiPostTransportServiceProvider::class);
    }
}

<?php

namespace App\Mail;

use App\Traits\Meta;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;

class ResetMail extends Mailable
{
    use Queueable, SerializesModels, Meta;
    public $details;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($details)
    {
        $this->details = $details;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $name                  = $this->details->first_name . ' ' . $this->details->last_name;
        $id                    = Crypt::encryptString($this->details->id);
        $insertCryptIDUserMeta = ['reset_id' => $id];
        foreach ($insertCryptIDUserMeta as $key => $value) {
            $this->details->updateMeta($key, $value);
        }

        return $this->from("info@worldstudio.com", "World Studio")
            ->subject('Reset your password for World Studio')
            ->markdown('email.reset', ['id' => $id, 'name' => $name ?? '']);
    }
}

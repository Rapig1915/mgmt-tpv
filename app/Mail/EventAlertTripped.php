<?php

namespace App\Mail;

use App\Models\EventAlert;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class EventAlertTripped extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $alert;

    /**
     * Create a new message instance.
     *
     * @param EventAlert $alert the alert that was triggered
     *
     * @return void
     */
    public function __construct(EventAlert $alert)
    {
        $this->alert = $alert;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->from('no-reply@tpvhub.com');

        $subject = "";
        if (config('app.env') != 'production') {
            $subject .= "(" . config('app.env') . ") ";
        }

        if (
            $this->alert->event
            && $this->alert->event->brand
            && $this->alert->event->brand->name
        ) {
            $subject .= $this->alert->event->brand->name . " - ";
        }

        info("ALERT OBJECT is :" . json_encode($this->alert));

        $this->subject($subject . 'Alert: ' . $this->alert->client_alert->title);
        return $this
            ->view('emails.event-has-alert')
            ->with(['alert' => $this->alert]);
    }
}

<?php

namespace App\Mail;

use App\Models\Enquiry;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NewEnquiry extends Mailable
{
    public function __construct(public Enquiry $enquiry) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: ucfirst($this->enquiry->type).' enquiry from '.$this->enquiry->name,
            replyTo: [$this->enquiry->email],
        );
    }

    public function content(): Content
    {
        $e = $this->enquiry;
        $lines = array_filter([
            "Type: {$e->type}", "Name: {$e->name}", "Email: {$e->email}",
            $e->phone ? "Phone: {$e->phone}" : null, $e->company ? "Company: {$e->company}" : null,
            $e->city ? "City: {$e->city}" : null, '', $e->message,
        ], fn ($l) => $l !== null);

        return new Content(htmlString: '<pre style="font-family:inherit">'.e(implode("\n", $lines)).'</pre>');
    }
}

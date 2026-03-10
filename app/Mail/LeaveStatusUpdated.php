<?php

namespace App\Mail;

use App\Models\LeaveApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeaveStatusUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public $leave;
    public $remarks;
    public $approverName;

    /**
     * Create a new message instance.
     */
    public function __construct(LeaveApplication $leave, $remarks = null, $approverName = null)
    {
        $this->leave = $leave;
        $this->remarks = $remarks;
        $this->approverName = $approverName;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $status = strtoupper($this->leave->status);
        return new Envelope(
            subject: "Leave Application Update: #{$this->leave->id} - {$status}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.leave_status_updated', // We will create this view next
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}

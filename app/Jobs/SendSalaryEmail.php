<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SendSalaryEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email;
    protected $filePath;
    protected $fileName;
    protected $htmlContent;
    protected $subject;

    public function __construct($email, $filePath = null, $fileName = null, $htmlContent = null, $subject = null)
    {
        $this->email = $email;
        $this->filePath = $filePath;
        $this->fileName = $fileName;
        $this->htmlContent = $htmlContent;
        $this->subject = $subject;
    }

    public function handle()
    {
        if ($this->htmlContent) {
            Mail::send([], [], function ($message) {
                $message->to($this->email)
                    ->subject($this->subject ?? 'Salary Details')
                    ->html($this->htmlContent);
            });
        } else {
            Mail::send([], [], function ($message) {
                $message->to($this->email)
                    ->subject('Salary Sheet')
                    ->attach(Storage::path($this->filePath), [
                        'as' => $this->fileName
                    ]);
            });
        }
    }
}

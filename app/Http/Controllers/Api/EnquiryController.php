<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\NewEnquiry;
use App\Models\Enquiry;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnquiryController extends Controller
{
    public function contact(Request $request): JsonResponse
    {
        return $this->store($request, 'contact');
    }

    public function partner(Request $request): JsonResponse
    {
        return $this->store($request, 'partner');
    }

    private function store(Request $request, string $type): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:30'],
            'company' => ['nullable', 'string', 'max:160'],
            'city' => ['nullable', 'string', 'max:120'],
            'message' => [$type === 'contact' ? 'required' : 'nullable', 'string', 'max:3000'],
            'website' => ['nullable', 'max:0'], // honeypot: real visitors leave this empty; bots that fill it are rejected
        ]);

        $enquiry = Enquiry::create([
            'type' => $type,
            'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null,
            'company' => $data['company'] ?? null, 'city' => $data['city'] ?? null, 'message' => $data['message'] ?? null,
            'ip_address' => $request->ip(),
        ]);

        $settings = SiteSetting::first();
        $to = $type === 'partner' ? $settings?->partner_notify_email : $settings?->contact_notify_email;
        if ($to) {
            try {
                Mail::to($to)->send(new NewEnquiry($enquiry));
            } catch (\Throwable $e) {
                // The enquiry is saved; never fail the visitor because email is down.
                Log::warning('Enquiry notification failed: '.$e->getMessage());
            }
        }

        return response()->json(['message' => 'Thank you, we will be in touch.'], 201);
    }
}

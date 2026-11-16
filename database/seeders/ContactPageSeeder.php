<?php

namespace Database\Seeders;

use App\Models\{Faq, PageSection, SiteAsset, SiteSetting};

/**
 * Content for the Contact Us page. Unlike ContentSeeder this is ADDITIVE and safe to run on every deploy:
 * it only creates what is missing (sections by key, FAQ items only if there are none) and never overwrites edits.
 */
class ContactPageSeeder extends ContentSeeder
{
    public function run(): void
    {
        $this->src = rtrim(env('SEED_ASSETS_PATH', base_path('../nonstop/public/figma')), '/');

        $sections = [
            'contact_hero' => ['title' => 'CONTACT US', 'image' => $this->img('contact-hero')],
            'contact_info' => [
                'title' => 'CONTACT INFORMATION',
                'description' => 'A eleifend turpis sed orci est amet. Urna nulla ut nunc in fusce a. Lacinia nulla sapien malesuada amet tortor cras ut sapien. Lacinia nulla sapien malesuada amet tortor cras ut sapien.',
                'extra' => ['email_label' => 'Email:', 'phone_label' => 'Phone number:', 'location_label' => 'Location:'],
            ],
            'contact_form' => [
                'title' => 'Contact form',
                'button_label' => 'Submit Now',
                'extra' => [
                    'name_placeholder' => 'Name', 'surname_placeholder' => 'Surname',
                    'email_placeholder' => 'Email ID*', 'phone_placeholder' => 'Phone Number*', 'message_placeholder' => 'Message',
                    'consent_text' => 'Lacinia nulla sapien malesuada amet tortor',
                    'sending_text' => 'Sending...',
                    'success_message' => 'Thank you! Your message has been sent. We will get back to you soon.',
                    'error_message' => 'Sorry, something went wrong. Please try again.',
                    'required_message' => 'Please fill in this field.',
                    'email_invalid_message' => 'Please enter a valid email address.',
                    'consent_message' => 'Please tick the box to continue.',
                ],
            ],
            'faq' => ['title' => 'FREQUENTLY ASKED QUESTIONS', 'description' => 'A eleifend turpis sed orci est amet. Urna nulla ut nunc'],
            'marquee' => ['title' => 'Scrolling strip', 'extra' => ['phrases' => 'MAXIMUM EFFECT|NONSTOP ENERGY']],
        ];
        foreach ($sections as $key => $data) {
            PageSection::firstOrCreate(['key' => $key], $data);
        }

        if (! Faq::exists()) {
            $answer = 'A eleifend turpis sed orci est amet. Urna nulla ut nunc in fusce a. Lacinia nulla sapien malesuada amet tortor cras ut sapien. Metus nibh facilisis sed pellentesque adipiscing venenatis faucibus nisi.';
            foreach (range(1, 5) as $i) {
                Faq::create(['question' => 'A eleifend turpis sed orci est amet. Urna nulla ut nunc in fusce a ?', 'answer' => $answer, 'sort_order' => $i]);
            }
        }

        SiteSetting::query()->whereNull('address')->update(['address' => 'Full Address']);

        if ($this->img('blogs-bg')) {
            SiteAsset::firstOrCreate(['key' => 'faq_pattern'], [
                'label' => 'FAQ section background pattern', 'used_in' => 'Contact page, FAQ section',
                'image' => $this->img('blogs-bg'), 'sort_order' => 20,
            ]);
        }
    }
}

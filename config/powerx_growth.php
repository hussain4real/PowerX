<?php

return [
    'ai_assistant' => [
        'feature' => 'powerx-ai-assistant',
        'enabled' => env('POWERX_AI_ASSISTANT_ENABLED', false),
        'driver' => env('POWERX_AI_ASSISTANT_DRIVER', 'approved_knowledge'),
        'scope' => env('POWERX_AI_ASSISTANT_SCOPE', 'faq_course_recommendation_registration_help'),
        'handoff_follow_up_hours' => (int) env('POWERX_AI_HANDOFF_FOLLOW_UP_HOURS', 4),
        'approved_fallback' => 'PowerX can confirm that with the training team before making any certificate, government, legal, refund, pricing, or accreditation commitment.',
        'disclaimer' => 'PowerX guidance is based on approved public course and FAQ information. Final pricing, schedules, certificates, approvals, and legal terms must be confirmed by PowerX staff.',
        'faq' => [
            [
                'question' => 'Does PowerX guarantee exam results?',
                'answer' => 'No. PowerX provides structured preparation, practice, and instructor support, but final assessment results depend on the learner and the relevant authority requirements.',
                'tags' => ['exam', 'certificate', 'guarantee'],
            ],
            [
                'question' => 'Can companies train several employees at once?',
                'answer' => 'Yes. Corporate requests can include employee details, preferred schedules, course interest, and notes for quotation follow-up.',
                'tags' => ['corporate', 'batch', 'quotation'],
            ],
            [
                'question' => 'What happens after I submit an inquiry?',
                'answer' => 'The PowerX team reviews your course interest, preferred contact route, and timing, then follows up with next steps.',
                'tags' => ['registration', 'follow-up', 'admissions'],
            ],
        ],
        'blocked_topics' => [
            'accreditation',
            'certificate guarantee',
            'government approval',
            'kahramaa approval',
            'legal',
            'price guarantee',
            'refund',
            'qatar government',
        ],
    ],

    'campaigns' => [
        'default_currency' => env('POWERX_CAMPAIGN_DEFAULT_CURRENCY', 'QAR'),
        'attribution_status' => 'Internal CRM attribution with UTM, referral, campaign cost, and approved finance revenue matching.',
    ],

    'renewals' => [
        'campaign' => env('POWERX_RENEWAL_CAMPAIGN_NAME', 'certificate-renewal'),
        'source' => env('POWERX_RENEWAL_CAMPAIGN_SOURCE', 'renewal'),
    ],
];

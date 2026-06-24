<?php

return [
    'delivery' => [
        'email' => [
            'enabled' => env('POWERX_EMAIL_NOTIFICATIONS_ENABLED', true),
            'queue' => env('POWERX_EMAIL_QUEUE', 'mail'),
            'dispatch_limit' => (int) env('POWERX_EMAIL_DISPATCH_LIMIT', 100),
            'backoff' => array_values(array_filter(
                array_map('intval', explode(',', env('POWERX_EMAIL_RETRY_BACKOFF', '60,300,900'))),
                fn (int $seconds): bool => $seconds > 0,
            )) ?: [60, 300, 900],
        ],
        'whatsapp' => [
            'enabled' => env('POWERX_WHATSAPP_NOTIFICATIONS_ENABLED', false),
        ],
    ],

    'templates' => [
        'registration_confirmation' => [
            'subject' => 'PowerX registration received for {{ course_title }}',
            'message' => 'Hello {{ student_name }}, PowerX received your registration for {{ course_title }}. Our admissions team will confirm documents, payment, and schedule details.',
            'whatsapp' => 'Hello {{ student_name }}, PowerX received your registration for {{ course_title }}. We will confirm payment and schedule details shortly.',
        ],
        'enrollment_approved' => [
            'subject' => 'PowerX enrollment approved for {{ course_title }}',
            'message' => 'Hello {{ student_name }}, your PowerX enrollment for {{ course_title }} has been approved. Our team will confirm payment and access details.',
            'whatsapp' => 'Hello {{ student_name }}, your PowerX enrollment for {{ course_title }} has been approved. We will confirm payment and access details shortly.',
        ],
        'enrollment_rejected' => [
            'subject' => 'PowerX enrollment update for {{ course_title }}',
            'message' => 'Hello {{ student_name }}, PowerX reviewed your enrollment request for {{ course_title }} and cannot approve it right now. Note: {{ admission_note }}',
            'whatsapp' => 'PowerX enrollment update for {{ course_title }}: we cannot approve the request right now. Note: {{ admission_note }}',
        ],
        'enrollment_request_information' => [
            'subject' => 'More information needed for {{ course_title }}',
            'message' => 'Hello {{ student_name }}, PowerX needs more information before approving your enrollment for {{ course_title }}. Note: {{ admission_note }}',
            'whatsapp' => 'PowerX needs more information for {{ course_title }} before approval. Note: {{ admission_note }}',
        ],
        'payment_reminder' => [
            'subject' => 'Payment reminder for {{ course_title }}',
            'message' => 'Hello {{ student_name }}, your PowerX enrollment for {{ course_title }} is waiting for payment confirmation. Amount due: {{ amount_due }}.',
            'whatsapp' => 'PowerX reminder: {{ course_title }} payment is pending. Amount due: {{ amount_due }}.',
        ],
        'class_reminder' => [
            'subject' => 'Class reminder: {{ session_title }}',
            'message' => 'Hello {{ student_name }}, your PowerX session {{ session_title }} is scheduled for {{ session_time }} at {{ venue }}.',
            'whatsapp' => 'Reminder from PowerX: {{ session_title }} is on {{ session_time }} at {{ venue }}.',
        ],
        'class_schedule_changed' => [
            'subject' => 'PowerX class schedule update: {{ session_title }}',
            'message' => 'Hello {{ student_name }}, your PowerX session {{ session_title }} has moved from {{ previous_session_time }} to {{ new_session_time }} at {{ venue }}. Reason: {{ reason }}.',
            'whatsapp' => 'PowerX schedule update: {{ session_title }} is now on {{ new_session_time }} at {{ venue }}. Reason: {{ reason }}.',
        ],
        'class_cancelled' => [
            'subject' => 'PowerX class cancelled: {{ session_title }}',
            'message' => 'Hello {{ student_name }}, your PowerX session {{ session_title }} planned for {{ session_time }} has been cancelled. Reason: {{ reason }}. Our team will confirm the next step.',
            'whatsapp' => 'PowerX update: {{ session_title }} planned for {{ session_time }} has been cancelled. Reason: {{ reason }}.',
        ],
        'batch_transfer' => [
            'subject' => 'PowerX batch transfer for {{ course_title }}',
            'message' => 'Hello {{ student_name }}, your {{ course_title }} enrollment has been moved to batch {{ batch_name }}. First session: {{ first_session_time }} at {{ venue }}. Reason: {{ reason }}.',
            'whatsapp' => 'PowerX batch update: {{ course_title }} is now assigned to {{ batch_name }}. First session: {{ first_session_time }} at {{ venue }}.',
        ],
        'make_up_class' => [
            'subject' => 'PowerX make-up class assigned: {{ session_title }}',
            'message' => 'Hello {{ student_name }}, a make-up class {{ session_title }} has been assigned for {{ session_time }} at {{ venue }}. Reason: {{ reason }}.',
            'whatsapp' => 'PowerX make-up class: {{ session_title }} is scheduled for {{ session_time }} at {{ venue }}.',
        ],
        'certificate_issued' => [
            'subject' => 'Your PowerX certificate is ready',
            'message' => 'Congratulations {{ student_name }}. Your certificate {{ certificate_number }} for {{ course_title }} has been issued and can be verified online.',
            'whatsapp' => 'Congratulations {{ student_name }}. Your PowerX certificate {{ certificate_number }} for {{ course_title }} is ready.',
        ],
        'renewal_reminder' => [
            'subject' => 'PowerX renewal reminder for {{ course_title }}',
            'message' => 'Hello {{ student_name }}, your PowerX certificate for {{ course_title }} expires on {{ expiry_date }}. Contact us to plan renewal training.',
            'whatsapp' => 'PowerX renewal reminder: your {{ course_title }} certificate expires on {{ expiry_date }}.',
        ],
        'lead_follow_up' => [
            'subject' => 'PowerX course follow-up: {{ course_title }}',
            'message' => 'Hello {{ lead_name }}, thank you for your interest in {{ course_title }}. PowerX can help with course details, pricing, and schedule options.',
            'whatsapp' => 'Hello {{ lead_name }}, PowerX is following up on your interest in {{ course_title }}. Reply here for pricing and schedule options.',
        ],
    ],
];

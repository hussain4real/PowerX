<?php

return [
    'templates' => [
        'registration_confirmation' => [
            'subject' => 'PowerX registration received for {{ course_title }}',
            'message' => 'Hello {{ student_name }}, PowerX received your registration for {{ course_title }}. Our admissions team will confirm documents, payment, and schedule details.',
            'whatsapp' => 'Hello {{ student_name }}, PowerX received your registration for {{ course_title }}. We will confirm payment and schedule details shortly.',
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

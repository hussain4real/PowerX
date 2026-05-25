<?php

namespace App\Enums;

enum PowerXPermission: string
{
    case AdminAccess = 'admin.access';
    case ManageUsers = 'users.manage';
    case ManageRoles = 'roles.manage';
    case ManageLeads = 'leads.manage';
    case ManageRegistrations = 'registrations.manage';
    case ManageCourses = 'courses.manage';
    case ManageLearningContent = 'learning-content.manage';
    case ManageBatches = 'batches.manage';
    case ManageAttendance = 'attendance.manage';
    case ManageExams = 'exams.manage';
    case ManagePayments = 'payments.manage';
    case ManageCertificates = 'certificates.manage';
    case ViewReports = 'reports.view';
    case ManageCommunications = 'communications.manage';
    case ManageSupport = 'support.manage';
    case ManageSettings = 'settings.manage';

    public function label(): string
    {
        return match ($this) {
            self::AdminAccess => 'Access admin panel',
            self::ManageUsers => 'Manage users',
            self::ManageRoles => 'Manage roles and permissions',
            self::ManageLeads => 'Manage CRM leads',
            self::ManageRegistrations => 'Manage registrations',
            self::ManageCourses => 'Manage courses',
            self::ManageLearningContent => 'Manage learning content',
            self::ManageBatches => 'Manage batches',
            self::ManageAttendance => 'Manage attendance',
            self::ManageExams => 'Manage exams',
            self::ManagePayments => 'Manage payments',
            self::ManageCertificates => 'Manage certificates',
            self::ViewReports => 'View reports',
            self::ManageCommunications => 'Manage communications',
            self::ManageSupport => 'Manage support',
            self::ManageSettings => 'Manage settings',
        };
    }
}

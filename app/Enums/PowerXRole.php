<?php

namespace App\Enums;

enum PowerXRole: string
{
    case Management = 'management';
    case Admin = 'admin';
    case Sales = 'sales';
    case Finance = 'finance';
    case Instructor = 'instructor';
    case Student = 'student';
    case Corporate = 'corporate';
    case Support = 'support';

    public function label(): string
    {
        return match ($this) {
            self::Management => 'Management',
            self::Admin => 'Admin',
            self::Sales => 'Sales',
            self::Finance => 'Finance',
            self::Instructor => 'Instructor',
            self::Student => 'Student',
            self::Corporate => 'Corporate',
            self::Support => 'Support',
        };
    }

    /**
     * @return array<PowerXPermission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Management, self::Admin => PowerXPermission::cases(),
            self::Sales => [
                PowerXPermission::AdminAccess,
                PowerXPermission::ManageLeads,
                PowerXPermission::ManageRegistrations,
                PowerXPermission::ManageCommunications,
                PowerXPermission::ViewReports,
            ],
            self::Finance => [
                PowerXPermission::AdminAccess,
                PowerXPermission::ManageRegistrations,
                PowerXPermission::ManagePayments,
                PowerXPermission::ViewReports,
            ],
            self::Instructor => [
                PowerXPermission::AdminAccess,
                PowerXPermission::ManageCourses,
                PowerXPermission::ManageLearningContent,
                PowerXPermission::ManageBatches,
                PowerXPermission::ManageAttendance,
                PowerXPermission::ManageExams,
                PowerXPermission::ViewReports,
            ],
            self::Support => [
                PowerXPermission::AdminAccess,
                PowerXPermission::ManageLeads,
                PowerXPermission::ManageRegistrations,
                PowerXPermission::ManageCommunications,
                PowerXPermission::ManageSupport,
            ],
            self::Student, self::Corporate => [],
        };
    }
}

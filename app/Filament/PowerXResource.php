<?php

namespace App\Filament;

use App\Enums\PowerXPermission;
use App\Filament\Resources\AttendanceRecords\AttendanceRecordResource;
use App\Filament\Resources\AuditEvents\AuditEventResource;
use App\Filament\Resources\Certificates\CertificateResource;
use App\Filament\Resources\Communications\CommunicationResource;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\CourseModules\CourseModuleResource;
use App\Filament\Resources\CoursePackages\CoursePackageResource;
use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Filament\Resources\ExamAttempts\ExamAttemptResource;
use App\Filament\Resources\Exams\ExamResource;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\LessonProgress\LessonProgressResource;
use App\Filament\Resources\Lessons\LessonResource;
use App\Filament\Resources\PaymentTransactions\PaymentTransactionResource;
use App\Filament\Resources\Questions\QuestionResource;
use App\Filament\Resources\StudentProfiles\StudentProfileResource;
use App\Filament\Resources\TrainingBatches\TrainingBatchResource;
use App\Filament\Resources\TrainingSessions\TrainingSessionResource;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

abstract class PowerXResource extends Resource
{
    public static function canViewAny(): bool
    {
        return static::canManagePowerXResource();
    }

    public static function canCreate(): bool
    {
        return static::canManagePowerXResource();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canManagePowerXResource();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canManagePowerXResource();
    }

    public static function canDeleteAny(): bool
    {
        return static::canManagePowerXResource();
    }

    public static function canForceDelete(Model $record): bool
    {
        return static::canManagePowerXResource();
    }

    public static function canForceDeleteAny(): bool
    {
        return static::canManagePowerXResource();
    }

    public static function canRestore(Model $record): bool
    {
        return static::canManagePowerXResource();
    }

    public static function canRestoreAny(): bool
    {
        return static::canManagePowerXResource();
    }

    public static function canView(Model $record): bool
    {
        return static::canManagePowerXResource();
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return static::resourceConfiguration()['group'];
    }

    public static function getNavigationSort(): ?int
    {
        return static::resourceConfiguration()['sort'];
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return static::resourceConfiguration()['icon'];
    }

    protected static function canManagePowerXResource(): bool
    {
        return Auth::user()?->can(static::resourceConfiguration()['permission']->value) ?? false;
    }

    /**
     * @return array{permission: PowerXPermission, group: string, sort: int, icon: Heroicon}
     */
    protected static function resourceConfiguration(): array
    {
        return match (static::class) {
            AuditEventResource::class => [
                'permission' => PowerXPermission::ManageSettings,
                'group' => 'Settings',
                'sort' => 5,
                'icon' => Heroicon::OutlinedClipboardDocumentList,
            ],
            LeadResource::class => [
                'permission' => PowerXPermission::ManageLeads,
                'group' => 'Sales & CRM',
                'sort' => 10,
                'icon' => Heroicon::OutlinedUserGroup,
            ],
            CommunicationResource::class => [
                'permission' => PowerXPermission::ManageCommunications,
                'group' => 'Sales & CRM',
                'sort' => 20,
                'icon' => Heroicon::OutlinedChatBubbleLeftRight,
            ],
            CompanyResource::class => [
                'permission' => PowerXPermission::ManageRegistrations,
                'group' => 'Admissions',
                'sort' => 30,
                'icon' => Heroicon::OutlinedBuildingOffice2,
            ],
            StudentProfileResource::class => [
                'permission' => PowerXPermission::ManageRegistrations,
                'group' => 'Admissions',
                'sort' => 40,
                'icon' => Heroicon::OutlinedIdentification,
            ],
            EnrollmentResource::class => [
                'permission' => PowerXPermission::ManageRegistrations,
                'group' => 'Admissions',
                'sort' => 50,
                'icon' => Heroicon::OutlinedClipboardDocumentCheck,
            ],
            CourseResource::class => [
                'permission' => PowerXPermission::ManageCourses,
                'group' => 'Courses & LMS',
                'sort' => 60,
                'icon' => Heroicon::OutlinedAcademicCap,
            ],
            CoursePackageResource::class => [
                'permission' => PowerXPermission::ManageCourses,
                'group' => 'Courses & LMS',
                'sort' => 70,
                'icon' => Heroicon::OutlinedTag,
            ],
            CourseModuleResource::class => [
                'permission' => PowerXPermission::ManageLearningContent,
                'group' => 'Courses & LMS',
                'sort' => 80,
                'icon' => Heroicon::OutlinedRectangleGroup,
            ],
            LessonResource::class => [
                'permission' => PowerXPermission::ManageLearningContent,
                'group' => 'Courses & LMS',
                'sort' => 90,
                'icon' => Heroicon::OutlinedBookOpen,
            ],
            LessonProgressResource::class => [
                'permission' => PowerXPermission::ManageLearningContent,
                'group' => 'Courses & LMS',
                'sort' => 100,
                'icon' => Heroicon::OutlinedChartBar,
            ],
            TrainingBatchResource::class => [
                'permission' => PowerXPermission::ManageBatches,
                'group' => 'Training Operations',
                'sort' => 110,
                'icon' => Heroicon::OutlinedCalendarDays,
            ],
            TrainingSessionResource::class => [
                'permission' => PowerXPermission::ManageBatches,
                'group' => 'Training Operations',
                'sort' => 120,
                'icon' => Heroicon::OutlinedClock,
            ],
            AttendanceRecordResource::class => [
                'permission' => PowerXPermission::ManageAttendance,
                'group' => 'Training Operations',
                'sort' => 130,
                'icon' => Heroicon::OutlinedClipboardDocumentList,
            ],
            InvoiceResource::class => [
                'permission' => PowerXPermission::ManagePayments,
                'group' => 'Finance',
                'sort' => 140,
                'icon' => Heroicon::OutlinedDocumentCurrencyDollar,
            ],
            PaymentTransactionResource::class => [
                'permission' => PowerXPermission::ManagePayments,
                'group' => 'Finance',
                'sort' => 150,
                'icon' => Heroicon::OutlinedBanknotes,
            ],
            QuestionResource::class => [
                'permission' => PowerXPermission::ManageExams,
                'group' => 'Exams',
                'sort' => 160,
                'icon' => Heroicon::OutlinedQuestionMarkCircle,
            ],
            ExamResource::class => [
                'permission' => PowerXPermission::ManageExams,
                'group' => 'Exams',
                'sort' => 170,
                'icon' => Heroicon::OutlinedClipboardDocument,
            ],
            ExamAttemptResource::class => [
                'permission' => PowerXPermission::ManageExams,
                'group' => 'Exams',
                'sort' => 180,
                'icon' => Heroicon::OutlinedChartPie,
            ],
            CertificateResource::class => [
                'permission' => PowerXPermission::ManageCertificates,
                'group' => 'Certificates',
                'sort' => 190,
                'icon' => Heroicon::OutlinedShieldCheck,
            ],
        };
    }
}

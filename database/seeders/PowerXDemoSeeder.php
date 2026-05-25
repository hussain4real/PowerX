<?php

namespace Database\Seeders;

use App\Enums\PowerXRole;
use App\Enums\TeamRole;
use App\Models\AttendanceRecord;
use App\Models\AuditEvent;
use App\Models\Certificate;
use App\Models\Communication;
use App\Models\Company;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CoursePackage;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\PaymentTransaction;
use App\Models\Question;
use App\Models\StudentProfile;
use App\Models\Team;
use App\Models\TrainingBatch;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PowerXDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('PowerX demo data is skipped in production.');

            return;
        }

        $this->call(PowerXAccessSeeder::class);

        $team = $this->seedTeam();
        $users = $this->seedUsers($team);
        $companies = $this->seedCompanies($team);
        $courses = $this->seedCourses($team);
        $packages = $this->seedPackages($team, $courses);
        $lessons = $this->seedLearningContent($courses);
        $profiles = $this->seedStudentProfiles($team, $users, $companies);
        $enrollments = $this->seedEnrollments($team, $users, $companies, $courses, $packages, $profiles);

        $this->seedBatchesAndAttendance($team, $users, $courses, $enrollments);
        $this->seedLeadsAndCommunications($team, $users, $companies, $courses, $profiles);
        $invoices = $this->seedFinance($team, $users, $companies, $profiles, $enrollments);
        $this->seedExams($team, $courses, $profiles, $enrollments);
        $certificates = $this->seedCertificates($team, $users, $courses, $profiles, $enrollments);
        $this->seedProgress($enrollments, $lessons);
        $this->seedAuditEvents($team, $users, $invoices, $certificates);

        $this->command?->info("PowerX demo data is ready for the {$team->name} workspace.");
    }

    private function seedTeam(): Team
    {
        return Team::query()->firstOrCreate(
            ['slug' => 'powerx-training-center'],
            [
                'name' => 'PowerX Training Center',
                'is_personal' => false,
            ],
        );
    }

    /**
     * @return array<string, User>
     */
    private function seedUsers(Team $team): array
    {
        $personas = [
            'management' => ['name' => 'Mariam PowerX Manager', 'email' => 'management@powerx.test', 'role' => PowerXRole::Management, 'teamRole' => TeamRole::Owner],
            'admin' => ['name' => 'Omar Admin', 'email' => 'admin@powerx.test', 'role' => PowerXRole::Admin, 'teamRole' => TeamRole::Admin],
            'sales' => ['name' => 'Noura Sales', 'email' => 'sales@powerx.test', 'role' => PowerXRole::Sales, 'teamRole' => TeamRole::Member],
            'finance' => ['name' => 'Hassan Finance', 'email' => 'finance@powerx.test', 'role' => PowerXRole::Finance, 'teamRole' => TeamRole::Member],
            'instructor' => ['name' => 'Instructor Noor', 'email' => 'instructor@powerx.test', 'role' => PowerXRole::Instructor, 'teamRole' => TeamRole::Member],
            'support' => ['name' => 'Sara Support', 'email' => 'support@powerx.test', 'role' => PowerXRole::Support, 'teamRole' => TeamRole::Member],
            'student' => ['name' => 'Fatima Student', 'email' => 'student@powerx.test', 'role' => PowerXRole::Student, 'teamRole' => TeamRole::Member],
            'corporate' => ['name' => 'Karim Corporate', 'email' => 'corporate@powerx.test', 'role' => PowerXRole::Corporate, 'teamRole' => TeamRole::Member],
        ];

        return collect($personas)
            ->mapWithKeys(function (array $persona, string $key) use ($team): array {
                $user = User::query()->updateOrCreate(
                    ['email' => $persona['email']],
                    [
                        'name' => $persona['name'],
                        'password' => Hash::make('password'),
                    ],
                );

                $user->forceFill(['email_verified_at' => now()])->save();
                $team->members()->syncWithoutDetaching([
                    $user->id => ['role' => $persona['teamRole']->value],
                ]);
                $user->switchTeam($team);
                $user->assignRole($persona['role']->value);

                return [$key => $user];
            })
            ->all();
    }

    /**
     * @return array<string, Company>
     */
    private function seedCompanies(Team $team): array
    {
        return [
            'mep' => Company::query()->updateOrCreate(
                ['team_id' => $team->id, 'email' => 'training@doha-electrical.test'],
                [
                    'name' => 'Doha Electrical Works',
                    'contact_name' => 'Noura Coordinator',
                    'phone' => '+974 4499 0000',
                    'address' => 'Industrial Area, Doha, Qatar',
                    'metadata' => ['industry' => 'MEP contracting', 'demo' => true],
                ],
            ),
            'facility' => Company::query()->updateOrCreate(
                ['team_id' => $team->id, 'email' => 'academy@qatar-facilities.test'],
                [
                    'name' => 'Qatar Facilities Group',
                    'contact_name' => 'Karim Corporate',
                    'phone' => '+974 4466 2211',
                    'address' => 'West Bay, Doha, Qatar',
                    'metadata' => ['industry' => 'facility management', 'demo' => true],
                ],
            ),
        ];
    }

    /**
     * @return array<string, Course>
     */
    private function seedCourses(Team $team): array
    {
        $courses = [
            'kahramaa' => [
                'title' => 'Kahramaa Exam Preparation',
                'slug' => 'kahramaa-exam-preparation',
                'category' => 'Kahramaa',
                'delivery_mode' => 'blended',
                'base_price' => 1500,
                'summary' => 'Exam-focused electrical preparation for engineers, technicians, and supervisors working in Qatar.',
                'description' => 'Curated regulations review, drawing practice, mock exams, practical demonstrations, and instructor support for PowerX learners preparing for Kahramaa-related assessments.',
                'is_featured' => true,
                'metadata' => ['audience' => 'Engineers, technicians, supervisors', 'claim_policy' => 'Public-safe, success-focused wording only'],
            ],
            'safety' => [
                'title' => 'Electrical Safety and Compliance',
                'slug' => 'electrical-safety-and-compliance',
                'category' => 'Electrical safety',
                'delivery_mode' => 'classroom',
                'base_price' => 900,
                'summary' => 'Practical safety training for isolation, testing, site controls, and safe electrical work.',
                'description' => 'Hands-on safety sessions for teams that need documented attendance, practical assessment comments, and consistent refresher training.',
                'is_featured' => true,
                'metadata' => ['audience' => 'Site teams and supervisors'],
            ],
            'cable' => [
                'title' => 'Cable Jointing Practical Workshop',
                'slug' => 'cable-jointing-practical-workshop',
                'category' => 'Practical workshop',
                'delivery_mode' => 'classroom',
                'base_price' => 1250,
                'summary' => 'Workshop-led cable jointing practice with instructor feedback and safety checkpoints.',
                'description' => 'A practical course for technicians who need structured lab practice, attendance tracking, and workshop assessment records.',
                'is_featured' => false,
                'metadata' => ['audience' => 'Electrical technicians'],
            ],
            'power-design' => [
                'title' => 'Power Distribution Design',
                'slug' => 'power-distribution-design',
                'category' => 'Design',
                'delivery_mode' => 'online',
                'base_price' => 1800,
                'summary' => 'Load calculation, cable sizing, protection coordination, and drawing review for project engineers.',
                'description' => 'Design-focused sessions with worked examples, downloadable notes, and instructor review.',
                'is_featured' => false,
                'metadata' => ['audience' => 'Electrical engineers'],
            ],
            'substation' => [
                'title' => 'Substation and Switchgear Fundamentals',
                'slug' => 'substation-and-switchgear-fundamentals',
                'category' => 'Power systems',
                'delivery_mode' => 'blended',
                'base_price' => 2100,
                'summary' => 'Transformer, switchgear, and protection fundamentals for maintenance and project teams.',
                'description' => 'A systems course for engineers and technicians who support power distribution assets.',
                'is_featured' => false,
                'metadata' => ['audience' => 'Maintenance and project teams'],
            ],
            'corporate' => [
                'title' => 'Corporate Technical Training Program',
                'slug' => 'corporate-technical-training-program',
                'category' => 'Corporate',
                'delivery_mode' => 'blended',
                'base_price' => 4500,
                'summary' => 'Custom PowerX training tracks for MEP contractors, construction teams, and facility companies.',
                'description' => 'Company-focused quotation, bulk enrollment, attendance reporting, and completion tracking for technical teams.',
                'is_featured' => true,
                'metadata' => ['audience' => 'Corporate coordinators'],
            ],
        ];

        return collect($courses)
            ->mapWithKeys(fn (array $course, string $key): array => [
                $key => Course::query()->updateOrCreate(
                    ['slug' => $course['slug']],
                    [
                        'team_id' => $team->id,
                        'title' => $course['title'],
                        'category' => $course['category'],
                        'status' => 'published',
                        'delivery_mode' => $course['delivery_mode'],
                        'currency' => 'QAR',
                        'base_price' => $course['base_price'],
                        'validity_days' => 180,
                        'summary' => $course['summary'],
                        'description' => $course['description'],
                        'is_featured' => $course['is_featured'],
                        'metadata' => $course['metadata'] + ['demo' => true],
                        'published_at' => now()->subDay(),
                    ],
                ),
            ])
            ->all();
    }

    /**
     * @param  array<string, Course>  $courses
     * @return array<string, CoursePackage>
     */
    private function seedPackages(Team $team, array $courses): array
    {
        $packages = [];

        foreach ($courses as $key => $course) {
            $packages[$key] = CoursePackage::query()->updateOrCreate(
                ['course_id' => $course->id, 'slug' => 'exam-ready'],
                [
                    'team_id' => $team->id,
                    'name' => $key === 'corporate' ? 'Corporate Team Track' : 'Exam Ready',
                    'package_type' => $key === 'corporate' ? 'corporate' : 'standard',
                    'currency' => 'QAR',
                    'price' => $course->base_price,
                    'discount_price' => $key === 'kahramaa' ? 1350 : null,
                    'validity_days' => $key === 'corporate' ? 365 : 180,
                    'max_exam_attempts' => 3,
                    'includes_certificate' => true,
                    'allows_free_preview' => true,
                    'is_active' => true,
                    'metadata' => ['demo' => true, 'includes_practical' => in_array($course->delivery_mode, ['classroom', 'blended'], true)],
                ],
            );
        }

        $packages['kahramaa-corporate'] = CoursePackage::query()->updateOrCreate(
            ['course_id' => $courses['kahramaa']->id, 'slug' => 'corporate-batch'],
            [
                'team_id' => $team->id,
                'name' => 'Corporate Batch',
                'package_type' => 'corporate',
                'currency' => 'QAR',
                'price' => 8500,
                'discount_price' => null,
                'validity_days' => 365,
                'max_exam_attempts' => 3,
                'includes_certificate' => true,
                'allows_free_preview' => true,
                'is_active' => true,
                'metadata' => ['demo' => true, 'includes_practical' => true, 'seats' => 8],
            ],
        );

        return $packages;
    }

    /**
     * @param  array<string, Course>  $courses
     * @return array<string, array<int, Lesson>>
     */
    private function seedLearningContent(array $courses): array
    {
        $lessons = [];

        $modulePlans = [
            'kahramaa' => [
                'Kahramaa rules and standards' => [
                    ['Kahramaa approval flow overview', 'video', true, 28],
                    ['Common exam mistakes and corrections', 'document', false, 35],
                ],
                'Electrical fundamentals' => [
                    ['Load calculation worked examples', 'video', false, 42],
                    ['Cable sizing and protection checklist', 'document', false, 30],
                ],
                'Mock exam practice' => [
                    ['Timed question practice', 'quiz', false, 45],
                    ['Practical viva preparation', 'practical', false, 50],
                ],
            ],
            'safety' => [
                'Safe electrical work sequence' => [
                    ['Isolation and lockout walkthrough', 'video', true, 24],
                    ['Testing before touch practical', 'practical', false, 40],
                ],
            ],
            'cable' => [
                'Workshop readiness' => [
                    ['Tools and materials checklist', 'document', true, 20],
                    ['Jointing practice assessment', 'practical', false, 55],
                ],
            ],
            'power-design' => [
                'Design calculations' => [
                    ['Load schedule preparation', 'video', true, 34],
                    ['Voltage drop and protection notes', 'document', false, 36],
                ],
            ],
            'substation' => [
                'Switchgear fundamentals' => [
                    ['Transformer and switchgear overview', 'video', true, 32],
                    ['Protection relay basics', 'document', false, 38],
                ],
            ],
            'corporate' => [
                'Corporate training operations' => [
                    ['Training-needs intake', 'document', true, 18],
                    ['Completion report walkthrough', 'video', false, 22],
                ],
            ],
        ];

        $moduleSort = 1;

        foreach ($modulePlans as $courseKey => $modules) {
            foreach ($modules as $moduleTitle => $lessonPlan) {
                $module = CourseModule::query()->updateOrCreate(
                    ['course_id' => $courses[$courseKey]->id, 'title' => $moduleTitle],
                    [
                        'summary' => 'Demo module for '.$courses[$courseKey]->title,
                        'sort_order' => $moduleSort++,
                        'is_active' => true,
                        'metadata' => ['demo' => true],
                    ],
                );

                foreach ($lessonPlan as $index => [$title, $type, $isPreview, $duration]) {
                    $lesson = Lesson::query()->updateOrCreate(
                        ['course_module_id' => $module->id, 'slug' => Str::slug($title)],
                        [
                            'title' => $title,
                            'lesson_type' => $type,
                            'sort_order' => $index + 1,
                            'duration_minutes' => $duration,
                            'content' => 'Demo learning content for '.$title.'.',
                            'is_preview' => $isPreview,
                            'is_active' => true,
                            'metadata' => ['demo' => true],
                        ],
                    );

                    $lessons[$courseKey][] = $lesson;
                }
            }
        }

        return $lessons;
    }

    /**
     * @param  array<string, User>  $users
     * @param  array<string, Company>  $companies
     * @return array<string, StudentProfile>
     */
    private function seedStudentProfiles(Team $team, array $users, array $companies): array
    {
        return [
            'fatima' => StudentProfile::query()->updateOrCreate(
                ['user_id' => $users['student']->id],
                [
                    'team_id' => $team->id,
                    'company_id' => $companies['facility']->id,
                    'full_name' => 'Fatima Ali',
                    'email' => 'student@powerx.test',
                    'mobile' => '+974 5011 2233',
                    'profession' => 'Electrical Engineer',
                    'qatar_location' => 'Doha',
                    'preferred_schedule' => 'weekend',
                    'document_status' => 'verified',
                    'metadata' => ['demo' => true, 'persona' => 'student portal'],
                ],
            ),
            'aisha' => StudentProfile::query()->updateOrCreate(
                ['team_id' => $team->id, 'email' => 'aisha.candidate@powerx.test'],
                [
                    'user_id' => null,
                    'company_id' => $companies['mep']->id,
                    'full_name' => 'Aisha Candidate',
                    'mobile' => '+974 5522 3344',
                    'profession' => 'Electrical Supervisor',
                    'qatar_location' => 'Industrial Area',
                    'preferred_schedule' => 'weekday-evening',
                    'document_status' => 'verified',
                    'metadata' => ['demo' => true, 'persona' => 'corporate trainee'],
                ],
            ),
            'bilal' => StudentProfile::query()->updateOrCreate(
                ['team_id' => $team->id, 'email' => 'bilal.technician@powerx.test'],
                [
                    'user_id' => null,
                    'company_id' => $companies['mep']->id,
                    'full_name' => 'Bilal Khan',
                    'mobile' => '+974 5333 4455',
                    'profession' => 'Electrical Technician',
                    'qatar_location' => 'Al Wakrah',
                    'preferred_schedule' => 'intensive',
                    'document_status' => 'pending',
                    'metadata' => ['demo' => true, 'persona' => 'renewal opportunity'],
                ],
            ),
        ];
    }

    /**
     * @param  array<string, User>  $users
     * @param  array<string, Company>  $companies
     * @param  array<string, Course>  $courses
     * @param  array<string, CoursePackage>  $packages
     * @param  array<string, StudentProfile>  $profiles
     * @return array<string, Enrollment>
     */
    private function seedEnrollments(Team $team, array $users, array $companies, array $courses, array $packages, array $profiles): array
    {
        return [
            'fatima-kahramaa' => Enrollment::query()->updateOrCreate(
                ['team_id' => $team->id, 'student_profile_id' => $profiles['fatima']->id, 'course_id' => $courses['kahramaa']->id],
                [
                    'company_id' => $companies['facility']->id,
                    'course_package_id' => $packages['kahramaa']->id,
                    'approved_by_id' => $users['admin']->id,
                    'status' => 'active',
                    'payment_status' => 'paid',
                    'access_starts_at' => now()->subDays(10),
                    'access_expires_at' => now()->addDays(170),
                    'approved_at' => now()->subDays(9),
                    'notes' => 'Demo student with active paid access.',
                    'metadata' => ['demo' => true, 'admission_channel' => 'individual'],
                ],
            ),
            'aisha-safety' => Enrollment::query()->updateOrCreate(
                ['team_id' => $team->id, 'student_profile_id' => $profiles['aisha']->id, 'course_id' => $courses['safety']->id],
                [
                    'company_id' => $companies['mep']->id,
                    'course_package_id' => $packages['safety']->id,
                    'approved_by_id' => $users['admin']->id,
                    'status' => 'completed',
                    'payment_status' => 'paid',
                    'access_starts_at' => now()->subMonths(2),
                    'access_expires_at' => now()->addMonths(4),
                    'approved_at' => now()->subMonths(2),
                    'notes' => 'Completed corporate safety training.',
                    'metadata' => ['demo' => true, 'admission_channel' => 'corporate'],
                ],
            ),
            'bilal-kahramaa' => Enrollment::query()->updateOrCreate(
                ['team_id' => $team->id, 'student_profile_id' => $profiles['bilal']->id, 'course_id' => $courses['kahramaa']->id],
                [
                    'company_id' => $companies['mep']->id,
                    'course_package_id' => $packages['kahramaa-corporate']->id,
                    'approved_by_id' => $users['admin']->id,
                    'status' => 'completed',
                    'payment_status' => 'paid',
                    'access_starts_at' => now()->subMonths(7),
                    'access_expires_at' => now()->subMonth(),
                    'approved_at' => now()->subMonths(7),
                    'notes' => 'Completed record used for certificate renewal demos.',
                    'metadata' => ['demo' => true, 'admission_channel' => 'corporate'],
                ],
            ),
            'fatima-power-design' => Enrollment::query()->updateOrCreate(
                ['team_id' => $team->id, 'student_profile_id' => $profiles['fatima']->id, 'course_id' => $courses['power-design']->id],
                [
                    'company_id' => $companies['facility']->id,
                    'course_package_id' => $packages['power-design']->id,
                    'approved_by_id' => null,
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'access_starts_at' => null,
                    'access_expires_at' => null,
                    'approved_at' => null,
                    'notes' => 'Demo pending admissions/payment scenario.',
                    'metadata' => ['demo' => true, 'admission_channel' => 'individual'],
                ],
            ),
        ];
    }

    /**
     * @param  array<string, User>  $users
     * @param  array<string, Course>  $courses
     * @param  array<string, Enrollment>  $enrollments
     */
    private function seedBatchesAndAttendance(Team $team, array $users, array $courses, array $enrollments): void
    {
        $batch = TrainingBatch::query()->updateOrCreate(
            ['team_id' => $team->id, 'name' => 'PX-KAH-WKND-01'],
            [
                'course_id' => $courses['kahramaa']->id,
                'instructor_id' => $users['instructor']->id,
                'delivery_mode' => 'blended',
                'venue' => 'Doha Training Lab',
                'capacity' => 16,
                'status' => 'scheduled',
                'starts_at' => now()->addDays(3)->setTime(18, 0),
                'ends_at' => now()->addDays(17)->setTime(21, 0),
                'metadata' => ['demo' => true, 'language' => 'English'],
            ],
        );

        $sessions = [
            TrainingSession::query()->updateOrCreate(
                ['training_batch_id' => $batch->id, 'title' => 'Kahramaa theory review'],
                [
                    'session_type' => 'theory',
                    'venue' => 'Online',
                    'status' => 'completed',
                    'starts_at' => now()->subDays(2)->setTime(18, 0),
                    'ends_at' => now()->subDays(2)->setTime(20, 0),
                    'metadata' => ['demo' => true, 'attendance_required' => true],
                ],
            ),
            TrainingSession::query()->updateOrCreate(
                ['training_batch_id' => $batch->id, 'title' => 'Weekend practical lab'],
                [
                    'session_type' => 'practical',
                    'venue' => 'Doha Training Lab',
                    'status' => 'scheduled',
                    'starts_at' => now()->addDays(5)->setTime(9, 0),
                    'ends_at' => now()->addDays(5)->setTime(13, 0),
                    'metadata' => ['demo' => true, 'attendance_required' => true],
                ],
            ),
        ];

        foreach ($sessions as $session) {
            foreach (['fatima-kahramaa', 'bilal-kahramaa'] as $enrollmentKey) {
                AttendanceRecord::query()->updateOrCreate(
                    ['training_session_id' => $session->id, 'enrollment_id' => $enrollments[$enrollmentKey]->id],
                    [
                        'team_id' => $team->id,
                        'marked_by_id' => $users['instructor']->id,
                        'assessed_by_id' => $session->session_type === 'practical' ? $users['instructor']->id : null,
                        'status' => $session->session_type === 'theory' ? 'present' : 'pending',
                        'attended_at' => $session->session_type === 'theory' ? $session->starts_at : null,
                        'practical_outcome' => $session->session_type === 'practical' ? null : 'passed',
                        'practical_score' => $session->session_type === 'practical' ? null : 88,
                        'practical_comments' => $session->session_type === 'practical' ? null : 'Completed the theory review.',
                        'assessed_at' => $session->session_type === 'theory' ? $session->ends_at : null,
                        'metadata' => ['demo' => true],
                    ],
                );
            }
        }
    }

    /**
     * @param  array<string, User>  $users
     * @param  array<string, Company>  $companies
     * @param  array<string, Course>  $courses
     * @param  array<string, StudentProfile>  $profiles
     */
    private function seedLeadsAndCommunications(Team $team, array $users, array $companies, array $courses, array $profiles): void
    {
        $newLead = Lead::query()->updateOrCreate(
            ['team_id' => $team->id, 'email' => 'new.lead@powerx.test'],
            [
                'owner_id' => $users['sales']->id,
                'company_id' => $companies['facility']->id,
                'course_id' => $courses['kahramaa']->id,
                'name' => 'Rashid Prospect',
                'phone' => '+974 5666 7788',
                'source' => 'website',
                'campaign' => 'kahramaa-weekend-demo',
                'status' => 'new',
                'course_interest' => 'Kahramaa exam prep',
                'notes' => 'Asked for weekend timing and bank transfer steps.',
                'follow_up_at' => now()->addDay(),
                'outcome' => null,
                'converted_at' => null,
                'metadata' => ['demo' => true, 'utm_source' => 'local-demo'],
            ],
        );

        Lead::query()->updateOrCreate(
            ['team_id' => $team->id, 'email' => 'qualified.corporate@powerx.test'],
            [
                'owner_id' => $users['sales']->id,
                'company_id' => $companies['mep']->id,
                'course_id' => $courses['corporate']->id,
                'name' => 'Corporate Coordinator',
                'phone' => '+974 5777 8899',
                'source' => 'referral',
                'campaign' => 'contractor-alumni',
                'status' => 'qualified',
                'course_interest' => 'Corporate training',
                'notes' => 'Needs quotation for eight technicians.',
                'follow_up_at' => now()->addDays(2),
                'outcome' => null,
                'converted_at' => null,
                'metadata' => ['demo' => true, 'team_size' => 8],
            ],
        );

        Lead::query()->updateOrCreate(
            ['team_id' => $team->id, 'email' => 'converted.alumni@powerx.test'],
            [
                'owner_id' => $users['sales']->id,
                'company_id' => $companies['mep']->id,
                'course_id' => $courses['safety']->id,
                'name' => 'Aisha Candidate',
                'phone' => $profiles['aisha']->mobile,
                'source' => 'referral',
                'campaign' => 'contractor-alumni',
                'status' => 'converted',
                'course_interest' => 'Electrical safety',
                'notes' => 'Converted into a completed safety enrollment.',
                'follow_up_at' => null,
                'outcome' => 'enrolled',
                'converted_at' => now()->subMonth(),
                'metadata' => ['demo' => true],
            ],
        );

        Communication::query()->updateOrCreate(
            ['team_id' => $team->id, 'template_key' => 'lead_follow_up', 'subject' => 'PowerX demo follow-up: Kahramaa exam prep'],
            [
                'lead_id' => $newLead->id,
                'student_profile_id' => null,
                'company_id' => $companies['facility']->id,
                'user_id' => $users['sales']->id,
                'channel' => Communication::CHANNEL_WHATSAPP,
                'message' => 'Hello Rashid, PowerX can help with Kahramaa-focused preparation, mock exams, and practical sessions. Would you like the next weekend batch details?',
                'status' => Communication::STATUS_SCHEDULED,
                'scheduled_at' => now()->addHours(4),
                'sent_at' => null,
                'metadata' => ['demo' => true, 'whatsapp_url' => 'https://wa.me/97456667788'],
            ],
        );

        Communication::query()->updateOrCreate(
            ['team_id' => $team->id, 'template_key' => 'payment_reminder', 'subject' => 'PowerX demo payment reminder'],
            [
                'lead_id' => null,
                'student_profile_id' => $profiles['fatima']->id,
                'company_id' => $companies['facility']->id,
                'user_id' => $users['finance']->id,
                'channel' => Communication::CHANNEL_EMAIL,
                'message' => 'Your PowerX course access is active. This demo reminder shows how finance follow-up messages are tracked before external sending is enabled.',
                'status' => Communication::STATUS_DRAFT,
                'scheduled_at' => null,
                'sent_at' => null,
                'metadata' => ['demo' => true],
            ],
        );
    }

    /**
     * @param  array<string, User>  $users
     * @param  array<string, Company>  $companies
     * @param  array<string, StudentProfile>  $profiles
     * @param  array<string, Enrollment>  $enrollments
     * @return array<string, Invoice>
     */
    private function seedFinance(Team $team, array $users, array $companies, array $profiles, array $enrollments): array
    {
        $invoices = [
            'fatima' => Invoice::query()->updateOrCreate(
                ['number' => 'PX-INV-DEMO-001'],
                [
                    'team_id' => $team->id,
                    'enrollment_id' => $enrollments['fatima-kahramaa']->id,
                    'company_id' => $companies['facility']->id,
                    'student_profile_id' => $profiles['fatima']->id,
                    'type' => 'invoice',
                    'status' => 'paid',
                    'currency' => 'QAR',
                    'subtotal' => 1500,
                    'discount_total' => 150,
                    'tax_total' => 0,
                    'total' => 1350,
                    'issued_at' => now()->subDays(8),
                    'due_at' => now()->subDay(),
                    'paid_at' => now()->subDays(6),
                    'metadata' => ['demo' => true, 'payment_terms' => 'Manual payment approved by finance.'],
                ],
            ),
            'pending' => Invoice::query()->updateOrCreate(
                ['number' => 'PX-INV-DEMO-002'],
                [
                    'team_id' => $team->id,
                    'enrollment_id' => $enrollments['fatima-power-design']->id,
                    'company_id' => $companies['facility']->id,
                    'student_profile_id' => $profiles['fatima']->id,
                    'type' => 'invoice',
                    'status' => 'issued',
                    'currency' => 'QAR',
                    'subtotal' => 1800,
                    'discount_total' => 0,
                    'tax_total' => 0,
                    'total' => 1800,
                    'issued_at' => now()->subDay(),
                    'due_at' => now()->addDays(6),
                    'paid_at' => null,
                    'metadata' => ['demo' => true, 'payment_terms' => 'Awaiting manual payment proof.'],
                ],
            ),
            'corporate' => Invoice::query()->updateOrCreate(
                ['number' => 'PX-QUO-DEMO-001'],
                [
                    'team_id' => $team->id,
                    'enrollment_id' => $enrollments['aisha-safety']->id,
                    'company_id' => $companies['mep']->id,
                    'student_profile_id' => $profiles['aisha']->id,
                    'type' => 'quotation',
                    'status' => 'paid',
                    'currency' => 'QAR',
                    'subtotal' => 7200,
                    'discount_total' => 600,
                    'tax_total' => 0,
                    'total' => 6600,
                    'issued_at' => now()->subMonths(2),
                    'due_at' => now()->subMonths(2)->addDays(7),
                    'paid_at' => now()->subMonths(2)->addDays(2),
                    'metadata' => ['demo' => true, 'seats' => 8],
                ],
            ),
        ];

        PaymentTransaction::query()->updateOrCreate(
            ['reference' => 'PX-PAY-DEMO-001'],
            [
                'team_id' => $team->id,
                'enrollment_id' => $enrollments['fatima-kahramaa']->id,
                'invoice_id' => $invoices['fatima']->id,
                'company_id' => $companies['facility']->id,
                'student_profile_id' => $profiles['fatima']->id,
                'approved_by_id' => $users['finance']->id,
                'method' => 'bank_transfer',
                'provider' => null,
                'status' => 'approved',
                'currency' => 'QAR',
                'amount' => 1350,
                'paid_at' => now()->subDays(6),
                'approved_at' => now()->subDays(5),
                'metadata' => ['demo' => true, 'manual_review' => true],
            ],
        );

        PaymentTransaction::query()->updateOrCreate(
            ['reference' => 'PX-PAY-DEMO-002'],
            [
                'team_id' => $team->id,
                'enrollment_id' => $enrollments['fatima-power-design']->id,
                'invoice_id' => $invoices['pending']->id,
                'company_id' => $companies['facility']->id,
                'student_profile_id' => $profiles['fatima']->id,
                'approved_by_id' => null,
                'method' => 'bank_transfer',
                'provider' => null,
                'status' => 'pending',
                'currency' => 'QAR',
                'amount' => 1800,
                'paid_at' => now(),
                'approved_at' => null,
                'metadata' => ['demo' => true, 'manual_review' => true],
            ],
        );

        PaymentTransaction::query()->updateOrCreate(
            ['reference' => 'PX-PAY-DEMO-003'],
            [
                'team_id' => $team->id,
                'enrollment_id' => $enrollments['aisha-safety']->id,
                'invoice_id' => $invoices['corporate']->id,
                'company_id' => $companies['mep']->id,
                'student_profile_id' => $profiles['aisha']->id,
                'approved_by_id' => $users['finance']->id,
                'method' => 'cheque',
                'provider' => null,
                'status' => 'approved',
                'currency' => 'QAR',
                'amount' => 6600,
                'paid_at' => now()->subMonths(2)->addDays(2),
                'approved_at' => now()->subMonths(2)->addDays(3),
                'metadata' => ['demo' => true, 'manual_review' => true],
            ],
        );

        return $invoices;
    }

    /**
     * @param  array<string, Course>  $courses
     * @param  array<string, StudentProfile>  $profiles
     * @param  array<string, Enrollment>  $enrollments
     */
    private function seedExams(Team $team, array $courses, array $profiles, array $enrollments): void
    {
        foreach ([
            ['Safety', 'Which action should be completed before electrical testing?', ['A' => 'Confirm isolation', 'B' => 'Skip PPE', 'C' => 'Ignore permit', 'D' => 'Bypass lockout']],
            ['Regulations', 'What should a candidate review before a Kahramaa-related assessment?', ['A' => 'Applicable rules and drawings', 'B' => 'Unrelated civil drawings', 'C' => 'Expired notes only', 'D' => 'No standards']],
            ['Load Calculation', 'What is a key input for feeder sizing?', ['A' => 'Connected load', 'B' => 'Paint color', 'C' => 'Office furniture', 'D' => 'Vehicle plate number']],
        ] as [$topic, $text, $options]) {
            Question::query()->updateOrCreate(
                ['team_id' => $team->id, 'course_id' => $courses['kahramaa']->id, 'question_text' => $text],
                [
                    'topic' => $topic,
                    'difficulty' => 'standard',
                    'type' => 'single_choice',
                    'options' => collect($options)
                        ->map(fn (string $label, string $key): array => ['key' => $key, 'label' => $label])
                        ->values()
                        ->all(),
                    'correct_answer' => ['A'],
                    'explanation' => 'Demo explanation for '.$topic.'.',
                    'is_active' => true,
                    'metadata' => ['demo' => true],
                ],
            );
        }

        $exams = [
            'kahramaa' => Exam::query()->updateOrCreate(
                ['team_id' => $team->id, 'course_id' => $courses['kahramaa']->id, 'title' => 'Kahramaa readiness mock exam'],
                [
                    'exam_type' => 'mock',
                    'duration_minutes' => 60,
                    'pass_mark' => 70,
                    'max_attempts' => 3,
                    'question_count' => 25,
                    'randomize_questions' => true,
                    'is_active' => true,
                    'metadata' => ['demo' => true, 'show_results_immediately' => true],
                ],
            ),
            'safety' => Exam::query()->updateOrCreate(
                ['team_id' => $team->id, 'course_id' => $courses['safety']->id, 'title' => 'Safety practical knowledge check'],
                [
                    'exam_type' => 'final',
                    'duration_minutes' => 45,
                    'pass_mark' => 75,
                    'max_attempts' => 2,
                    'question_count' => 15,
                    'randomize_questions' => true,
                    'is_active' => true,
                    'metadata' => ['demo' => true],
                ],
            ),
        ];

        ExamAttempt::query()->updateOrCreate(
            ['team_id' => $team->id, 'exam_id' => $exams['kahramaa']->id, 'enrollment_id' => $enrollments['fatima-kahramaa']->id, 'attempt_number' => 1],
            [
                'student_profile_id' => $profiles['fatima']->id,
                'result' => 'passed',
                'score' => 86,
                'duration_seconds' => 3120,
                'answers' => [['question_id' => Question::query()->whereBelongsTo($courses['kahramaa'])->value('id'), 'answer' => ['A']]],
                'started_at' => now()->subDays(3),
                'submitted_at' => now()->subDays(3)->addMinutes(52),
                'metadata' => ['demo' => true, 'weak_topic' => 'Drawing review'],
            ],
        );

        ExamAttempt::query()->updateOrCreate(
            ['team_id' => $team->id, 'exam_id' => $exams['safety']->id, 'enrollment_id' => $enrollments['aisha-safety']->id, 'attempt_number' => 1],
            [
                'student_profile_id' => $profiles['aisha']->id,
                'result' => 'passed',
                'score' => 91,
                'duration_seconds' => 2400,
                'answers' => [['question_id' => null, 'answer' => ['A']]],
                'started_at' => now()->subMonth(),
                'submitted_at' => now()->subMonth()->addMinutes(40),
                'metadata' => ['demo' => true, 'weak_topic' => 'Switchgear safety'],
            ],
        );
    }

    /**
     * @param  array<string, User>  $users
     * @param  array<string, Course>  $courses
     * @param  array<string, StudentProfile>  $profiles
     * @param  array<string, Enrollment>  $enrollments
     * @return array<string, Certificate>
     */
    private function seedCertificates(Team $team, array $users, array $courses, array $profiles, array $enrollments): array
    {
        return [
            'fatima' => Certificate::query()->updateOrCreate(
                ['certificate_number' => 'PX-CERT-DEMO-001'],
                [
                    'team_id' => $team->id,
                    'enrollment_id' => $enrollments['fatima-kahramaa']->id,
                    'student_profile_id' => $profiles['fatima']->id,
                    'course_id' => $courses['kahramaa']->id,
                    'approved_by_id' => $users['management']->id,
                    'verification_token' => 'demo-fatima-kahramaa-certificate',
                    'status' => 'issued',
                    'result' => 'passed',
                    'issued_at' => now()->subDay(),
                    'expires_at' => now()->addDays(45),
                    'pdf_generated_at' => null,
                    'metadata' => ['demo' => true, 'verification_scope' => 'PowerX completion record'],
                ],
            ),
            'aisha' => Certificate::query()->updateOrCreate(
                ['certificate_number' => 'PX-CERT-DEMO-002'],
                [
                    'team_id' => $team->id,
                    'enrollment_id' => $enrollments['aisha-safety']->id,
                    'student_profile_id' => $profiles['aisha']->id,
                    'course_id' => $courses['safety']->id,
                    'approved_by_id' => $users['management']->id,
                    'verification_token' => 'demo-aisha-safety-certificate',
                    'status' => 'issued',
                    'result' => 'passed',
                    'issued_at' => now()->subMonth(),
                    'expires_at' => now()->addMonths(11),
                    'pdf_generated_at' => null,
                    'metadata' => ['demo' => true, 'verification_scope' => 'PowerX completion record'],
                ],
            ),
            'bilal' => Certificate::query()->updateOrCreate(
                ['certificate_number' => 'PX-CERT-DEMO-EXPIRED'],
                [
                    'team_id' => $team->id,
                    'enrollment_id' => $enrollments['bilal-kahramaa']->id,
                    'student_profile_id' => $profiles['bilal']->id,
                    'course_id' => $courses['kahramaa']->id,
                    'approved_by_id' => $users['management']->id,
                    'verification_token' => 'demo-bilal-expired-certificate',
                    'status' => 'issued',
                    'result' => 'passed',
                    'issued_at' => now()->subMonths(7),
                    'expires_at' => now()->subDays(2),
                    'pdf_generated_at' => null,
                    'metadata' => ['demo' => true, 'verification_scope' => 'PowerX completion record'],
                ],
            ),
        ];
    }

    /**
     * @param  array<string, Enrollment>  $enrollments
     * @param  array<string, array<int, Lesson>>  $lessons
     */
    private function seedProgress(array $enrollments, array $lessons): void
    {
        foreach ($lessons['kahramaa'] as $index => $lesson) {
            LessonProgress::query()->updateOrCreate(
                ['enrollment_id' => $enrollments['fatima-kahramaa']->id, 'lesson_id' => $lesson->id],
                [
                    'progress_percentage' => $index < 4 ? 100 : 35,
                    'last_position_seconds' => $index < 4 ? ($lesson->duration_minutes * 60) : 420,
                    'started_at' => now()->subDays(6 - $index),
                    'completed_at' => $index < 4 ? now()->subDays(5 - $index) : null,
                    'metadata' => ['demo' => true],
                ],
            );
        }
    }

    /**
     * @param  array<string, User>  $users
     * @param  array<string, Invoice>  $invoices
     * @param  array<string, Certificate>  $certificates
     */
    private function seedAuditEvents(Team $team, array $users, array $invoices, array $certificates): void
    {
        AuditEvent::query()->updateOrCreate(
            ['team_id' => $team->id, 'action' => 'payment.approved', 'summary' => 'Demo bank transfer approved'],
            [
                'actor_id' => $users['finance']->id,
                'subject_type' => Invoice::class,
                'subject_id' => $invoices['fatima']->id,
                'before' => ['status' => 'issued'],
                'after' => ['status' => 'paid'],
                'metadata' => ['demo' => true, 'reference' => 'PX-PAY-DEMO-001'],
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PowerXDemoSeeder',
            ],
        );

        AuditEvent::query()->updateOrCreate(
            ['team_id' => $team->id, 'action' => 'certificate.issued', 'summary' => 'Demo certificate issued'],
            [
                'actor_id' => $users['management']->id,
                'subject_type' => Certificate::class,
                'subject_id' => $certificates['fatima']->id,
                'before' => ['status' => 'draft'],
                'after' => ['status' => 'issued'],
                'metadata' => ['demo' => true, 'certificate_number' => 'PX-CERT-DEMO-001'],
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PowerXDemoSeeder',
            ],
        );
    }
}

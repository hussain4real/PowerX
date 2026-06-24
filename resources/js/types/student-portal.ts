export type StudentProfile = {
    id: number;
    fullName: string;
    email: string;
    mobile: string | null;
    profession: string | null;
    qatarLocation: string | null;
    preferredSchedule: string | null;
    documentStatus: string;
};

export type StudentSummary = {
    enrolledCourses: number;
    activeEnrollments: number;
    pendingPayments: number;
    issuedCertificates: number;
    averageProgress: number;
    nextSessionLabel: string;
};

export type LessonMedia = {
    id: number;
    name: string;
    fileName: string;
    collectionName: string;
    collectionLabel: string;
    mimeType: string;
    size: number;
    humanReadableSize: string;
    url: string;
    inlineUrl: string;
    downloadUrl: string;
    expiresAt: string;
    canPreviewInline: boolean;
    mediaType: 'video' | 'pdf' | 'download';
};

export type Lesson = {
    id: number;
    title: string;
    lessonType: string;
    durationMinutes: number | null;
    isPreview: boolean;
    isLocked: boolean;
    canViewLesson: boolean;
    canUpdateProgress: boolean;
    viewerUrl: string | null;
    content: string | null;
    contentRevision: number;
    progressPercentage: number;
    lastPositionSeconds: number;
    isCompleted: boolean;
    media: LessonMedia[];
};

export type CourseModule = {
    id: number;
    title: string;
    summary: string | null;
    lessons: Lesson[];
};

export type ScheduleSession = {
    id: number;
    title: string;
    sessionType: string;
    venue: string | null;
    status: string;
    practicalOutcome: string | null;
    practicalComments: string | null;
    startsAt: string | null;
    endsAt: string | null;
    batch: {
        name: string;
        deliveryMode: string;
        instructor: string | null;
    };
};

export type StudentExam = {
    id: number;
    title: string;
    examType: string;
    durationMinutes: number;
    passMark: number;
    maxAttempts: number;
    attemptsUsed: number;
    bestScore: string | number | null;
    lastResult: string | null;
    lastAttemptUrl: string | null;
    activeAttemptUrl: string | null;
    startUrl: string;
    canStart: boolean;
};

export type StudentCertificate = {
    id: number;
    certificateNumber: string;
    status: string;
    result: string | null;
    issuedAt: string | null;
    expiresAt: string | null;
    verifyUrl: string;
};

export type FinanceItem = {
    id: number;
    number?: string;
    method?: string;
    reference?: string | null;
    type?: string;
    status: string;
    currency: string;
    total?: number;
    amount?: number;
    dueAt?: string | null;
    paidAt?: string | null;
};

export type StudentEnrollment = {
    id: number;
    status: string;
    paymentStatus: string;
    accessStatus: string;
    hasPaidAccess: boolean;
    accessStartsAt: string | null;
    accessExpiresAt: string | null;
    course: {
        id: number;
        title: string;
        slug: string;
        category: string | null;
        deliveryMode: string;
        url: string;
    };
    package: {
        name: string | null;
        packageType: string | null;
        validityDays: number | null;
        includesCertificate: boolean | null;
    };
    progress: {
        completedLessons: number;
        totalLessons: number;
        percentage: number;
    };
    modules: CourseModule[];
    schedule: ScheduleSession[];
    exams: StudentExam[];
    certificates: StudentCertificate[];
    finance: {
        invoices: FinanceItem[];
        payments: FinanceItem[];
    };
};

export type CourseCatalogPackage = {
    id: number;
    name: string;
    packageType: string;
    currency: string;
    price: number;
    discountPrice: number | null;
};

export type CourseCatalogItem = {
    id: number;
    title: string;
    slug: string;
    category: string | null;
    summary: string | null;
    deliveryMode: string;
    currency: string;
    basePrice: number;
    validityDays: number | null;
    isFeatured: boolean;
    modulesCount: number;
    enrollmentsCount: number;
    lowestPackagePrice: number;
    url: string;
    packages: CourseCatalogPackage[];
};

export type StudentPortalProps = {
    profile: StudentProfile | null;
    summary: StudentSummary;
    enrollments: StudentEnrollment[];
    courseCatalog: CourseCatalogItem[];
};

export type StudentLessonViewerProps = {
    profile: StudentProfile | null;
    summary: StudentSummary;
    enrollment: StudentEnrollment;
    lesson: Lesson;
    previousLesson: Lesson | null;
    nextLesson: Lesson | null;
    courseCatalog: CourseCatalogItem[];
};

export type ExamAttemptQuestion = {
    id: number;
    topic: string | null;
    difficulty: string | null;
    type: string;
    questionText: string;
    options: { key: string; label: string }[];
    answer: string[];
    explanation: string | null;
};

export type StudentExamAttemptProps = {
    profile: {
        id: number;
        fullName: string;
        email: string;
    };
    attempt: {
        id: number;
        attemptNumber: number;
        result: string;
        score: string | number | null;
        durationSeconds: number | null;
        startedAt: string | null;
        expiresAt: string | null;
        submittedAt: string | null;
        isSubmitted: boolean;
        autosaveUrl: string;
        submitUrl: string;
    };
    enrollment: {
        id: number;
        courseTitle: string;
        courseSlug: string;
    };
    exam: {
        id: number;
        title: string;
        examType: string;
        durationMinutes: number;
        passMark: number;
        questionCount: number;
    };
    questions: ExamAttemptQuestion[];
};

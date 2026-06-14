interface Classroom {
    id: number;
    title: string;
    slug: string;
    unique_code: string;
    thumbnail_path: string;
    description: string | null;
    is_published: boolean;
    modules?: ClassroomModule[];
    educator?: {
        id: number;
        name: string;
        email: string;
    };
    enrollments?: {
        id: number;
        classroom_id: number;
        student_id: number;
        enrolled_at: string;
    }[];
}

interface LearningContent {
    id: number;
    title: string;
    content: string | null;
}

interface Quiz {
    id: number;
    title: string;
    description: string | null;
}

interface ModuleObject {
    id: number;
    module_id: number;
    object_id: number;
    object_type: string; // App\Models\LearningContent or App\Models\Quiz
    position: number;
    object?: LearningContent | Quiz;
}

interface ClassroomModule {
    id: number;
    classroom_id: number;
    title: string;
    position: number;
    objects?: ModuleObject[];
}

interface LessonObject {
    id: number;
    module_id: number;
    object_type: string;
    object_id: number;
    position: number;
    can_access: boolean;
    is_completed: boolean;
    object: {
        id: number;
        title: string;
        content?: any;
        description?: string;
        passing_grade?: number;
        highest_score?: number | null;
    };
}

interface Module {
    id: number;
    classroom_id: number;
    title: string;
    position: number;
    objects: LessonObject[];
}

interface QuizSubmission {
    id: number;
    score: number;
    submitted_at: string;
    is_passing: boolean;
}

export type { Classroom, ClassroomModule, ModuleObject, LearningContent, Quiz, QuizSubmission, Module, LessonObject };

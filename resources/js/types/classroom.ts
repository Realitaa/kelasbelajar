interface Classroom {
    id: number;
    title: string;
    slug: string;
    unique_code: string;
    thumbnail_path: string;
    description: string | null;
    is_published: boolean;
    modules?: ClassroomModule[];
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

export type { Classroom, ClassroomModule, ModuleObject, LearningContent, Quiz };

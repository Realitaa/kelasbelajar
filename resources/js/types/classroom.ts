interface Classroom {
    id: number;
    title: string;
    slug: string;
    unique_code: string;
    thumbnail_path: string;
    description: string | null;
    is_published: boolean;
}

export type { Classroom };

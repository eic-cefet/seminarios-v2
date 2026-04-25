import { z } from "zod";

export const USER_ROLES = ["admin", "teacher", "user"] as const;

export type UserRole = (typeof USER_ROLES)[number];

export const userFormSchema = z.object({
    name: z.string().min(1, "Nome é obrigatório").max(255),
    email: z.string().min(1, "Email é obrigatório").email("Email inválido"),
    password: z.string(),
    role: z.enum(USER_ROLES),
    student_data: z.object({
        course_name: z.string(),
        course_situation: z.string(),
        course_role: z.string(),
    }),
    speaker_data: z.object({
        slug: z.string(),
        institution: z.string(),
        description: z.string(),
    }),
});

export type UserFormData = z.infer<typeof userFormSchema>;

export const userFormDefaults: UserFormData = {
    name: "",
    email: "",
    password: "",
    role: "user",
    student_data: { course_name: "", course_situation: "", course_role: "" },
    speaker_data: { slug: "", institution: "", description: "" },
};

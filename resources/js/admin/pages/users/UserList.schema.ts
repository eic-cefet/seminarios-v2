import { z } from "zod";

export const USER_ROLES = ["admin", "teacher", "user"] as const;

export type UserRole = (typeof USER_ROLES)[number];

const baseUserFormSchema = z.object({
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

export const updateUserFormSchema = baseUserFormSchema;

export const createUserFormSchema = baseUserFormSchema.superRefine((data, ctx) => {
    if (data.password.length < 8) {
        ctx.addIssue({
            code: z.ZodIssueCode.custom,
            path: ["password"],
            message: "A senha deve ter pelo menos 8 caracteres",
        });
    }
});

/**
 * @deprecated Use `createUserFormSchema` or `updateUserFormSchema` based on dialog mode.
 *             Kept as the permissive base shape for the inferred form data type.
 */
export const userFormSchema = baseUserFormSchema;

export type UserFormData = z.infer<typeof baseUserFormSchema>;

export const userFormDefaults: UserFormData = {
    name: "",
    email: "",
    password: "",
    role: "user",
    student_data: { course_name: "", course_situation: "", course_role: "" },
    speaker_data: { slug: "", institution: "", description: "" },
};

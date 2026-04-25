import { z } from "zod";

export const COURSE_SITUATION_OPTIONS = ["studying", "graduated"] as const;
export const COURSE_ROLE_OPTIONS = ["Aluno", "Professor", "Outro"] as const;

const CONSENT_REQUIRED_MESSAGE =
    "Você precisa aceitar os Termos de Uso e a Política de Privacidade.";
const COURSE_REQUIRED_MESSAGE = "Preencha todos os campos obrigatórios";

export const registerSchema = z
    .object({
        name: z.string().min(1, "Nome é obrigatório"),
        email: z
            .string()
            .min(1, "E-mail é obrigatório")
            .email("E-mail inválido"),
        password: z.string().min(8, "A senha deve ter pelo menos 8 caracteres"),
        passwordConfirmation: z.string().min(1, "Confirme sua senha"),
        courseSituation: z.string(),
        courseRole: z.string(),
        courseId: z.string(),
        acceptedTerms: z.boolean(),
        acceptedPrivacy: z.boolean(),
    })
    .superRefine((data, ctx) => {
        if (
            !COURSE_SITUATION_OPTIONS.includes(
                data.courseSituation as (typeof COURSE_SITUATION_OPTIONS)[number],
            )
        ) {
            ctx.addIssue({
                code: z.ZodIssueCode.custom,
                path: ["courseSituation"],
                message: COURSE_REQUIRED_MESSAGE,
            });
        }

        if (
            !COURSE_ROLE_OPTIONS.includes(
                data.courseRole as (typeof COURSE_ROLE_OPTIONS)[number],
            )
        ) {
            ctx.addIssue({
                code: z.ZodIssueCode.custom,
                path: ["courseRole"],
                message: COURSE_REQUIRED_MESSAGE,
            });
        }

        if (
            data.password &&
            data.passwordConfirmation &&
            data.password !== data.passwordConfirmation
        ) {
            ctx.addIssue({
                code: z.ZodIssueCode.custom,
                path: ["passwordConfirmation"],
                message: "As senhas não coincidem",
            });
        }

        if (!data.acceptedTerms) {
            ctx.addIssue({
                code: z.ZodIssueCode.custom,
                path: ["acceptedTerms"],
                message: CONSENT_REQUIRED_MESSAGE,
            });
        }

        if (!data.acceptedPrivacy) {
            ctx.addIssue({
                code: z.ZodIssueCode.custom,
                path: ["acceptedPrivacy"],
                message: CONSENT_REQUIRED_MESSAGE,
            });
        }
    });

export type RegisterFormValues = z.infer<typeof registerSchema>;

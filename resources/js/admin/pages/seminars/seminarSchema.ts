import { z } from "zod";

export const seminarSchema = z.object({
    name: z.string().min(1, "Nome é obrigatório").max(255),
    description: z.string().optional(),
    scheduled_at: z.string().min(1, "Data é obrigatória"),
    room_link: z.string().url("URL inválida").max(500).optional().or(z.literal("")),
    active: z.boolean(),
    seminar_location_id: z.number({ required_error: "Local é obrigatório" }),
    seminar_type_id: z.number().optional(),
    workshop_id: z.number().optional(),
    subject_names: z
        .array(z.string())
        .min(1, "Selecione pelo menos uma disciplina"),
    speaker_ids: z
        .array(z.number())
        .min(1, "Selecione pelo menos um palestrante"),
});

export type SeminarFormData = z.infer<typeof seminarSchema>;

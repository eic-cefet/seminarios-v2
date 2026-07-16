<?php

namespace App\Gamification;

use App\Enums\BadgeKey;

final class BadgeCatalog
{
    /**
     * @return array<string, BadgeDefinition>
     */
    public function all(): array
    {
        $definitions = [
            new BadgeDefinition(BadgeKey::FirstPresence, 'Primeiro Passo', 'Participe de uma apresentação.', 'Participação', 'bronze', 'attendance_count', 1, 'Footprints'),
            new BadgeDefinition(BadgeKey::Attendance5, 'Pegando Ritmo', 'Participe de 5 apresentações.', 'Participação', 'bronze', 'attendance_count', 5, 'Flame'),
            new BadgeDefinition(BadgeKey::Attendance10, 'Presença Marcante', 'Participe de 10 apresentações.', 'Participação', 'silver', 'attendance_count', 10, 'Medal'),
            new BadgeDefinition(BadgeKey::Attendance25, 'Habitante da EIC', 'Participe de 25 apresentações.', 'Participação', 'gold', 'attendance_count', 25, 'Landmark'),
            new BadgeDefinition(BadgeKey::Attendance50, 'Veterano de Auditório', 'Participe de 50 apresentações.', 'Participação', 'platinum', 'attendance_count', 50, 'Crown'),
            new BadgeDefinition(BadgeKey::Attendance100, 'Lenda das Apresentações', 'Participe de 100 apresentações.', 'Participação', 'platinum', 'attendance_count', 100, 'Trophy'),
            new BadgeDefinition(BadgeKey::Subjects3, 'Mente Curiosa', 'Participe de apresentações sobre 3 tópicos distintos.', 'Exploração', 'bronze', 'distinct_subjects', 3, 'Search'),
            new BadgeDefinition(BadgeKey::Subjects5, 'Explorador de Tópicos', 'Participe de apresentações sobre 5 tópicos distintos.', 'Exploração', 'bronze', 'distinct_subjects', 5, 'Compass'),
            new BadgeDefinition(BadgeKey::Subjects10, 'Conexões do Conhecimento', 'Participe de apresentações sobre 10 tópicos distintos.', 'Exploração', 'silver', 'distinct_subjects', 10, 'Network'),
            new BadgeDefinition(BadgeKey::Subjects20, 'Polímata da EIC', 'Participe de apresentações sobre 20 tópicos distintos.', 'Exploração', 'platinum', 'distinct_subjects', 20, 'Brain'),
            new BadgeDefinition(BadgeKey::Types3, 'Formatos sem Fronteiras', 'Participe de apresentações de 3 tipos distintos.', 'Exploração', 'silver', 'distinct_types', 3, 'Shapes'),
            new BadgeDefinition(BadgeKey::Speakers5, 'Cinco Perspectivas', 'Participe de apresentações com 5 palestrantes distintos.', 'Exploração', 'bronze', 'distinct_speakers', 5, 'Users'),
            new BadgeDefinition(BadgeKey::Speakers15, 'Vozes da Comunidade', 'Participe de apresentações com 15 palestrantes distintos.', 'Exploração', 'gold', 'distinct_speakers', 15, 'AudioLines'),
            new BadgeDefinition(BadgeKey::DoubleDay, 'Dose Dupla', 'Participe de 2 apresentações no mesmo dia.', 'Ritmo', 'bronze', 'max_attendances_day', 2, 'CopyPlus'),
            new BadgeDefinition(BadgeKey::TripleDay, 'Maratona de Conhecimento', 'Participe de 3 apresentações no mesmo dia.', 'Ritmo', 'special', 'max_attendances_day', 3, 'Gauge'),
            new BadgeDefinition(BadgeKey::Week5, 'Semana Intensiva', 'Participe de 5 apresentações na mesma semana.', 'Ritmo', 'silver', 'max_attendances_week', 5, 'CalendarDays'),
            new BadgeDefinition(BadgeKey::Month8, 'Mês em Foco', 'Participe de 8 apresentações no mesmo mês.', 'Ritmo', 'gold', 'max_attendances_month', 8, 'CalendarRange'),
            new BadgeDefinition(BadgeKey::Semester10, 'Semestre em Movimento', 'Participe de 10 apresentações no mesmo semestre.', 'Ritmo', 'gold', 'max_attendances_semester', 10, 'GraduationCap'),
            new BadgeDefinition(BadgeKey::Year20, 'Ano de Ouro', 'Participe de 20 apresentações no mesmo ano.', 'Ritmo', 'platinum', 'max_attendances_year', 20, 'Sparkles'),
            new BadgeDefinition(BadgeKey::Semesters2, 'Sempre Presente', 'Participe de apresentações em 2 semestres distintos.', 'Constância', 'bronze', 'active_semesters', 2, 'Repeat2'),
            new BadgeDefinition(BadgeKey::Semesters4, 'Jornada Contínua', 'Participe de apresentações em 4 semestres distintos.', 'Constância', 'silver', 'active_semesters', 4, 'Route'),
            new BadgeDefinition(BadgeKey::Semesters6, 'Legado EIC', 'Participe de apresentações em 6 semestres distintos.', 'Constância', 'platinum', 'active_semesters', 6, 'Milestone'),
            new BadgeDefinition(BadgeKey::FirstWorkshop, 'Workshop Concluído', 'Conclua um workshop.', 'Workshops', 'bronze', 'completed_workshops', 1, 'Wrench'),
            new BadgeDefinition(BadgeKey::Workshops3, 'Mão na Massa', 'Conclua 3 workshops.', 'Workshops', 'silver', 'completed_workshops', 3, 'Hammer'),
            new BadgeDefinition(BadgeKey::Workshops5, 'Mestre de Workshops', 'Conclua 5 workshops.', 'Workshops', 'platinum', 'completed_workshops', 5, 'BadgeCheck'),
            new BadgeDefinition(BadgeKey::FirstEvaluation, 'Primeira Impressão', 'Avalie uma apresentação.', 'Contribuição', 'bronze', 'evaluation_count', 1, 'MessageSquare'),
            new BadgeDefinition(BadgeKey::Evaluations5, 'Voz Ativa', 'Avalie 5 apresentações.', 'Contribuição', 'bronze', 'evaluation_count', 5, 'MessagesSquare'),
            new BadgeDefinition(BadgeKey::Evaluations10, 'Feedback que Transforma', 'Avalie 10 apresentações.', 'Contribuição', 'silver', 'evaluation_count', 10, 'MessageCircleMore'),
            new BadgeDefinition(BadgeKey::Evaluations25, 'Conselheiro da Comunidade', 'Avalie 25 apresentações.', 'Contribuição', 'gold', 'evaluation_count', 25, 'HeartHandshake'),
            new BadgeDefinition(BadgeKey::FeedbackChampion, 'Compromisso com a Melhoria', 'Participe de pelo menos 10 apresentações e avalie pelo menos 90% delas.', 'Contribuição', 'special', 'feedback_champion', 1, 'Award'),
        ];

        $catalog = [];

        foreach ($definitions as $definition) {
            $catalog[$definition->key->value] = $definition;
        }

        return $catalog;
    }

    /**
     * @return array<BadgeKey>
     */
    public function earnedBy(GamificationSnapshot $snapshot): array
    {
        $earned = [];

        foreach ($this->all() as $definition) {
            if ($snapshot->metric($definition->metric) >= $definition->threshold) {
                $earned[] = $definition->key;
            }
        }

        return $earned;
    }
}

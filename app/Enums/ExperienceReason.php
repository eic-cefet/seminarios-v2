<?php

namespace App\Enums;

enum ExperienceReason: string
{
    case Attendance = 'attendance';
    case Evaluation = 'evaluation';
    case NewSubject = 'new_subject';
    case WorkshopCompletion = 'workshop_completion';
    case BadgeBonus = 'badge_bonus';
}

<?php

namespace App\Enums;

enum BadgeKey: string
{
    case FirstPresence = 'first_presence';
    case Attendance5 = 'attendance_5';
    case Attendance10 = 'attendance_10';
    case Attendance25 = 'attendance_25';
    case Attendance50 = 'attendance_50';
    case Attendance100 = 'attendance_100';
    case Subjects3 = 'subjects_3';
    case Subjects5 = 'subjects_5';
    case Subjects10 = 'subjects_10';
    case Subjects20 = 'subjects_20';
    case Types3 = 'types_3';
    case Speakers5 = 'speakers_5';
    case Speakers15 = 'speakers_15';
    case DoubleDay = 'double_day';
    case TripleDay = 'triple_day';
    case Week5 = 'week_5';
    case Month8 = 'month_8';
    case Semester10 = 'semester_10';
    case Year20 = 'year_20';
    case Semesters2 = 'semesters_2';
    case Semesters4 = 'semesters_4';
    case Semesters6 = 'semesters_6';
    case FirstWorkshop = 'first_workshop';
    case Workshops3 = 'workshops_3';
    case Workshops5 = 'workshops_5';
    case FirstEvaluation = 'first_evaluation';
    case Evaluations5 = 'evaluations_5';
    case Evaluations10 = 'evaluations_10';
    case Evaluations25 = 'evaluations_25';
    case FeedbackChampion = 'feedback_champion';
}

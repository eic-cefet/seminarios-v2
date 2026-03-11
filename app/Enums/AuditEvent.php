<?php

namespace App\Enums;

enum AuditEvent: string
{
    // Auth
    case UserLogin = 'user.login';
    case UserLogout = 'user.logout';
    case UserRegister = 'user.register';
    case UserForgotPassword = 'user.forgot_password';
    case UserPasswordReset = 'user.password_reset';
    case UserSocialLogin = 'user.social_login';

    // Presence
    case PresenceRegistered = 'presence.registered';

    // Admin actions
    case SubjectsMerged = 'subjects.merged';
    case ReportGenerated = 'report.generated';

    // AI
    case AiTextTransform = 'ai.text_transform';
    case AiSuggestMergeName = 'ai.suggest_merge_name';

    // Bug reports
    case BugReportSubmitted = 'bug_report.submitted';

    // System (jobs/commands)
    case CertificateGenerated = 'certificate.generated';
    case SentimentAnalysisCompleted = 'sentiment_analysis.completed';
    case CertificatesProcessed = 'command.certificates_processed';
    case MissingCertificatesProcessed = 'command.missing_certificates_processed';
    case EvaluationRemindersSent = 'command.evaluation_reminders_sent';
    case SeminarRemindersSent = 'command.seminar_reminders_sent';
    case SeminarRescheduled = 'seminar.rescheduled';
}

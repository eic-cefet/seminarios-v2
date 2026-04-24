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
    case UserCommunicationPreferencesUpdated = 'user.communication_preferences.updated';

    // Presence
    case PresenceRegistered = 'presence.registered';

    // Admin actions
    case SubjectsMerged = 'subjects.merged';
    case ReportQueued = 'report.queued';
    case ReportGenerated = 'report.generated';

    // AI
    case AiTextTransform = 'ai.text_transform';
    case AiSuggestMergeName = 'ai.suggest_merge_name';
    case AiSuggestSubjectTags = 'ai.suggest_subject_tags';

    // API Tokens
    case ApiTokenCreated = 'api_token.created';
    case ApiTokenUpdated = 'api_token.updated';
    case ApiTokenRegenerated = 'api_token.regenerated';
    case ApiTokenRevoked = 'api_token.revoked';

    // Bug reports
    case BugReportSubmitted = 'bug_report.submitted';

    // System (jobs/commands)
    case CertificateGenerated = 'certificate.generated';
    case SentimentAnalysisCompleted = 'sentiment_analysis.completed';
    case CertificatesProcessed = 'command.certificates_processed';
    case MissingCertificatesProcessed = 'command.missing_certificates_processed';
    case EvaluationRemindersSent = 'command.evaluation_reminders_sent';
    case SeminarRemindersSent = 'command.seminar_reminders_sent';
    case AiSubjectsMerged = 'command.ai_subjects_merged';
    case AiSubjectsNormalized = 'command.ai_subjects_normalized';
    case OrphanSubjectsCleanedUp = 'command.orphan_subjects_cleaned_up';
    case SeminarRescheduled = 'seminar.rescheduled';
    case SeminarAlertDispatched = 'seminar_alert.dispatched';
    case S3FileDeleted = 's3.file_deleted';
    case EmailSent = 'email.sent';

    // MFA
    case UserMfaEnabled = 'user.mfa_enabled';
    case UserMfaDisabled = 'user.mfa_disabled';
    case UserMfaConfirmed = 'user.mfa_confirmed';
    case UserMfaUsed = 'user.mfa_used';
    case UserMfaChallengeFailed = 'user.mfa_challenge_failed';
    case UserMfaRecoveryCodeUsed = 'user.mfa_recovery_code_used';
    case UserMfaRecoveryCodesRegenerated = 'user.mfa_recovery_codes_regenerated';
    case UserMfaDeviceRemembered = 'user.mfa_device_remembered';
    case UserMfaDeviceRevoked = 'user.mfa_device_revoked';

    // LGPD
    case ConsentGranted = 'lgpd.consent_granted';
    case ConsentRevoked = 'lgpd.consent_revoked';
    case DataExportRequested = 'lgpd.data_export_requested';
    case DataExportDelivered = 'lgpd.data_export_delivered';
    case DataExportFailed = 'lgpd.data_export_failed';
    case AccountDeletionConfirmationSent = 'lgpd.account_deletion_confirmation_sent';
    case AccountDeletionRequested = 'lgpd.account_deletion_requested';
    case AccountDeletionCancelled = 'lgpd.account_deletion_cancelled';
    case AccountDeletionExecuted = 'lgpd.account_deletion_executed';
    case AccountAnonymized = 'lgpd.account_anonymized';
    case LgpdRetentionPurged = 'lgpd.retention_purged';
}

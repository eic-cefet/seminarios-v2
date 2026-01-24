import ReCAPTCHA from "react-google-recaptcha";
import { forwardRef } from "react";

interface ReCaptchaProps {
    onVerify: (token: string | null) => void;
    onExpire?: () => void;
}

export function isRecaptchaEnabled(): boolean {
    return Boolean(app.RECAPTCHA_SITE_KEY);
}

export const ReCaptcha = forwardRef<ReCAPTCHA, ReCaptchaProps>(
    ({ onVerify, onExpire }, ref) => {
        if (!isRecaptchaEnabled()) {
            return null;
        }

        return (
            <div className="flex justify-center">
                <ReCAPTCHA
                    ref={ref}
                    sitekey={app.RECAPTCHA_SITE_KEY}
                    onChange={onVerify}
                    onExpired={onExpire}
                />
            </div>
        );
    },
);

ReCaptcha.displayName = "ReCaptcha";

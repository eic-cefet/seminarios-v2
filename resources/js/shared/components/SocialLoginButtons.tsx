import { GoogleIcon, GithubIcon } from "@shared/components/icons/SocialIcons";

interface SocialLoginButtonsProps {
    onSocialLogin: (provider: "google" | "github") => void;
    actionLabel?: string;
    dividerText?: string;
    dividerBgColor?: string;
}

export function SocialLoginButtons({
    onSocialLogin,
    actionLabel = "Continuar",
    dividerText = "ou",
    dividerBgColor = "bg-white",
}: SocialLoginButtonsProps) {
    return (
        <>
            <div className="space-y-3">
                <button
                    type="button"
                    onClick={() => onSocialLogin("google")}
                    className="flex w-full items-center justify-center gap-3 rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors cursor-pointer"
                >
                    <GoogleIcon className="h-5 w-5" />
                    {actionLabel} com Google
                </button>
                <button
                    type="button"
                    onClick={() => onSocialLogin("github")}
                    className="flex w-full items-center justify-center gap-3 rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors cursor-pointer"
                >
                    <GithubIcon className="h-5 w-5" />
                    {actionLabel} com GitHub
                </button>
            </div>

            <div className="relative">
                <div className="absolute inset-0 flex items-center">
                    <div className="w-full border-t border-gray-200" />
                </div>
                <div className="relative flex justify-center text-sm">
                    <span className={`${dividerBgColor} px-2 text-gray-500`}>
                        {dividerText}
                    </span>
                </div>
            </div>
        </>
    );
}

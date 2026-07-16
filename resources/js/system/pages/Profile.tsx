import { PageTitle } from "@shared/components/PageTitle";
import { useAuth } from "@shared/contexts/AuthContext";
import { CertificatesSection } from "../components/profile/CertificatesSection";
import { PasswordSection } from "../components/profile/PasswordSection";
import { TwoFactorSection } from "../components/profile/TwoFactorSection";
import { ProfileInfoSection } from "../components/profile/ProfileInfoSection";
import { RegistrationsSection } from "../components/profile/RegistrationsSection";
import { StudentDataSection } from "../components/profile/StudentDataSection";
import { PrivacySection } from "../components/profile/PrivacySection";
import { Layout } from "../components/Layout";
import { ProtectedRoute } from "../components/ProtectedRoute";
import { ROUTES } from "@shared/config/routes";
import { Trophy } from "lucide-react";
import { Link } from "react-router-dom";

export default function Profile() {
    const { user, refreshUser } = useAuth();

    return (
        <ProtectedRoute>
            <PageTitle title="Perfil" />
            <Layout>
                <div className="bg-white border-b border-gray-200">
                    <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
                        <h1 className="text-2xl font-bold text-gray-900">
                            Meu Perfil
                        </h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Gerencie suas informações pessoais e configurações
                        </p>
                    </div>
                </div>

                <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8 space-y-8">
                    <Link
                        to={ROUTES.SYSTEM.ACHIEVEMENTS}
                        aria-label="Ver minhas conquistas"
                        className="group flex items-center justify-between gap-4 rounded-xl border border-primary-200 bg-primary-50 p-5 transition-colors hover:border-primary-300 hover:bg-primary-100 dark:border-primary-800 dark:bg-primary-950 dark:hover:bg-primary-900"
                    >
                        <div className="flex items-center gap-4">
                            <span className="flex h-11 w-11 items-center justify-center rounded-full bg-primary-600 text-white">
                                <Trophy className="h-5 w-5" aria-hidden="true" />
                            </span>
                            <span>
                                <span className="block font-semibold text-gray-900 dark:text-gray-100">
                                    Minhas conquistas
                                </span>
                                <span className="mt-1 block text-sm text-gray-600 dark:text-gray-300">
                                    Veja seu nível, XP e coleção de emblemas.
                                </span>
                            </span>
                        </div>
                        <span className="text-sm font-semibold text-primary-700 group-hover:underline dark:text-primary-300">
                            Ver galeria
                        </span>
                    </Link>
                    <ProfileInfoSection user={user!} onUpdate={refreshUser} />
                    <StudentDataSection user={user!} onUpdate={refreshUser} />
                    <PasswordSection />
                    <TwoFactorSection />
                    <RegistrationsSection />
                    <CertificatesSection />
                    <PrivacySection user={user!} onUpdate={refreshUser} />
                </div>
            </Layout>
        </ProtectedRoute>
    );
}

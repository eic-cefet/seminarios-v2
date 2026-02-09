import { useState, useRef } from "react";
import { Link } from "react-router-dom";
import * as Label from "@radix-ui/react-label";
import ReCAPTCHA from "react-google-recaptcha";
import { Bug, Upload, X, FileText, Image } from "lucide-react";
import { Layout } from "../components/Layout";
import { PageTitle } from "@shared/components/PageTitle";
import { ReCaptcha, isRecaptchaEnabled } from "@shared/components/ReCaptcha";
import { cn } from "@shared/lib/utils";
import { useAuth } from "@shared/contexts/AuthContext";
import { getErrorMessage } from "@shared/lib/errors";
import { bugReportApi } from "@shared/api/client";

const MAX_FILES = 3;
const MAX_FILE_SIZE = 1024 * 1024; // 1MB
const ALLOWED_TYPES = ["image/jpeg", "image/png", "image/gif", "application/pdf"];

export default function BugReport() {
    const { user } = useAuth();
    const recaptchaRef = useRef<ReCAPTCHA>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const [formData, setFormData] = useState({
        subject: "",
        message: "",
        name: user?.name || "",
        email: user?.email || "",
    });
    const [files, setFiles] = useState<File[]>([]);
    const [captchaToken, setCaptchaToken] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState(false);

    const handleChange = (
        e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>,
    ) => {
        setFormData((prev) => ({
            ...prev,
            [e.target.name]: e.target.value,
        }));
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const selectedFiles = Array.from(e.target.files || []);
        const validFiles: File[] = [];
        const errors: string[] = [];

        for (const file of selectedFiles) {
            if (files.length + validFiles.length >= MAX_FILES) {
                errors.push(`Máximo de ${MAX_FILES} arquivos permitidos`);
                break;
            }
            if (!ALLOWED_TYPES.includes(file.type)) {
                errors.push(`${file.name}: tipo de arquivo não permitido`);
                continue;
            }
            if (file.size > MAX_FILE_SIZE) {
                errors.push(`${file.name}: arquivo maior que 1MB`);
                continue;
            }
            validFiles.push(file);
        }

        if (errors.length > 0) {
            setError(errors.join(". "));
        }

        setFiles((prev) => [...prev, ...validFiles]);

        // Reset file input so the same file can be re-selected
        fileInputRef.current && (fileInputRef.current.value = "");
    };

    const removeFile = (index: number) => {
        setFiles((prev) => prev.filter((_, i) => i !== index));
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError(null);

        if (isRecaptchaEnabled() && !captchaToken) {
            setError("Por favor, complete o captcha");
            return;
        }

        if (!formData.subject.trim() || !formData.message.trim()) {
            setError("Assunto e mensagem são obrigatórios");
            return;
        }

        setLoading(true);

        try {
            await bugReportApi.submit({
                subject: formData.subject,
                message: formData.message,
                name: formData.name || undefined,
                email: formData.email || undefined,
                files: files.length > 0 ? files : undefined,
            });

            setSuccess(true);
            setFormData({ subject: "", message: "", name: "", email: "" });
            setFiles([]);
            setCaptchaToken(null);
            recaptchaRef.current?.reset();
        } catch (err) {
            setError(getErrorMessage(err));
            recaptchaRef.current?.reset();
            setCaptchaToken(null);
        } finally {
            setLoading(false);
        }
    };

    const getFileIcon = (file: File) => {
        if (file.type === "application/pdf") {
            return <FileText className="h-4 w-4" />;
        }
        return <Image className="h-4 w-4" />;
    };

    if (success) {
        return (
            <>
                <PageTitle title="Bug Reportado" />
                <Layout>
                    <div className="flex min-h-[calc(100vh-4rem-4rem)] items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
                        <div className="w-full max-w-md text-center space-y-6">
                            <div className="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                                <Bug className="h-8 w-8 text-green-600" />
                            </div>
                            <h2 className="text-2xl font-bold text-gray-900">
                                Bug reportado com sucesso!
                            </h2>
                            <p className="text-gray-600">
                                Obrigado por nos ajudar a melhorar o sistema. Sua
                                mensagem foi enviada e será analisada em breve.
                            </p>
                            <div className="flex justify-center gap-4">
                                <button
                                    onClick={() => setSuccess(false)}
                                    className="px-4 py-2 text-sm font-medium text-primary-600 hover:text-primary-700"
                                >
                                    Reportar outro bug
                                </button>
                                <Link
                                    to="/"
                                    className="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700"
                                >
                                    Voltar ao início
                                </Link>
                            </div>
                        </div>
                    </div>
                </Layout>
            </>
        );
    }

    return (
        <>
            <PageTitle title="Reportar Bug" />
            <Layout>
                <div className="flex min-h-[calc(100vh-4rem-4rem)] items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
                    <div className="w-full max-w-lg space-y-8">
                        <div className="text-center">
                            <div className="mx-auto w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center mb-4">
                                <Bug className="h-6 w-6 text-primary-600" />
                            </div>
                            <h2 className="text-3xl font-bold tracking-tight text-gray-900">
                                Reportar Bug
                            </h2>
                            <p className="mt-2 text-sm text-gray-600">
                                Encontrou um problema? Ajude-nos a melhorar o
                                sistema reportando bugs.
                            </p>
                        </div>

                        <form onSubmit={handleSubmit} className="space-y-5">
                            {error && (
                                <div className="rounded-md bg-red-50 p-3 text-sm text-red-700">
                                    {error}
                                </div>
                            )}

                            <div>
                                <Label.Root
                                    htmlFor="subject"
                                    className="block text-sm font-medium text-gray-700"
                                >
                                    Assunto *
                                </Label.Root>
                                <input
                                    id="subject"
                                    name="subject"
                                    type="text"
                                    required
                                    value={formData.subject}
                                    onChange={handleChange}
                                    className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                    placeholder="Descreva o problema brevemente"
                                />
                            </div>

                            <div>
                                <Label.Root
                                    htmlFor="message"
                                    className="block text-sm font-medium text-gray-700"
                                >
                                    Mensagem *
                                </Label.Root>
                                <textarea
                                    id="message"
                                    name="message"
                                    required
                                    rows={5}
                                    value={formData.message}
                                    onChange={handleChange}
                                    className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 resize-none"
                                    placeholder="Descreva o problema em detalhes. Inclua os passos para reproduzir o bug, o que você esperava que acontecesse e o que realmente aconteceu."
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label.Root
                                        htmlFor="name"
                                        className="block text-sm font-medium text-gray-700"
                                    >
                                        Nome (opcional)
                                    </Label.Root>
                                    <input
                                        id="name"
                                        name="name"
                                        type="text"
                                        value={formData.name}
                                        onChange={handleChange}
                                        className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                        placeholder="Seu nome"
                                    />
                                </div>

                                <div>
                                    <Label.Root
                                        htmlFor="email"
                                        className="block text-sm font-medium text-gray-700"
                                    >
                                        E-mail (opcional)
                                    </Label.Root>
                                    <input
                                        id="email"
                                        name="email"
                                        type="email"
                                        value={formData.email}
                                        onChange={handleChange}
                                        className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                        placeholder="seu@email.com"
                                    />
                                </div>
                            </div>

                            <div>
                                <Label.Root className="block text-sm font-medium text-gray-700 mb-2">
                                    Anexos (opcional)
                                </Label.Root>
                                <p className="text-xs text-gray-500 mb-2">
                                    Máximo de {MAX_FILES} arquivos. Formatos
                                    permitidos: JPG, PNG, GIF, PDF. Tamanho máximo:
                                    1MB cada.
                                </p>

                                {files.length > 0 && (
                                    <div className="mb-3 space-y-2">
                                        {files.map((file, index) => (
                                            <div
                                                key={index}
                                                className="flex items-center justify-between p-2 bg-gray-50 rounded-md"
                                            >
                                                <div className="flex items-center gap-2 min-w-0">
                                                    {getFileIcon(file)}
                                                    <span className="text-sm text-gray-700 truncate">
                                                        {file.name}
                                                    </span>
                                                    <span className="text-xs text-gray-500">
                                                        ({(file.size / 1024).toFixed(1)} KB)
                                                    </span>
                                                </div>
                                                <button
                                                    type="button"
                                                    onClick={() => removeFile(index)}
                                                    className="p-1 text-gray-400 hover:text-red-500"
                                                >
                                                    <X className="h-4 w-4" />
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                )}

                                {files.length < MAX_FILES && (
                                    <div>
                                        <input
                                            ref={fileInputRef}
                                            type="file"
                                            multiple
                                            accept=".jpg,.jpeg,.png,.gif,.pdf"
                                            onChange={handleFileChange}
                                            className="hidden"
                                            id="file-upload"
                                        />
                                        <label
                                            htmlFor="file-upload"
                                            className="flex items-center justify-center gap-2 px-4 py-2 border border-gray-300 rounded-md cursor-pointer hover:bg-gray-50 transition-colors"
                                        >
                                            <Upload className="h-4 w-4 text-gray-500" />
                                            <span className="text-sm text-gray-600">
                                                Adicionar arquivo
                                            </span>
                                        </label>
                                    </div>
                                )}
                            </div>

                            <ReCaptcha
                                ref={recaptchaRef}
                                onVerify={setCaptchaToken}
                                onExpire={() => setCaptchaToken(null)}
                            />

                            <button
                                type="submit"
                                disabled={
                                    loading ||
                                    (isRecaptchaEnabled() && !captchaToken)
                                }
                                className={cn(
                                    "w-full rounded-md bg-primary-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors",
                                    (loading ||
                                        (isRecaptchaEnabled() &&
                                            !captchaToken)) &&
                                        "opacity-70 cursor-not-allowed",
                                )}
                            >
                                {loading ? "Enviando..." : "Enviar relatório"}
                            </button>
                        </form>

                        <p className="text-center text-sm text-gray-500">
                            <Link
                                to="/"
                                className="font-medium text-primary-600 hover:text-primary-700"
                            >
                                Voltar ao início
                            </Link>
                        </p>
                    </div>
                </div>
            </Layout>
        </>
    );
}

import { useState } from "react";
import { Tabs, TabsList, TabsTrigger, TabsContent } from "./ui/tabs";
import { Label } from "./ui/label";
import ReactMarkdown from "react-markdown";
import rehypeSanitize from "rehype-sanitize";
import { AiTextToolbar } from "./AiTextToolbar";

interface MarkdownEditorProps {
    value: string;
    onChange: (value: string) => void;
    label?: string;
    error?: string;
}

export function MarkdownEditor({
    value,
    onChange,
    label,
    error,
}: MarkdownEditorProps) {
    const [aiLoading, setAiLoading] = useState(false);

    return (
        <div className="space-y-2">
            <div className="flex items-center justify-between">
                {label && <Label>{label}</Label>}
                <AiTextToolbar
                    value={value}
                    onChange={onChange}
                    onLoadingChange={setAiLoading}
                />
            </div>
            <Tabs defaultValue="write" className="w-full">
                <TabsList>
                    <TabsTrigger value="write">Escrever</TabsTrigger>
                    <TabsTrigger value="preview">Visualizar</TabsTrigger>
                </TabsList>
                <TabsContent value="write">
                    <textarea
                        value={value}
                        onChange={(e) => onChange(e.target.value)}
                        disabled={aiLoading}
                        className="w-full min-h-[200px] px-3 py-2 border border-border rounded-md focus:outline-none focus:ring-2 focus:ring-ring bg-background text-foreground disabled:opacity-50 disabled:cursor-not-allowed"
                        placeholder="Escreva a descrição em Markdown..."
                    />
                </TabsContent>
                <TabsContent value="preview">
                    <div className="w-full min-h-[200px] px-3 py-2 border border-border rounded-md bg-background html-content">
                        <ReactMarkdown rehypePlugins={[rehypeSanitize]}>
                            {value || "*Nenhuma descrição*"}
                        </ReactMarkdown>
                    </div>
                </TabsContent>
            </Tabs>
            {error && <p className="text-sm text-red-500">{error}</p>}
        </div>
    );
}

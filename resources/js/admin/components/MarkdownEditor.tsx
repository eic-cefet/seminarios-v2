import { Tabs, TabsList, TabsTrigger, TabsContent } from "./ui/tabs";
import { Label } from "./ui/label";
import ReactMarkdown from "react-markdown";
import rehypeSanitize from "rehype-sanitize";

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
    return (
        <div className="space-y-2">
            {label && <Label>{label}</Label>}
            <Tabs defaultValue="write" className="w-full">
                <TabsList>
                    <TabsTrigger value="write">Escrever</TabsTrigger>
                    <TabsTrigger value="preview">Visualizar</TabsTrigger>
                </TabsList>
                <TabsContent value="write">
                    <textarea
                        value={value}
                        onChange={(e) => onChange(e.target.value)}
                        className="w-full min-h-[200px] px-3 py-2 border border-border rounded-md focus:outline-none focus:ring-2 focus:ring-ring bg-background text-foreground"
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

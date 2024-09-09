import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from "@/Components/ui/accordion";
import { Button } from "@/Components/ui/button";
import { Label } from "@/Components/ui/label";
import { Separator } from "@/Components/ui/separator";
import { Switch } from "@/Components/ui/switch";
import { Textarea } from "@/Components/ui/textarea";
import { useForm } from "@inertiajs/react";

export default function Index({
    suggestedIcons,
}: {
    suggestedIcons: {
        id: number;
        name: string;
        explanation: string;
        iconData: any;
    }[];
}) {
    const { data, setData, post, processing, errors } = useForm({
        copy: "",
        excludeUnmentionedLogos: true,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(route("icons.suggest"));
    }

    return (
        <>
            <header className="container max-w-4xl py-12 mt-8">
                <h1 className="text-muted-foreground">Icon Butler</h1>
                <p className="text-2xl font-semibold tracking-tight scroll-m-20">
                    Always serving you the best icons.
                </p>
            </header>
            <main className="container max-w-4xl py-8">
                <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
                    <div>
                        <Label>Your copy</Label>
                        <Textarea
                            placeholder="Paste your copy here."
                            value={data.copy}
                            onChange={(e) => setData("copy", e.target.value)}
                        />
                        {errors.copy && (
                            <p className="text-sm text-red-500">
                                {errors.copy}
                            </p>
                        )}
                    </div>
                    <Accordion type="single" collapsible>
                        <AccordionItem
                            value="advanced-settings"
                            className="border-b-0"
                        >
                            <AccordionTrigger>
                                Advanced settings
                            </AccordionTrigger>
                            <AccordionContent>
                                <div className="flex flex-col gap-2">
                                    <div className="flex items-center gap-2">
                                        <Switch
                                            id="exclude-logos"
                                            checked={
                                                data.excludeUnmentionedLogos
                                            }
                                            onCheckedChange={(e) =>
                                                setData(
                                                    "excludeUnmentionedLogos",
                                                    e,
                                                )
                                            }
                                        />
                                        <Label htmlFor="exclude-logos">
                                            Exclude company logos not explicitly
                                            mentioned
                                        </Label>
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        This usually results in a more accurate
                                        match.
                                    </p>
                                </div>
                            </AccordionContent>
                        </AccordionItem>
                    </Accordion>
                    <div className="flex justify-end">
                        <Button disabled={processing}>
                            {processing ? (
                                <div className="flex items-center gap-1">
                                    <div role="status">
                                        <svg
                                            aria-hidden="true"
                                            className="w-4 h-4 text-muted-foreground animate-spin fill-muted"
                                            viewBox="0 0 100 101"
                                            fill="none"
                                            xmlns="http://www.w3.org/2000/svg"
                                        >
                                            <path
                                                d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z"
                                                fill="currentColor"
                                            />
                                            <path
                                                d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z"
                                                fill="currentFill"
                                            />
                                        </svg>
                                        <span className="sr-only">
                                            Loading...
                                        </span>
                                    </div>
                                    <span>Working some magic…</span>
                                </div>
                            ) : (
                                "Suggest me some icons!"
                            )}
                        </Button>
                    </div>
                </form>
                {suggestedIcons && (
                    <>
                        <div className="py-8">
                            <Separator />
                        </div>
                        {suggestedIcons.map((icon) => (
                            <div key={icon.id} className="flex flex-col gap-4">
                                <h2 className="text-lg font-semibold">
                                    {icon.name}
                                </h2>
                                <p>{icon.explanation}</p>
                            </div>
                        ))}
                    </>
                )}
            </main>
        </>
    );
}

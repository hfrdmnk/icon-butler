import Spinner from "@/Components/spinner";
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from "@/Components/ui/accordion";
import { Button } from "@/Components/ui/button";
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from "@/Components/ui/card";
import { Label } from "@/Components/ui/label";
import { Separator } from "@/Components/ui/separator";
import { Switch } from "@/Components/ui/switch";
import { Textarea } from "@/Components/ui/textarea";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/Components/ui/tooltip";
import App from "@/Layouts/AppLayout";
import { PageProps } from "@/types";
import { Head, useForm } from "@inertiajs/react";
import { ClipboardIcon } from "@radix-ui/react-icons";
import { useEffect, useState } from "react";
import { toast } from "sonner";

export default function Index({
    auth,
    suggestedIcons,
    availableTypes,
}: PageProps<{
    suggestedIcons: Icon[];
    availableTypes: { id: string; name: string }[];
}>) {
    const [selectedType, setSelectedType] = useState<null | string>(null);

    const { data, setData, post, processing, errors } = useForm({
        copy: "",
        excludeUnmentionedLogos: true,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route("icons.suggest"));
    };

    const copyToClipboard = (icon: Icon) => {
        if (selectedType) {
            navigator.clipboard
                .writeText(icon.iconData[selectedType].svg_content)
                .then(() => {
                    toast.success(`SVG "${icon.name}" copied to clipboard`, {
                        description: "Now go create something awesome!",
                    });
                })
                .catch((err) => {
                    toast.error("Failed to copy SVG to clipboard");
                    console.error("Clipboard error:", err);
                });
        }
    };

    useEffect(() => {
        if (availableTypes?.length > 0) {
            const regularType = availableTypes.find(
                (type) => type.id === "regular",
            );
            if (regularType) {
                setSelectedType("regular");
            } else {
                setSelectedType(availableTypes[0].id);
            }
        } else {
            setSelectedType(null);
        }
    }, [availableTypes]);

    return (
        <App user={auth.user}>
            <Head title="Serving icons" />
            <header className="container max-w-3xl py-12 mt-8">
                <h1 className="text-muted-foreground">Icon Butler</h1>
                <p className="text-2xl font-semibold tracking-tight scroll-m-20">
                    Always serving you the best icons.
                </p>
            </header>
            <main className="container max-w-3xl py-8">
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
                                    <Spinner />
                                    <span>Working some magic…</span>
                                </div>
                            ) : (
                                "Suggest me some icons!"
                            )}
                        </Button>
                    </div>
                </form>
                <div className="py-8">
                    <Separator />
                </div>
                {suggestedIcons?.length > 0 && (
                    <>
                        <div className="py-8"></div>
                        <div className="grid grid-cols-2 gap-2">
                            {suggestedIcons.map((icon) => (
                                <Card key={icon.id} className="flex flex-col">
                                    <CardHeader>
                                        <div className="flex items-center gap-4">
                                            {selectedType && (
                                                <img
                                                    src={
                                                        icon.iconData[
                                                            selectedType
                                                        ].file_path
                                                    }
                                                    alt={icon.name}
                                                    className="w-8 h-8"
                                                />
                                            )}
                                            <CardTitle className="font-semibold">
                                                {icon.name}
                                            </CardTitle>
                                        </div>
                                    </CardHeader>
                                    <CardContent>
                                        <p className="text-muted-foreground">
                                            {icon.explanation}
                                        </p>
                                    </CardContent>
                                    <CardFooter className="mt-auto">
                                        <TooltipProvider>
                                            <Tooltip>
                                                <TooltipTrigger asChild>
                                                    <Button
                                                        variant={"outline"}
                                                        onClick={() =>
                                                            copyToClipboard(
                                                                icon,
                                                            )
                                                        }
                                                    >
                                                        <ClipboardIcon className="w-4 h-4" />
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <p>Copy SVG to clipboard</p>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                    </CardFooter>
                                </Card>
                            ))}
                        </div>
                    </>
                )}
            </main>
        </App>
    );
}

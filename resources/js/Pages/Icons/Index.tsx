import IconCard from "@/Components/iconCard";
import IconCardSkeleton from "@/Components/iconCardSkeleton";
import Spinner from "@/Components/spinner";
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from "@/Components/ui/accordion";
import { badgeVariants } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Label } from "@/Components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import { Separator } from "@/Components/ui/separator";
import { Skeleton } from "@/Components/ui/skeleton";
import { Switch } from "@/Components/ui/switch";
import { Textarea } from "@/Components/ui/textarea";
import App from "@/Layouts/AppLayout";
import { cn } from "@/lib/utils";
import { PageProps } from "@/types";
import { Head, useForm, usePage } from "@inertiajs/react";
import { useEffect, useState } from "react";
import { useHotkeys } from "react-hotkeys-hook";
import { toast } from "sonner";

export default function Index({
    auth,
    suggestedIcons,
    availableTypes,
}: PageProps<{
    suggestedIcons: Icon[];
    availableTypes: IconType[];
}>) {
    const { flash } = usePage<PageProps>().props;
    const [selectedType, setSelectedType] = useState<null | string>(null);

    useHotkeys("mod+enter", () => handleSubmit(new Event("submit")), {
        enableOnFormTags: true,
    });

    useEffect(() => {
        if (flash.error) {
            toast.error(flash.error);
        }
        if (flash.message) {
            toast.success(flash.message);
        }
    }, [flash]);

    const { data, setData, post, processing, errors } = useForm({
        copy: "",
        excludeUnmentionedLogos: true,
    });

    const handleSubmit = (e: React.FormEvent | Event) => {
        e.preventDefault();
        post(route("icons.suggest"));
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
                <div className="mt-4">
                    <a
                        href="https://phosphoricons.com/"
                        target="_blank"
                        className={badgeVariants({ variant: "outline" })}
                    >
                        Powered by Phosphor Icons
                    </a>
                </div>
            </header>
            <main className="container max-w-3xl py-8">
                <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center justify-between h-9">
                            <Label>Your copy</Label>
                            {data.copy.length > 0 ? (
                                <Button
                                    variant={"link"}
                                    onClick={(e) => {
                                        e.preventDefault();
                                        setData("copy", "");
                                    }}
                                    className="px-0"
                                >
                                    Clear textarea
                                </Button>
                            ) : (
                                <Button
                                    variant={"link"}
                                    onClick={(e) => {
                                        e.preventDefault();
                                        navigator.clipboard
                                            .readText()
                                            .then((text) => {
                                                setData("copy", text);
                                            });
                                    }}
                                    className="px-0"
                                >
                                    Paste from clipboard
                                </Button>
                            )}
                        </div>
                        <Textarea
                            placeholder="Paste your copy here."
                            value={data.copy}
                            onChange={(e) => setData("copy", e.target.value)}
                            className="min-h-32 max-h-96"
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
                        <div className="flex flex-col items-center gap-1">
                            <Button
                                disabled={processing || data.copy.length < 3}
                                type="submit"
                            >
                                {processing ? (
                                    <div className="flex items-center gap-1">
                                        <Spinner />
                                        <span>Working some magic…</span>
                                    </div>
                                ) : (
                                    "Suggest me some icons!"
                                )}
                            </Button>
                            <p
                                className={cn([
                                    "text-sm text-muted-foreground",
                                    data.copy.length < 3 && "opacity-0",
                                ])}
                            >
                                ⌘ + enter
                            </p>
                        </div>
                    </div>
                </form>
                <div className="py-8">
                    <Separator />
                </div>
                {!suggestedIcons && !processing && (
                    <div className="flex items-center justify-center p-16 text-sm text-center border border-dashed rounded select-none min-h-96 text-muted-foreground">
                        Paste your copy above to get some icon suggestions.
                    </div>
                )}
                {processing && (
                    <>
                        <div className="mb-8">
                            <Skeleton className="w-24 h-6" />
                        </div>
                        <div className="grid grid-cols-2 gap-2">
                            <IconCardSkeleton />
                            <IconCardSkeleton />
                            <IconCardSkeleton />
                            <IconCardSkeleton />
                        </div>
                    </>
                )}
                {suggestedIcons?.length > 0 && !processing && (
                    <>
                        <div className="mb-8">
                            {selectedType && (
                                <div className="flex flex-col gap-2">
                                    <Label>Icon type</Label>
                                    <Select
                                        value={selectedType}
                                        onValueChange={(e) =>
                                            setSelectedType(e)
                                        }
                                    >
                                        <SelectTrigger className="w-[180px]">
                                            <SelectValue placeholder="Icon Type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {availableTypes.map(
                                                (type: IconType) => {
                                                    return (
                                                        <SelectItem
                                                            key={type.id}
                                                            value={type.id}
                                                        >
                                                            {type.name}
                                                        </SelectItem>
                                                    );
                                                },
                                            )}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}
                        </div>
                        <div className="grid grid-cols-2 gap-2">
                            {suggestedIcons.map((icon) => (
                                <IconCard
                                    key={icon.id}
                                    icon={icon}
                                    selectedType={selectedType ?? ""}
                                />
                            ))}
                        </div>
                    </>
                )}
            </main>
        </App>
    );
}

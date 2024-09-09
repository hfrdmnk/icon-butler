import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from "@/Components/ui/accordion";
import { Button } from "@/Components/ui/button";
import { Label } from "@/Components/ui/label";
import { Switch } from "@/Components/ui/switch";
import { Textarea } from "@/Components/ui/textarea";

export default function Index() {
    return (
        <>
            <header className="container max-w-4xl py-12 mt-8">
                <h1 className="text-muted-foreground">Icon Butler</h1>
                <p className="text-2xl font-semibold tracking-tight scroll-m-20">
                    Always serving you the best icons.
                </p>
            </header>
            <main className="container max-w-4xl py-8">
                <form className="flex flex-col gap-4">
                    <div>
                        <Label>Your copy</Label>
                        <Textarea placeholder="Paste your copy here." />
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
                                        <Switch id="exclude-logos" />
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
                        <Button>Suggest me some icons!</Button>
                    </div>
                </form>
            </main>
        </>
    );
}

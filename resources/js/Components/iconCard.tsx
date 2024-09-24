import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/Components/ui/card';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { prepareWebflowObject } from '@/lib/webflow';
import { ClipboardIcon } from '@radix-ui/react-icons';
import { toast } from 'sonner';

export default function IconCard({ icon, selectedType }: { icon: Icon; selectedType: string }) {
    const copyToClipboard = (icon: Icon) => {
        if (selectedType) {
            navigator.clipboard
                .writeText(icon.iconData[selectedType].svg_content)
                .then(() => {
                    toast.success(`SVG "${icon.name}" copied to clipboard`, {
                        description: 'Now go create something awesome!',
                    });
                })
                .catch((err) => {
                    toast.error('Failed to copy SVG to clipboard');
                    console.error('Clipboard error:', err);
                });
        }
    };

    const copyAsWebflowElement = (icon: Icon) => {
        const webflowObject = prepareWebflowObject(icon.iconData[selectedType].svg_content);

        const clipboardData = new ClipboardEvent('').clipboardData || new DataTransfer();
        clipboardData.setData('text/plain', webflowObject);

        document.addEventListener('copy', function onCopy(e: ClipboardEvent) {
            e.clipboardData?.setData('application/json', webflowObject);
            e.preventDefault();
            document.removeEventListener('copy', onCopy);
        });

        document.execCommand('copy');

        toast.success(`Copied "${icon.name}" as Webflow element to clipboard`);
    };

    return (
        <Card key={icon.id} className="flex flex-col">
            <CardHeader>
                <div className="flex flex-col gap-4">
                    {selectedType && (
                        <img src={icon.iconData[selectedType].file_path} alt={icon.name} className="h-8 w-8" />
                    )}
                    <CardTitle className="font-semibold">{icon.name}</CardTitle>
                </div>
            </CardHeader>
            {icon.explanation && (
                <CardContent>
                    <p className="text-muted-foreground">{icon.explanation}</p>
                </CardContent>
            )}
            <CardFooter className="mt-auto flex gap-1">
                <TooltipProvider>
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button variant={'outline'} onClick={() => copyToClipboard(icon)}>
                                <ClipboardIcon className="h-4 w-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p>Copy SVG to clipboard</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
                <TooltipProvider>
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button variant={'outline'} onClick={() => copyAsWebflowElement(icon)}>
                                <img src="/assets/webflow-logo.svg" alt="Webflow Logo" className="h-4 w-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p>Copy as Webflow element to clipboard</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
            </CardFooter>
        </Card>
    );
}

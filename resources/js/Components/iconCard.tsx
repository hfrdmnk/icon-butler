import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/Components/ui/card';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
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

    return (
        <Card key={icon.id} className="flex flex-col">
            <CardHeader>
                <div className="flex items-center gap-4">
                    {selectedType && (
                        <img src={icon.iconData[selectedType].file_path} alt={icon.name} className="h-8 w-8" />
                    )}
                    <CardTitle className="font-semibold">{icon.name}</CardTitle>
                </div>
            </CardHeader>
            <CardContent>
                <p className="text-muted-foreground">{icon.explanation}</p>
            </CardContent>
            <CardFooter className="mt-auto">
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
            </CardFooter>
        </Card>
    );
}

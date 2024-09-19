import IconCard from '@/Components/iconCard';
import IconCardSkeleton from '@/Components/iconCardSkeleton';
import Spinner from '@/Components/spinner';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/Components/ui/accordion';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Separator } from '@/Components/ui/separator';
import { Skeleton } from '@/Components/ui/skeleton';
import { Switch } from '@/Components/ui/switch';
import { Textarea } from '@/Components/ui/textarea';
import App from '@/Layouts/AppLayout';
import { cn } from '@/lib/utils';
import { IndexPageProps, PageProps } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { useHotkeys } from 'react-hotkeys-hook';
import { toast } from 'sonner';

export default function Index({ auth, iconSets, availableTypes }: IndexPageProps) {
    const { flash } = usePage<PageProps>().props;
    const [selectedType, setSelectedType] = useState<null | string>(null);
    const [availableIcons, setAvailableIcons] = useState<Icon[]>([]);
    const [keywordsEmbedding, setKeywordsEmbedding] = useState<string | null>(null);
    const [suggestionId, setSuggestionId] = useState<number | null>(null);
    const [loadMoreAttempts, setLoadMoreAttempts] = useState(0);
    const [isLoadingMore, setIsLoadingMore] = useState(false);

    const textAreaRef = useRef<HTMLTextAreaElement>(null);

    useHotkeys('mod+enter', () => handleSubmit(new Event('submit')), {
        enableOnFormTags: true,
    });

    useHotkeys('space', () => {
        if (availableIcons?.length > 0 && !isLoadingMore) {
            handleLoadMore();
        }
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
        copy: '',
        iconSet: iconSets[0].name,
        excludeUnmentionedLogos: true,
    });

    const handleSubmit = (e: React.FormEvent | Event) => {
        e.preventDefault();
        resetSelectedType();
        setLoadMoreAttempts(0);

        if (textAreaRef.current) {
            textAreaRef.current.blur();
        }

        post(route('icons.suggest'), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: (page) => {
                setAvailableIcons(page.props.suggestedIcons as Icon[]);
                setKeywordsEmbedding(page.props.keywordsEmbedding as string);
                setSuggestionId(page.props.suggestionId as number);
            },
        });
    };

    // TODO: Figure out how I can catch 429 errors and show a toast message
    const handleLoadMore = () => {
        if (loadMoreAttempts < 3) {
            setIsLoadingMore(true);
            router.post(
                route('icons.suggest'),
                {
                    copy: data.copy,
                    iconSet: data.iconSet,
                    excludeUnmentionedLogos: data.excludeUnmentionedLogos,
                    alreadySuggestedIcons: availableIcons.map((icon) => icon.id),
                    keywordsEmbedding,
                    suggestionId,
                },
                {
                    only: ['suggestedIcons'],
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: (page) => {
                        setAvailableIcons((prev) => [...prev, ...(page.props.suggestedIcons as Icon[])]);
                        setIsLoadingMore(false);
                        setLoadMoreAttempts(loadMoreAttempts + 1);
                    },
                },
            );
        } else {
            toast.error('These were all the icons we could find for you. Try a different copy.');
        }
    };

    const resetSelectedType = () => {
        if (availableTypes?.length > 0) {
            const regularType = availableTypes.find((type) => type.id === 'regular');
            if (regularType) {
                setSelectedType('regular');
            } else {
                setSelectedType(availableTypes[0].id);
            }
        } else {
            setSelectedType(null);
        }
    };

    useEffect(resetSelectedType, [availableTypes]);

    return (
        <App user={auth.user}>
            <Head title="Serving icons" />
            <header className="container max-w-3xl py-12 mt-8">
                <h1 className="text-muted-foreground">Icon Butler</h1>
                <p className="text-2xl font-semibold tracking-tight scroll-m-20">Always serving you the best icons.</p>
                <div className="mt-4">
                    <Badge variant={'outline'}>Powered by the best open source icon sets</Badge>
                </div>
            </header>
            <main className="container max-w-3xl py-8">
                <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center justify-between h-9">
                            <Label>Your copy</Label>
                            {data.copy.length > 0 ? (
                                <Button
                                    variant={'link'}
                                    onClick={(e) => {
                                        e.preventDefault();
                                        setData('copy', '');
                                    }}
                                    className="px-0"
                                >
                                    Clear textarea
                                </Button>
                            ) : (
                                <Button
                                    variant={'link'}
                                    onClick={(e) => {
                                        e.preventDefault();
                                        navigator.clipboard.readText().then((text) => {
                                            setData('copy', text);
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
                            ref={textAreaRef}
                            value={data.copy}
                            onChange={(e) => setData('copy', e.target.value)}
                            className="max-h-96 min-h-32"
                        />
                        {errors.copy && <p className="text-sm text-red-500">{errors.copy}</p>}
                    </div>
                    <div className="flex flex-col gap-2">
                        <Label>Choose your icon set</Label>
                        <Select value={data.iconSet} onValueChange={(e) => setData('iconSet', e)}>
                            <SelectTrigger className="w-[180px]">
                                <SelectValue placeholder="Icon Type" />
                            </SelectTrigger>
                            <SelectContent>
                                {iconSets.map((set: IconSet) => {
                                    return (
                                        <SelectItem key={set.id} value={set.name}>
                                            {set.display_name}
                                        </SelectItem>
                                    );
                                })}
                            </SelectContent>
                        </Select>
                        <p className="text-sm text-muted-foreground">
                            More information on this icon set:{' '}
                            <a
                                href={iconSets.find((s) => s.name === data.iconSet)?.url}
                                target="_blank"
                                className="underline"
                            >
                                {iconSets.find((s) => s.name === data.iconSet)?.display_name}
                            </a>
                        </p>
                    </div>
                    <Accordion type="single" collapsible>
                        <AccordionItem value="advanced-settings" className="border-b-0">
                            <AccordionTrigger>Advanced settings</AccordionTrigger>
                            <AccordionContent>
                                <div className="flex flex-col gap-2">
                                    <div className="flex items-center gap-2">
                                        <Switch
                                            id="exclude-logos"
                                            checked={data.excludeUnmentionedLogos}
                                            onCheckedChange={(e) => setData('excludeUnmentionedLogos', e)}
                                        />
                                        <Label htmlFor="exclude-logos">
                                            Exclude company logos not explicitly mentioned
                                        </Label>
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        This usually results in a more accurate match.
                                    </p>
                                </div>
                            </AccordionContent>
                        </AccordionItem>
                    </Accordion>
                    <div className="flex justify-end">
                        <div className="flex flex-col items-center gap-1">
                            <Button disabled={processing || data.copy.length < 3} type="submit">
                                {processing ? (
                                    <div className="flex items-center gap-1">
                                        <Spinner />
                                        <span>Working some magic…</span>
                                    </div>
                                ) : (
                                    'Suggest me some icons!'
                                )}
                            </Button>
                            <p className={cn(['text-sm text-muted-foreground', data.copy.length < 3 && 'opacity-0'])}>
                                ⌘ + enter
                            </p>
                        </div>
                    </div>
                </form>
                <div className="py-8">
                    <Separator />
                </div>
                {(!availableIcons || availableIcons?.length === 0) && !processing && (
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
                {availableIcons?.length > 0 && !processing && (
                    <>
                        <div className="mb-8">
                            {selectedType && (
                                <div className="flex flex-col gap-2">
                                    <Label>Icon type</Label>
                                    <Select value={selectedType} onValueChange={(e) => setSelectedType(e)}>
                                        <SelectTrigger className="w-[180px]">
                                            <SelectValue placeholder="Icon Type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {availableTypes.map((type: IconType) => {
                                                return (
                                                    <SelectItem key={type.id} value={type.id}>
                                                        {type.name}
                                                    </SelectItem>
                                                );
                                            })}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}
                        </div>
                        <div className="grid grid-cols-2 gap-2">
                            {availableIcons.map((icon) => (
                                <IconCard key={icon.id} icon={icon} selectedType={selectedType ?? ''} />
                            ))}
                        </div>
                        {loadMoreAttempts < 3 && (
                            <div className="flex flex-col items-center justify-center gap-1 mt-8">
                                <Button
                                    variant={'outline'}
                                    onClick={handleLoadMore}
                                    disabled={isLoadingMore}
                                    className="flex items-center gap-1"
                                >
                                    {isLoadingMore ? (
                                        <div className="flex items-center gap-1">
                                            <Spinner />
                                            <span>Loading more icons…</span>
                                        </div>
                                    ) : (
                                        'Load more icons'
                                    )}
                                </Button>
                                <p
                                    className={cn([
                                        'text-sm text-muted-foreground',
                                        data.copy.length < 3 && 'opacity-0',
                                    ])}
                                >
                                    space
                                </p>
                            </div>
                        )}
                    </>
                )}
            </main>
        </App>
    );
}

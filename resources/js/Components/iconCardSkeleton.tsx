import { Card, CardContent, CardFooter, CardHeader } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';

export default function IconCardSkeleton() {
    return (
        <Card className="flex flex-col">
            <CardHeader>
                <div className="flex items-center gap-4">
                    <Skeleton className="h-8 w-8 rounded" />
                    <Skeleton className="h-6 w-24" />
                </div>
            </CardHeader>
            <CardContent>
                <Skeleton className="h-4 w-full" />
                <Skeleton className="mt-2 h-4 w-3/4" />
            </CardContent>
            <CardFooter className="mt-auto">
                <Skeleton className="h-8 w-8 rounded" />
            </CardFooter>
        </Card>
    );
}

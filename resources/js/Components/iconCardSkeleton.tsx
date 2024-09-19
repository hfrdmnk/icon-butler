import { Card, CardFooter, CardHeader } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';

export default function IconCardSkeleton() {
    return (
        <Card className="flex flex-col">
            <CardHeader>
                <div className="flex flex-col gap-4">
                    <Skeleton className="h-8 w-8 rounded" />
                    <Skeleton className="h-6 w-24" />
                </div>
            </CardHeader>
            <CardFooter className="mt-auto">
                <Skeleton className="h-8 w-8 rounded" />
            </CardFooter>
        </Card>
    );
}

import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
} from "@/Components/ui/card";
import { Skeleton } from "@/Components/ui/skeleton";

export default function IconCardSkeleton() {
    return (
        <Card className="flex flex-col">
            <CardHeader>
                <div className="flex items-center gap-4">
                    <Skeleton className="w-8 h-8 rounded" />
                    <Skeleton className="w-24 h-6" />
                </div>
            </CardHeader>
            <CardContent>
                <Skeleton className="w-full h-4" />
                <Skeleton className="w-3/4 h-4 mt-2" />
            </CardContent>
            <CardFooter className="mt-auto">
                <Skeleton className="w-8 h-8 rounded" />
            </CardFooter>
        </Card>
    );
}

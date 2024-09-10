import { Toaster } from "@/Components/ui/sonner";
import { User } from "@/types";
import { PropsWithChildren, useState } from "react";

export default function App({
    user,
    children,
}: PropsWithChildren<{ user: User }>) {
    const [showingNavigationDropdown, setShowingNavigationDropdown] =
        useState(false);

    return (
        <div>
            <div>{children}</div>
            <Toaster position="top-right" closeButton />
        </div>
    );
}

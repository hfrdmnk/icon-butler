import { Toaster } from "@/Components/ui/sonner";
import { PropsWithChildren } from "react";

export default function App({ children }: PropsWithChildren) {
    return (
        <div>
            <div>{children}</div>
            <footer className="container py-12 text-sm text-center text-muted-foreground">
                <p>
                    A{" "}
                    <a href="https://linea.studio" target="_blank">
                        Linea
                    </a>{" "}
                    ×{" "}
                    <a href="https://bloqlabs.ch" target="_blank">
                        Bloq Labs
                    </a>{" "}
                    projektli.
                </p>
            </footer>
            <Toaster position="top-right" closeButton />
        </div>
    );
}

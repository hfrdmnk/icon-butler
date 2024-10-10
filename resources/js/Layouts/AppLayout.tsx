import IconButlerLogo from '@/../../public/assets/iconbutler-logo.svg?react';
import { Toaster } from '@/Components/ui/sonner';
import { User } from '@/types';
import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function App({ children, user }: PropsWithChildren<{ user: User }>) {
    return (
        <div>
            <nav>
                <div className="container flex max-w-3xl items-center justify-between py-4">
                    <Link href="/">
                        <IconButlerLogo className="w-10" />
                    </Link>
                    <div className="flex items-center gap-4 text-sm text-muted-foreground">
                        <a
                            href="mailto:info@bloqlabs.ch?subject=Icon%20Butler%20Feedback"
                            className="transition hover:text-bloq"
                        >
                            Feedback?
                        </a>
                        <Link href="/about" className="transition hover:text-bloq">
                            About
                        </Link>
                    </div>
                </div>
            </nav>
            <div>{children}</div>
            <footer className="container py-12 text-center font-mono text-xs text-muted-foreground">
                <p>
                    A{' '}
                    <a href="https://linea.studio" target="_blank">
                        Linea
                    </a>{' '}
                    ×{' '}
                    <a href="https://www.bloqlabs.ch/webflow-agentur" target="_blank">
                        Bloq Labs
                    </a>{' '}
                    projektli.
                </p>
            </footer>
            <Toaster position="top-right" closeButton />
        </div>
    );
}

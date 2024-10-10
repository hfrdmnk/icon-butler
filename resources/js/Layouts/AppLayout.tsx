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
                    <Link href="/" className="">
                        <IconButlerLogo className="w-10 text-[#3D50FF]" />
                    </Link>
                </div>
            </nav>
            <div>{children}</div>
            <footer className="container py-12 text-center text-sm text-muted-foreground">
                <p>
                    A{' '}
                    <a href="https://linea.studio" target="_blank">
                        Linea
                    </a>{' '}
                    ×{' '}
                    <a href="https://bloqlabs.ch" target="_blank">
                        Bloq Labs
                    </a>{' '}
                    projektli.
                </p>
            </footer>
            <Toaster position="top-right" closeButton />
        </div>
    );
}

import App from '@/Layouts/AppLayout';
import { PageProps } from '@/types';
import { Head } from '@inertiajs/react';

export default function Index({ auth }: PageProps) {
    return (
        <App user={auth.user}>
            <Head title="About" />
            <header className="container max-w-3xl py-12">
                <h1 className="text-sm font-light text-muted-foreground">What this is</h1>
                <p className="scroll-m-20 text-2xl font-semibold tracking-tight">About Icon Butler</p>
            </header>
            <main className="container prose prose-sm max-w-3xl py-8 prose-headings:font-semibold prose-a:font-medium">
                <p>
                    Icon Butler is a collaboration project between product studio{' '}
                    <a href="https://linea.studio" target="_blank">
                        Linea
                    </a>{' '}
                    and Webflow agency{' '}
                    <a href="https://bloqlabs.ch" target="_blank">
                        Bloq Labs.
                    </a>
                </p>
                <p>
                    Found a bug, want to request a new icon set or just have some feedback on how we can make Icon
                    Butler better? Don't hesitate to{' '}
                    <a href="mailto:info@bloqlabs.ch?subject=Icon%20Butler%20Feedback">reach out</a>!
                </p>
                <p>All used icons are available under the following licenses:</p>
                <h2>Phosphor Icons:</h2>
                <p>
                    Link:{' '}
                    <a href="https://phosphoricons.com/" target="_blank">
                        phosphoricons.com
                    </a>
                </p>
                <p>
                    Github:{' '}
                    <a href="https://github.com/phosphor-icons/" target="_blank">
                        github.com/phosphor-icons
                    </a>
                </p>
                <h3>MIT License</h3>
                <p>Copyright (c) 2020 Phosphor Icons</p>
                <p>
                    Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
                    associated documentation files (the "Software"), to deal in the Software without restriction,
                    including without limitation the rights to use, copy, modify, merge, publish, distribute,
                    sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is
                    furnished to do so, subject to the following conditions:
                </p>
                <p>
                    The above copyright notice and this permission notice shall be included in all copies or substantial
                    portions of the Software.
                </p>
                <p>
                    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT
                    NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
                    NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES
                    OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
                    CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
                </p>
                <h2>Lucide Icons:</h2>
                <p>
                    Link:{' '}
                    <a href="https://lucide.dev/icons/" target="_blank">
                        lucide.dev/icons
                    </a>
                </p>
                <p>
                    Github:{' '}
                    <a href="https://github.com/lucide-icons/" target="_blank">
                        https://github.com/lucide-icons
                    </a>
                </p>
                <h3>ISC License</h3>
                <p>
                    Copyright (c) for portions of Lucide are held by Cole Bemis 2013-2022 as part of Feather (MIT). All
                    other copyright (c) for Lucide are held by Lucide Contributors 2022.
                </p>
                <p>
                    Permission to use, copy, modify, and/or distribute this software for any purpose with or without fee
                    is hereby granted, provided that the above copyright notice and this permission notice appear in all
                    copies.
                </p>
                <p>
                    THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH REGARD TO THIS
                    SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE
                    AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
                    WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE
                    OR OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS
                    SOFTWARE.
                </p>
            </main>
        </App>
    );
}

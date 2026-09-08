export function Footer() {
    return (
        <footer className="border-border bg-card border-t">
            <div className="mx-auto flex max-w-[1440px] flex-col gap-6 px-4 py-7 md:px-6">
                <div className="flex items-center gap-3">
                    <img
                        src="/idx-logo.png"
                        alt="IDX Stock Information"
                        className="h-9 w-auto object-contain"
                        decoding="async"
                    />
                    <div>
                        <p className="font-heading font-semibold">
                            Market Monitor
                        </p>
                        <p className="text-muted-foreground text-sm">
                            Indonesia market dashboard
                        </p>
                    </div>
                </div>
                <div className="border-border flex flex-col gap-2 border-t pt-4 text-muted-foreground text-sm md:flex-row md:items-center md:justify-between">
                    <p>
                        Built for the{" "}
                        <span className="font-medium text-foreground">
                            System Developer Intern
                        </span>{" "}
                        position at{" "}
                        <span className="font-medium text-foreground">
                            IDXSTI
                        </span>
                        .
                    </p>
                    <p>
                        Built by{" "}
                        <span className="inline-flex items-center gap-1.5 align-middle">
                            <img
                                alt="rustian"
                                className="size-4 rounded-full"
                                height="auto"
                                src="https://github.com/shabanhr.png"
                                width="auto"
                            />
                            <span className="font-medium text-foreground">
                                Rustian Afencius Marbun
                            </span>
                        </span>
                        .
                    </p>
                </div>
            </div>
        </footer>
    );
}

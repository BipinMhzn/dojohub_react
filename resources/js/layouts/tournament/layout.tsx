import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import { Tournament } from '@/pages/tournaments';
import { Link } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

interface TournamentLayoutProps {
    tournament: Tournament;
    activeKey: 'overview' | 'categories';
}

const levelLabel: Record<number, string> = {
    1: 'Local',
    2: 'Regional',
    3: 'National',
    4: 'International',
};

const statusLabel: Record<number, string> = {
    0: 'Scheduled',
    1: 'In Progress',
    2: 'Completed',
    3: 'Cancelled',
};

export default function TournamentLayout({ tournament, activeKey, children }: PropsWithChildren<TournamentLayoutProps>) {
    if (typeof window === 'undefined') {
        return null;
    }

    const sidebarNavItems = [
        { key: 'overview', title: 'Overview', href: `/manage/tournaments/${tournament.id}` },
        { key: 'categories', title: 'Categories', href: `/manage/tournaments/${tournament.id}/categories` },
    ];

    return (
        <div className="px-4 py-6">
            <div className="mb-6 flex items-center gap-4">
                <Avatar className="h-14 w-14">
                    <AvatarImage src={tournament.logo_url ?? undefined} alt={tournament.name} />
                    <AvatarFallback>
                        {tournament.name
                            .split(' ')
                            .map((n) => n[0])
                            .join('')
                            .toUpperCase()
                            .slice(0, 2)}
                    </AvatarFallback>
                </Avatar>
                <div>
                    <div className="flex flex-wrap items-center gap-3">
                        <h1 className="text-2xl font-semibold">{tournament.name}</h1>
                        <Badge variant="outline">{levelLabel[Number(tournament.level)] ?? 'Unknown'}</Badge>
                        <Badge variant="outline">{statusLabel[Number(tournament.status)] ?? 'Unknown'}</Badge>
                    </div>
                    {tournament.description && (
                        <p className="text-muted-foreground text-sm">{tournament.description}</p>
                    )}
                </div>
            </div>

            <div className="flex flex-col space-y-8 lg:flex-row lg:space-y-0 lg:space-x-12">
                <aside className="w-full max-w-xl lg:w-56">
                    <nav className="flex flex-col space-y-1 space-x-0">
                        {sidebarNavItems.map((item) => (
                            <Button
                                key={item.key}
                                size="sm"
                                variant="ghost"
                                asChild
                                className={cn('w-full justify-start', {
                                    'bg-muted': activeKey === item.key,
                                })}
                            >
                                <Link href={item.href} prefetch>
                                    {item.title}
                                </Link>
                            </Button>
                        ))}
                    </nav>
                </aside>

                <Separator className="my-6 md:hidden" />

                <div className="flex-1">
                    <section className="space-y-12">{children}</section>
                </div>
            </div>
        </div>
    );
}

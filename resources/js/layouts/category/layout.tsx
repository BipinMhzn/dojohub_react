import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import { Tournament, TournamentCategory } from '@/pages/tournaments';
import { getCategoryDisplayName } from '@/pages/tournaments/partials/category-columns';
import { Link } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

interface CategoryLayoutProps {
    tournament: Tournament;
    category: TournamentCategory;
    activeKey: 'overview' | 'registrations' | 'matches' | 'results';
}

const categoryStatusLabel: Record<number, string> = {
    0: 'Pending', 1: 'Seeding', 2: 'Active', 3: 'Completed',
};

export default function CategoryLayout({ tournament, category, activeKey, children }: PropsWithChildren<CategoryLayoutProps>) {
    if (typeof window === 'undefined') {
        return null;
    }

    const sidebarNavItems = [
        { key: 'overview', title: 'Overview', href: `/manage/tournaments/${tournament.id}/categories/${category.id}/edit` },
        { key: 'registrations', title: 'Registrations', href: `/manage/tournaments/${tournament.id}/categories/${category.id}/registrations` },
        { key: 'matches', title: 'Matches', href: `/manage/tournaments/${tournament.id}/categories/${category.id}/matches` },
        { key: 'results', title: 'Results', href: `/manage/tournaments/${tournament.id}/categories/${category.id}/results` },
    ];

    const displayName = getCategoryDisplayName(category);

    return (
        <div className="px-4 py-6">
            <div className="mb-6 flex flex-col gap-1">
                <div className="flex flex-wrap items-center gap-3">
                    <h1 className="text-2xl font-semibold">{displayName}</h1>
                    <Badge variant="outline">{categoryStatusLabel[Number(category.status)] ?? 'Unknown'}</Badge>
                </div>
                <p className="text-sm text-muted-foreground">
                    {tournament.name} · Age {category.min_age}–{category.max_age} yrs · Weight {category.min_weight}–{category.max_weight} kg
                </p>
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

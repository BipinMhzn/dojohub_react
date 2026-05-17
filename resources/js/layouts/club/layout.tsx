import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import { Club } from '@/pages/clubs';
import { Link } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

interface ClubLayoutProps {
    club: Club;
    activeKey: 'overview' | 'players';
}

export default function ClubLayout({ club, activeKey, children }: PropsWithChildren<ClubLayoutProps>) {
    if (typeof window === 'undefined') {
        return null;
    }

    const sidebarNavItems = [
        { key: 'overview', title: 'Overview', href: `/manage/clubs/${club.id}` },
        { key: 'players', title: 'Players', href: `/manage/clubs/${club.id}/players` },
    ];

    return (
        <div className="px-4 py-6">
            <div className="mb-6 flex items-center gap-4">
                <Avatar className="h-14 w-14">
                    <AvatarImage src={club.logo_url ?? undefined} alt={club.name} />
                    <AvatarFallback>
                        {club.name
                            .split(' ')
                            .map((n) => n[0])
                            .join('')
                            .toUpperCase()
                            .slice(0, 2)}
                    </AvatarFallback>
                </Avatar>
                <div>
                    <div className="flex items-center gap-3">
                        <h1 className="text-2xl font-semibold">{club.name}</h1>
                        {club.abbreviation && (
                            <span className="text-muted-foreground text-sm font-medium">{club.abbreviation}</span>
                        )}
                    </div>
                    {club.address && (
                        <p className="text-muted-foreground text-sm">
                            {[club.address.address, club.address.city, club.address.state, club.address.country]
                                .filter(Boolean)
                                .join(', ')}
                        </p>
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

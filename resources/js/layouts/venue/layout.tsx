import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import { Venue } from '@/pages/venues/index';
import { Link } from '@inertiajs/react';
import { MapPin } from 'lucide-react';
import { type PropsWithChildren } from 'react';

interface VenueLayoutProps {
    venue: Venue;
    activeKey: 'overview' | 'mats';
}

export default function VenueLayout({ venue, activeKey, children }: PropsWithChildren<VenueLayoutProps>) {
    if (typeof window === 'undefined') {
        return null;
    }

    const sidebarNavItems = [
        { key: 'overview', title: 'Overview', href: `/manage/venues/${venue.id}` },
        { key: 'mats', title: 'Mats', href: `/manage/venues/${venue.id}/mats` },
    ];

    const locationParts = [venue.address?.city, venue.address?.country?.name ?? venue.address?.country_code].filter(Boolean);

    return (
        <div className="px-4 py-6">
            <div className="mb-6">
                <h1 className="text-2xl font-semibold dark:text-white">{venue.name}</h1>
                {locationParts.length > 0 && (
                    <p className="mt-1 flex items-center gap-1 text-sm text-muted-foreground">
                        <MapPin className="h-3.5 w-3.5" />
                        {locationParts.join(', ')}
                    </p>
                )}
            </div>

            <div className="flex flex-col space-y-8 lg:flex-row lg:space-x-12 lg:space-y-0">
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

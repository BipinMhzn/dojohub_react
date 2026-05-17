import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';

interface TournamentTabsProps {
    tournamentId: string;
    activeTab: 'categories' | string;
}

export default function TournamentTabs({ tournamentId, activeTab }: TournamentTabsProps) {
    const tabs = [
        {
            name: 'Categories',
            key: 'categories',
            href: `/manage/tournaments/${tournamentId}/categories`,
        },
    ];

    return (
        <div className="mb-6 border-b border-border">
            <div className="flex space-x-6">
                {tabs.map((tab) => (
                    <Link
                        key={tab.key}
                        href={tab.href}
                        className={cn(
                            'border-b-2 px-1 pb-2 text-sm font-medium transition-colors',
                            activeTab === tab.key
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:text-foreground',
                        )}
                    >
                        {tab.name}
                    </Link>
                ))}
            </div>
        </div>
    );
}

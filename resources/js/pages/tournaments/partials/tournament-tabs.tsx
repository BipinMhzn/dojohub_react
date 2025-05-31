import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils'; // utility for conditional classNames

interface Tab {
    name: string;
    href: string;
    key: string;
}

interface TournamentTabsProps {
    tournamentId: string;
    activeTab: 'players' | 'matches' | string;
    extraTabs?: Tab[];
}

export default function TournamentTabs({ tournamentId, activeTab, extraTabs = [] }: TournamentTabsProps) {
    const tabs: Tab[] = [
        {
            name: 'Players',
            key: 'players',
            href: `/manage/tournaments/${tournamentId}/registrations`,
        },
        {
            name: 'Matches',
            key: 'matches',
            href: `/manage/tournaments/${tournamentId}/matches`,
        },
        ...extraTabs,
    ];

    return (
        <div className="border-b border-border mb-6">
            <div className="flex space-x-6">
                {tabs.map((tab) => (
                    <Link
                        key={tab.key}
                        href={tab.href}
                        className={cn(
                            'px-1 pb-2 text-sm font-medium transition-colors border-b-2',
                            activeTab === tab.key
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:text-foreground'
                        )}
                    >
                        {tab.name}
                    </Link>
                ))}
            </div>
        </div>
    );
}

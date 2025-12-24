import HeadingSmall from '@/components/heading-small';
import AppLayout from '@/layouts/app-layout';
import { Club } from '@/pages/clubs';
import { Player } from '@/pages/players';
import TournamentHeader from '@/pages/tournaments/partials/tournament-header';
import TournamentTabs from '@/pages/tournaments/partials/tournament-tabs';
import { Head } from '@inertiajs/react';
import { Bracket } from 'react-brackets';
import { Tournament } from '../index';

interface Props {
    tournament: Tournament;
    players: Player[];
    clubs: Club[];
}

export default function TournamentMatches({ tournament }: Props) {
    const breadcrumbs = [
        {
            title: 'Tournaments',
            href: '/manage/tournaments',
        },
        {
            title: 'Details',
            href: `/manage/tournaments/${tournament.id}`,
        },
    ];

    const rounds = [
        {
            title: 'Quarterfinals',
            seeds: [
                {
                    id: 1,
                    date: new Date().toLocaleDateString(),
                    teams: [{ name: 'Player A' }, { name: 'Player B' }],
                },
                {
                    id: 2,
                    date: new Date().toLocaleDateString(),
                    teams: [{ name: 'Player C' }, { name: 'Player D' }],
                },
            ],
        },
        {
            title: 'Semifinals',
            seeds: [
                {
                    id: 3,
                    date: new Date().toLocaleDateString(),
                    teams: [{ name: 'Winner A/B' }, { name: 'Winner C/D' }],
                },
            ],
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Tournament Detail: ${tournament.name}`} />

            <div className="px-4 py-6">
                <TournamentHeader tournament={tournament} />
                <TournamentTabs tournamentId={tournament.id} activeTab="matches" />

                <div className="mb-6">
                    <HeadingSmall title="Fixture" description={`Match here`} />
                </div>
                <div className="overflow-auto">
                    <Bracket rounds={rounds} />
                </div>
            </div>
        </AppLayout>
    );
}

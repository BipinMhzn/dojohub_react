import HeadingSmall from '@/components/heading-small';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { Club } from '@/pages/clubs';
import { Player } from '@/pages/players';
import { AgeCategory, CategoryStatus, Gender, Tournament, TournamentCategory, TournamentRegistration, WeightCategory } from '@/pages/tournaments';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Trash2, UserPlus } from 'lucide-react';
import React, { useState } from 'react';

const ageCategoryLabel: Record<number, string> = {
    0: 'Preparation', 1: 'Super Mini', 2: 'Mini', 3: 'Pupils', 4: 'Juveniles',
    5: 'Cadets', 6: 'Juniors', 7: 'Under 23', 8: 'Seniors', 9: 'Veterans',
};

const weightCategoryLabel: Record<number, string> = {
    0: '-48kg', 1: '-52kg', 2: '-57kg', 3: '-60kg', 4: '-63kg',
    5: '-66kg', 6: '-70kg', 7: '-73kg', 8: '-78kg', 9: '-81kg',
    10: '-90kg', 11: '-100kg', 12: '+100kg', 13: '+78kg',
};

interface Props {
    tournament: Tournament;
    category: TournamentCategory;
    registrations: TournamentRegistration[];
    players: Player[];
    clubs: Club[];
}

function calculateAge(dob: string): number {
    const birth = new Date(dob);
    const today = new Date();
    let age = today.getFullYear() - birth.getFullYear();
    if (today.getMonth() < birth.getMonth() || (today.getMonth() === birth.getMonth() && today.getDate() < birth.getDate())) {
        age--;
    }
    return age;
}

export default function TournamentRegistrations({ tournament, category, registrations, players, clubs }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tournaments', href: '/manage/tournaments' },
        { title: tournament.name, href: `/manage/tournaments/${tournament.id}/categories` },
        { title: 'Categories', href: `/manage/tournaments/${tournament.id}/categories` },
        { title: category.name ?? `${category.gender === Gender.Male ? 'Male' : 'Female'} ${ageCategoryLabel[Number(category.age_category)]} ${weightCategoryLabel[Number(category.weight_category)]}`, href: '#' },
    ];

    const canRegister = Number(category.status) === CategoryStatus.Pending;
    const [selectedPlayerId, setSelectedPlayerId] = useState<string | null>(null);
    const [selectedClubId, setSelectedClubId] = useState<string | null>(null);
    const [dialogOpen, setDialogOpen] = useState(false);

    function handleRegister(e: React.FormEvent) {
        e.preventDefault();
        if (!selectedPlayerId || !selectedClubId) return;

        router.post(
            `/manage/tournaments/${tournament.id}/categories/${category.id}/registrations`,
            { player_id: selectedPlayerId, club_id: selectedClubId },
            {
                onSuccess: () => {
                    setSelectedPlayerId(null);
                    setSelectedClubId(null);
                    setDialogOpen(false);
                },
            },
        );
    }

    const categoryName = category.name ?? `${category.gender === Gender.Male ? 'Male' : 'Female'} ${ageCategoryLabel[Number(category.age_category)]} ${weightCategoryLabel[Number(category.weight_category)]}`;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${categoryName} – Registrations`} />

            <div className="px-4 py-6">
                <div className="mb-4 flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-bold">{categoryName}</h1>
                        <p className="text-sm text-muted-foreground">
                            Age {category.min_age}–{category.max_age} · Weight {category.min_weight}–{category.max_weight} kg
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <a href={`/manage/tournaments/${tournament.id}/categories`}>← Back to Categories</a>
                    </Button>
                </div>

                <div className="mb-6">
                    <div className="flex items-center justify-between">
                        <HeadingSmall
                            title="Registered Players"
                            description={`${registrations.length} player${registrations.length !== 1 ? 's' : ''} registered`}
                        />
                        {canRegister && (
                            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                                <DialogTrigger asChild>
                                    <Button>
                                        <UserPlus className="mr-2 size-4" />
                                        Register Player
                                    </Button>
                                </DialogTrigger>
                                <DialogContent className="sm:max-w-md">
                                    <DialogHeader>
                                        <DialogTitle>Register Player</DialogTitle>
                                        <DialogDescription>
                                            Select an eligible player and their club.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <form onSubmit={handleRegister} className="space-y-4">
                                        <div className="space-y-4">
                                            <div className="space-y-2">
                                                <label className="text-sm font-medium">Player</label>
                                                <Select value={selectedPlayerId ?? ''} onValueChange={setSelectedPlayerId}>
                                                    <SelectTrigger className="w-full">
                                                        <SelectValue placeholder="Select player" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {players.length > 0 ? (
                                                            players.map((player) => (
                                                                <SelectItem key={player.id} value={player.id}>
                                                                    <div className="flex items-center gap-2">
                                                                        <Avatar className="h-6 w-6">
                                                                            <AvatarImage src={player.profile_photo_url ?? undefined} alt={player.name} />
                                                                            <AvatarFallback>{player.name[0]}</AvatarFallback>
                                                                        </Avatar>
                                                                        <span>
                                                                            {player.name} — Age: {calculateAge(player.dob)}, {player.weight} kg
                                                                        </span>
                                                                    </div>
                                                                </SelectItem>
                                                            ))
                                                        ) : (
                                                            <div className="text-muted-foreground px-3 py-2 text-sm">No eligible players available</div>
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div className="space-y-2">
                                                <label className="text-sm font-medium">Club</label>
                                                <Select value={selectedClubId ?? ''} onValueChange={setSelectedClubId}>
                                                    <SelectTrigger className="w-full">
                                                        <SelectValue placeholder="Select club" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {clubs.map((club) => (
                                                            <SelectItem key={club.id} value={club.id}>
                                                                <div className="flex items-center gap-2">
                                                                    <Avatar className="h-6 w-6">
                                                                        <AvatarImage src={club.logo_url ?? undefined} alt={club.name} />
                                                                        <AvatarFallback>{club.name[0]}</AvatarFallback>
                                                                    </Avatar>
                                                                    <span>{club.name}</span>
                                                                </div>
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        </div>

                                        <DialogFooter>
                                            <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>Cancel</Button>
                                            <Button type="submit" disabled={!selectedPlayerId || !selectedClubId}>Register</Button>
                                        </DialogFooter>
                                    </form>
                                </DialogContent>
                            </Dialog>
                        )}
                    </div>

                    <Table className="mt-4">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Player</TableHead>
                                <TableHead>Club</TableHead>
                                <TableHead>Age (at reg.)</TableHead>
                                <TableHead>Weight (kg)</TableHead>
                                {canRegister && <TableHead>Actions</TableHead>}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {registrations.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={5} className="text-muted-foreground text-center">
                                        No players registered yet.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                registrations.map((reg) => (
                                    <TableRow key={reg.id}>
                                        <TableCell>
                                            <div className="flex items-center gap-3">
                                                <Avatar className="h-8 w-8">
                                                    <AvatarImage src={reg.player?.profile_photo_url ?? undefined} alt={reg.player?.name} />
                                                    <AvatarFallback>
                                                        {reg.player?.name.split(' ').map((n) => n[0]).join('').toUpperCase().slice(0, 2)}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <span>{reg.player?.name}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-3">
                                                <span>{reg.club?.name}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>{reg.player_age}</TableCell>
                                        <TableCell>{reg.player_weight}</TableCell>
                                        {canRegister && (
                                            <TableCell>
                                                <Button
                                                    variant="destructive"
                                                    size="sm"
                                                    onClick={() =>
                                                        router.delete(
                                                            `/manage/tournaments/${tournament.id}/categories/${category.id}/registrations/${reg.id}`
                                                        )
                                                    }
                                                >
                                                    <Trash2 size={16} />
                                                </Button>
                                            </TableCell>
                                        )}
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </AppLayout>
    );
}

import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import TournamentHeader from '@/pages/tournaments/partials/tournament-header';
import TournamentTabs from '@/pages/tournaments/partials/tournament-tabs';
import { Gender, Tournament, TournamentCategory } from '@/pages/tournaments';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { MoreHorizontal, Users } from 'lucide-react';
import { useState } from 'react';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';

interface Props {
    tournament: Tournament;
    categories: TournamentCategory[];
}

const ageCategoryLabel: Record<number, string> = {
    0: 'Preparation', 1: 'Super Mini', 2: 'Mini', 3: 'Pupils', 4: 'Juveniles',
    5: 'Cadets', 6: 'Juniors', 7: 'Under 23', 8: 'Seniors', 9: 'Veterans',
};

const weightCategoryLabel: Record<number, string> = {
    0: '-48kg', 1: '-52kg', 2: '-57kg', 3: '-60kg', 4: '-63kg',
    5: '-66kg', 6: '-70kg', 7: '-73kg', 8: '-78kg', 9: '-81kg',
    10: '-90kg', 11: '-100kg', 12: '+100kg', 13: '+78kg',
};

const categoryStatusLabel: Record<number, string> = {
    0: 'Pending', 1: 'Seeding', 2: 'Active', 3: 'Completed',
};

const categoryStatusVariant: Record<number, string> = {
    0: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
    1: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
    2: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    3: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
};

export default function CategoriesIndex({ tournament, categories }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tournaments', href: '/manage/tournaments' },
        { title: tournament.name, href: `/manage/tournaments/${tournament.id}/categories` },
        { title: 'Categories', href: `/manage/tournaments/${tournament.id}/categories` },
    ];

    const [categoryToDelete, setCategoryToDelete] = useState<TournamentCategory | null>(null);

    const handleConfirmDelete = () => {
        if (categoryToDelete) {
            router.delete(`/manage/tournaments/${tournament.id}/categories/${categoryToDelete.id}`);
            setCategoryToDelete(null);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${tournament.name} – Categories`} />

            <div className="px-4 py-6">
                <TournamentHeader tournament={tournament} />
                <TournamentTabs tournamentId={tournament.id} activeTab="categories" />

                <div className="flex items-center justify-between mb-4">
                    <h2 className="text-lg font-semibold">Competition Categories</h2>
                    <Button asChild>
                        <Link href={`/manage/tournaments/${tournament.id}/categories/create`}>
                            Add Category
                        </Link>
                    </Button>
                </div>

                {categories.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12 text-center">
                            <Users className="mb-3 h-10 w-10 text-muted-foreground" />
                            <p className="text-muted-foreground">No categories yet. Add a category to start registering players.</p>
                            <Button className="mt-4" asChild>
                                <Link href={`/manage/tournaments/${tournament.id}/categories/create`}>
                                    Add First Category
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {categories.map((category) => (
                            <Card key={category.id} className="relative">
                                <CardContent className="pt-5">
                                    <div className="flex items-start justify-between">
                                        <div>
                                            <p className="font-semibold text-base">
                                                {category.name ?? `${category.gender === Gender.Male ? 'Male' : 'Female'} ${ageCategoryLabel[Number(category.age_category)]} ${weightCategoryLabel[Number(category.weight_category)]}`}
                                            </p>
                                            <p className="text-sm text-muted-foreground mt-1">
                                                Age {category.min_age}–{category.max_age} yrs · Weight {category.min_weight}–{category.max_weight} kg
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {category.registrations_count ?? 0} registered
                                            </p>
                                        </div>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" className="h-8 w-8 p-0">
                                                    <MoreHorizontal className="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem asChild>
                                                    <Link href={`/manage/tournaments/${tournament.id}/categories/${category.id}/registrations`}>
                                                        Registrations
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem asChild>
                                                    <Link href={`/manage/tournaments/${tournament.id}/categories/${category.id}/matches`}>
                                                        Matches
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem asChild>
                                                    <Link href={`/manage/tournaments/${tournament.id}/categories/${category.id}/edit`}>
                                                        Edit
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem onClick={() => setCategoryToDelete(category)}>
                                                    Delete
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                    <div className="mt-3">
                                        <Badge className={categoryStatusVariant[Number(category.status)]}>
                                            {categoryStatusLabel[Number(category.status)] ?? 'Unknown'}
                                        </Badge>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>

            <Dialog open={!!categoryToDelete} onOpenChange={(open) => !open && setCategoryToDelete(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Category</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete this category? All registrations will also be removed.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setCategoryToDelete(null)}>Cancel</Button>
                        <Button variant="destructive" onClick={handleConfirmDelete}>Delete</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

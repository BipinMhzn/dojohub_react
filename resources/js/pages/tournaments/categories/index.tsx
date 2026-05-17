import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import TournamentLayout from '@/layouts/tournament/layout';
import { Tournament, TournamentCategory } from '@/pages/tournaments';
import { getCategoryColumns } from '@/pages/tournaments/partials/category-columns';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

interface Props {
    tournament: Tournament;
    categories: TournamentCategory[];
}

export default function TournamentCategories({ tournament, categories }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tournaments', href: '/manage/tournaments' },
        { title: tournament.name, href: `/manage/tournaments/${tournament.id}` },
        { title: 'Categories', href: `/manage/tournaments/${tournament.id}/categories` },
    ];

    const [categoryToDelete, setCategoryToDelete] = useState<TournamentCategory | null>(null);

    const columns = getCategoryColumns({
        tournamentId: tournament.id,
        onDelete: (category) => setCategoryToDelete(category),
    });

    const handleConfirmDelete = () => {
        if (categoryToDelete) {
            router.delete(`/manage/tournaments/${tournament.id}/categories/${categoryToDelete.id}`);
            setCategoryToDelete(null);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${tournament.name} – Categories`} />

            <TournamentLayout tournament={tournament} activeKey="categories">
                <div className="flex items-center justify-between">
                    <HeadingSmall
                        title="Categories"
                        description={`${categories.length} ${categories.length === 1 ? 'category' : 'categories'} in this tournament`}
                    />
                    <Button asChild>
                        <Link href={`/manage/tournaments/${tournament.id}/categories/create`}>Add Category</Link>
                    </Button>
                </div>

                <div className="mt-4">
                    <DataTable columns={columns} data={categories} />
                </div>
            </TournamentLayout>

            <Dialog open={!!categoryToDelete} onOpenChange={(open) => !open && setCategoryToDelete(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Category</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete this category? All registrations will also be removed.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setCategoryToDelete(null)}>
                            Cancel
                        </Button>
                        <Button variant="destructive" onClick={handleConfirmDelete}>
                            Delete
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import VenueLayout from '@/layouts/venue/layout';
import { Venue, VenueMat } from '@/pages/venues/index';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import React from 'react';

interface Props {
    venue: Venue;
}

export default function VenueMatsIndex({ venue }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Venues', href: '/manage/venues' },
        { title: venue.name, href: `/manage/venues/${venue.id}` },
        { title: 'Mats', href: `/manage/venues/${venue.id}/mats` },
    ];

    const matForm = useForm({ mat_number: '', display_name: '' });

    const handleAddMat = (e: React.FormEvent) => {
        e.preventDefault();
        matForm.post(`/manage/venues/${venue.id}/mats`, {
            preserveScroll: true,
            onSuccess: () => matForm.reset(),
        });
    };

    const handleDeleteMat = (mat: VenueMat) => {
        router.delete(`/manage/venues/${venue.id}/mats/${mat.id}`, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${venue.name} — Mats`} />

            <VenueLayout venue={venue} activeKey="mats">
                <div className="max-w-xl space-y-6">
                    <div className="flex items-start justify-between">
                        <HeadingSmall title="Tatami Mats" description="Manage the tatami mats available at this venue." />
                        <span className="text-sm text-muted-foreground">
                            {(venue.mats ?? []).length} {(venue.mats ?? []).length === 1 ? 'mat' : 'mats'}
                        </span>
                    </div>

                    {(venue.mats ?? []).length > 0 ? (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Mat Number</TableHead>
                                    <TableHead>Display Name</TableHead>
                                    <TableHead />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {(venue.mats ?? []).map((mat) => (
                                    <TableRow key={mat.id}>
                                        <TableCell>{mat.mat_number}</TableCell>
                                        <TableCell>{mat.display_name ?? '—'}</TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="text-destructive hover:text-destructive"
                                                onClick={() => handleDeleteMat(mat)}
                                            >
                                                Remove
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    ) : (
                        <p className="text-sm text-muted-foreground">No mats added yet.</p>
                    )}

                    <form onSubmit={handleAddMat} className="space-y-3">
                        <div className="flex items-start gap-3">
                            <div className="w-24">
                                <Label htmlFor="mat_number">Mat Number</Label>
                                <Input
                                    id="mat_number"
                                    type="number"
                                    min={1}
                                    max={99}
                                    value={matForm.data.mat_number}
                                    className="mt-1"
                                    onChange={(e) => matForm.setData('mat_number', e.target.value)}
                                    disabled={matForm.processing}
                                />
                            </div>
                            <div className="flex-1">
                                <Label htmlFor="mat_display_name">Display Name</Label>
                                <Input
                                    id="mat_display_name"
                                    value={matForm.data.display_name}
                                    className="mt-1"
                                    onChange={(e) => matForm.setData('display_name', e.target.value)}
                                    disabled={matForm.processing}
                                    placeholder="e.g. Tatami A"
                                />
                            </div>
                            <Button type="submit" disabled={matForm.processing} className="mt-6">
                                Add Mat
                            </Button>
                        </div>
                        <InputError message={matForm.errors.mat_number} />
                    </form>
                </div>
            </VenueLayout>
        </AppLayout>
    );
}

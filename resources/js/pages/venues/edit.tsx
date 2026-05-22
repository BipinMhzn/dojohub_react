import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { Country, Venue, VenueMat } from '@/pages/venues/index';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import React from 'react';

interface Props {
    venue: Venue;
    countries: Country[];
}

export default function EditVenue({ venue, countries }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Venues', href: '/manage/venues' },
        { title: venue.name, href: `/manage/venues/${venue.id}/edit` },
    ];

    const { data, setData, put, processing, errors } = useForm({
        name: venue.name,
        address_line_1: venue.address?.address_line_1 ?? '',
        address_line_2: venue.address?.address_line_2 ?? '',
        city: venue.address?.city ?? '',
        state: venue.address?.state ?? '',
        postal_code: venue.address?.postal_code ?? '',
        country_code: venue.address?.country_code ?? '',
        latitude: venue.address?.latitude?.toString() ?? '',
        longitude: venue.address?.longitude?.toString() ?? '',
    });

    const matForm = useForm({ mat_number: '', display_name: '' });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/manage/venues/${venue.id}`, { preserveScroll: true });
    };

    const handleAddMat = (e: React.FormEvent) => {
        e.preventDefault();
        matForm.post(`/manage/venues/${venue.id}/mats`, { preserveScroll: true, onSuccess: () => matForm.reset() });
    };

    const handleDeleteMat = (mat: VenueMat) => {
        router.delete(`/manage/venues/${venue.id}/mats/${mat.id}`, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Venue: ${venue.name}`} />
            <div className="px-4 py-6">
                <Heading title="Venues" description={`Edit ${venue.name}`} />

                <form onSubmit={handleSubmit} className="mt-6 max-w-xl space-y-4">
                    <div>
                        <Label htmlFor="name">Venue Name</Label>
                        <Input id="name" value={data.name} className="mt-1 block w-full" onChange={(e) => setData('name', e.target.value)} disabled={processing} />
                        <InputError message={errors.name} />
                    </div>

                    <div>
                        <Label htmlFor="country_code">Country</Label>
                        <Select value={data.country_code} onValueChange={(v) => setData('country_code', v)} disabled={processing}>
                            <SelectTrigger className="mt-1 w-full">
                                <SelectValue placeholder="Select a country" />
                            </SelectTrigger>
                            <SelectContent>
                                {countries.map((c) => (
                                    <SelectItem key={c.code} value={c.code}>{c.name}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.country_code} />
                    </div>

                    <div>
                        <Label htmlFor="state">State / Province</Label>
                        <Input id="state" value={data.state} className="mt-1 block w-full" onChange={(e) => setData('state', e.target.value)} disabled={processing} placeholder="Optional" />
                        <InputError message={errors.state} />
                    </div>

                    <div>
                        <Label htmlFor="city">City</Label>
                        <Input id="city" value={data.city} className="mt-1 block w-full" onChange={(e) => setData('city', e.target.value)} disabled={processing} />
                        <InputError message={errors.city} />
                    </div>

                    <div>
                        <Label htmlFor="address_line_1">Address Line 1</Label>
                        <Input id="address_line_1" value={data.address_line_1} className="mt-1 block w-full" onChange={(e) => setData('address_line_1', e.target.value)} disabled={processing} />
                        <InputError message={errors.address_line_1} />
                    </div>

                    <div>
                        <Label htmlFor="address_line_2">Address Line 2</Label>
                        <Input id="address_line_2" value={data.address_line_2} className="mt-1 block w-full" onChange={(e) => setData('address_line_2', e.target.value)} disabled={processing} placeholder="Optional" />
                        <InputError message={errors.address_line_2} />
                    </div>

                    <div>
                        <Label htmlFor="postal_code">Postal Code</Label>
                        <Input id="postal_code" value={data.postal_code} className="mt-1 block w-full" onChange={(e) => setData('postal_code', e.target.value)} disabled={processing} placeholder="Optional" />
                        <InputError message={errors.postal_code} />
                    </div>

                    <div className="flex gap-4">
                        <Button type="submit" disabled={processing}>{processing ? 'Saving...' : 'Save'}</Button>
                        <Button type="button" variant="outline" asChild>
                            <a href="/manage/venues">Cancel</a>
                        </Button>
                    </div>
                </form>

                {/* ── Tatami Mats ── */}
                <div className="mt-10 max-w-xl">
                    <h2 className="mb-4 text-lg font-semibold dark:text-white">Tatami Mats</h2>

                    {(venue.mats ?? []).length > 0 ? (
                        <Table className="mb-6">
                            <TableHeader>
                                <TableRow>
                                    <TableHead>#</TableHead>
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
                                            <Button variant="ghost" size="sm" className="text-destructive hover:text-destructive" onClick={() => handleDeleteMat(mat)}>
                                                Remove
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    ) : (
                        <p className="mb-4 text-sm text-muted-foreground">No mats added yet.</p>
                    )}

                    <form onSubmit={handleAddMat} className="flex items-end gap-3">
                        <div className="w-24">
                            <Label htmlFor="mat_number">Mat #</Label>
                            <Input id="mat_number" type="number" min={1} max={99} value={matForm.data.mat_number} className="mt-1" onChange={(e) => matForm.setData('mat_number', e.target.value)} disabled={matForm.processing} />
                            <InputError message={matForm.errors.mat_number} />
                        </div>
                        <div className="flex-1">
                            <Label htmlFor="mat_display_name">Display Name</Label>
                            <Input id="mat_display_name" value={matForm.data.display_name} className="mt-1" onChange={(e) => matForm.setData('display_name', e.target.value)} disabled={matForm.processing} placeholder="e.g. Tatami A" />
                        </div>
                        <Button type="submit" disabled={matForm.processing}>Add Mat</Button>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
